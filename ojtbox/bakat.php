<?php
session_start();

// --- NEW BANK TRANSFER LOGIC ---
if (isset($_POST['transfer_amount'])) {
    $_SESSION['balance'] = (int)$_POST['transfer_amount'];
    $_SESSION['transfer_complete'] = true;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Reset (Modified to force re-transfer)
if (isset($_POST['reset_bankroll'])) {
    unset($_SESSION['balance']);
    unset($_SESSION['transfer_complete']);
    $_SESSION['history'] = []; 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// --- END NEW LOGIC ---

// Initialize Bankroll
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0; // Start at 0 until transfer
}

// Initialize History
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

class Baccarat {
    private $deck = [];
    public $pHand = [];
    public $bHand = [];
    public $pScore;
    public $bScore;

    public function __construct() {
        $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        $suits = [['s'=>'&spades;', 'c'=>'black'], ['s'=>'&hearts;', 'c'=>'red'], ['s'=>'&diams;', 'c'=>'red'], ['s'=>'&clubs;', 'c'=>'black']];
        foreach ($suits as $suit) {
            foreach ($values as $v) {
                $this->deck[] = ['v' => $v, 's' => $suit['s'], 'color' => $suit['c']];
            }
        }
        shuffle($this->deck);
    }

    private function val($v) {
        if (is_numeric($v)) return (int)$v;
        return ($v == 'A') ? 1 : 0;
    }

    public function getScore($hand) {
        $total = 0;
        foreach ($hand as $card) $total += $this->val($card['v']);
        return $total % 10;
    }

    public function play() {
        // Initial Deal
        $this->pHand[] = array_pop($this->deck);
        $this->bHand[] = array_pop($this->deck);
        $this->pHand[] = array_pop($this->deck);
        $this->bHand[] = array_pop($this->deck);

        $this->pScore = $this->getScore($this->pHand);
        $this->bScore = $this->getScore($this->bHand);

        // Third Card Logic (Punto Banco Rules)
        if ($this->pScore < 8 && $this->bScore < 8) {
            $pThird = null;
            if ($this->pScore <= 5) {
                $card = array_pop($this->deck);
                $this->pHand[] = $card;
                $pThird = $this->val($card['v']);
            }

            if ($pThird === null) {
                if ($this->bScore <= 5) $this->bHand[] = array_pop($this->deck);
            } else {
                if ($this->bankerDraws($this->bScore, $pThird)) {
                    $this->bHand[] = array_pop($this->deck);
                }
            }
        }
        
        $this->pScore = $this->getScore($this->pHand);
        $this->bScore = $this->getScore($this->bHand);
        
        if ($this->pScore > $this->bScore) return 'player';
        if ($this->bScore > $this->pScore) return 'banker';
        return 'tie';
    }

    private function bankerDraws($b, $p3) {
        if ($b <= 2) return true;
        if ($b == 3 && $p3 != 8) return true;
        if ($b == 4 && in_array($p3, [2,3,4,5,6,7])) return true;
        if ($b == 5 && in_array($p3, [4,5,6,7])) return true;
        if ($b == 6 && in_array($p3, [6,7])) return true;
        return false;
    }
}

$gameData = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bet_amount'])) {
    $bet = (int)$_POST['bet_amount'];
    $side = $_POST['bet_on'];

    if ($bet <= $_SESSION['balance'] && $bet > 0) {
        $engine = new Baccarat();
        $winner = $engine->play();
        
        // Save to History
        array_unshift($_SESSION['history'], $winner);
        if(count($_SESSION['history']) > 10) array_pop($_SESSION['history']);

        if ($winner == $side) {
            $winAmt = ($winner == 'tie') ? $bet * 8 : $bet;
            $_SESSION['balance'] += $winAmt;
            $resultMsg = "WIN! +$" . $winAmt;
        } else {
            $_SESSION['balance'] -= $bet;
            $resultMsg = "LOSE! -$" . $bet;
        }

        $gameData = [
            'pHand' => $engine->pHand,
            'bHand' => $engine->bHand,
            'pScore' => $engine->pScore,
            'bScore' => $engine->bScore,
            'winner' => $winner,
            'msg' => $resultMsg,
            'newBalance' => $_SESSION['balance']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Royale Baccarat</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #d4af37; --felt: #0b4d2c; --p-blue: #3498db; --b-red: #e74c3c; --t-green: #2ecc71; }
        body { margin: 0; background: #050505; color: white; font-family: 'Montserrat', sans-serif; overflow: hidden; }
        
        /* Bank Transfer Modal */
        .bank-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); }
        .bank-modal { background: #111; border: 2px solid var(--gold); padding: 40px; border-radius: 20px; text-align: center; width: 400px; box-shadow: 0 0 50px rgba(212, 175, 55, 0.3); }
        .bank-modal h2 { color: var(--gold); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 3px; }
        .bank-modal p { font-size: 13px; color: #888; margin-bottom: 25px; }
        .bank-input-group { position: relative; margin-bottom: 25px; }
        .bank-input-group span { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gold); font-weight: bold; }
        .bank-input { width: 100%; background: #000; border: 1px solid #444; border-radius: 10px; padding: 15px 15px 15px 35px; color: #fff; font-size: 20px; box-sizing: border-box; }
        .btn-transfer { width: 100%; background: linear-gradient(135deg, #d4af37 0%, #aa8919 100%); border: none; padding: 15px; border-radius: 10px; font-weight: bold; cursor: pointer; text-transform: uppercase; color: #000; letter-spacing: 1px; }

        /* Layout */
        .casino-wrapper { height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle, #1a4a2e 0%, #051a0d 100%); }
        .header { position: absolute; top: 20px; width: 90%; display: flex; justify-content: space-between; align-items: center; }
        .balance-box { font-size: 24px; color: var(--gold); border: 2px solid var(--gold); padding: 10px 25px; border-radius: 50px; background: rgba(0,0,0,0.5); }

        /* History Floating Tab */
        .history-tab { position: absolute; right: 20px; top: 100px; width: 60px; background: rgba(0,0,0,0.7); border: 1px solid var(--gold); border-radius: 15px; padding: 15px 5px; display: flex; flex-direction: column; align-items: center; gap: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); z-index: 50; }
        .hist-dot { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; border: 2px solid rgba(255,255,255,0.2); }
        .hist-p { background: var(--p-blue); color: white; }
        .hist-b { background: var(--b-red); color: white; }
        .hist-t { background: var(--t-green); color: white; }

        /* The Table */
        .table { width: 900px; height: 500px; background: var(--felt); border: 15px solid #3d2b1f; border-radius: 250px; position: relative; box-shadow: inset 0 0 100px #000, 0 30px 60px rgba(0,0,0,0.8); }
        .shoe { position: absolute; top: -30px; left: 50%; transform: translateX(-50%); width: 80px; height: 110px; background: #222; border: 2px solid var(--gold); border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--gold); }

        /* Cards */
        .hand-container { position: absolute; top: 100px; width: 100%; display: flex; justify-content: space-around; }
        .card-slot { text-align: center; }
        .card-area { display: flex; gap: 10px; min-height: 120px; min-width: 200px; justify-content: center; }
        .card { width: 75px; height: 110px; background: #fff; border-radius: 8px; color: #000; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; position: relative; visibility: hidden; box-shadow: 0 5px 10px rgba(0,0,0,0.4); }
        .card.red { color: var(--b-red); }
        .score-pill { display: inline-block; margin-top: 10px; background: rgba(0,0,0,0.6); padding: 5px 15px; border-radius: 20px; font-size: 14px; border: 1px solid rgba(255,255,255,0.2); }

        /* Betting Area */
        .betting-grid { position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); display: flex; gap: 20px; }
        .bet-box { width: 180px; height: 110px; border: 2px dashed rgba(255,255,255,0.3); border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; background: rgba(0,0,0,0.2); }
        .bet-box:hover { background: rgba(255,255,255,0.1); }
        .bet-box h3 { margin: 0; font-size: 18px; letter-spacing: 2px; }
        .bet-box span { font-size: 11px; color: var(--gold); }
        
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + .bet-box.p { border-color: var(--p-blue); background: rgba(52, 152, 219, 0.2); box-shadow: 0 0 20px var(--p-blue); }
        input[type="radio"]:checked + .bet-box.b { border-color: var(--b-red); background: rgba(231, 76, 60, 0.2); box-shadow: 0 0 20px var(--b-red); }
        input[type="radio"]:checked + .bet-box.t { border-color: var(--t-green); background: rgba(46, 204, 113, 0.2); box-shadow: 0 0 20px var(--t-green); }

        /* Controls */
        .controls { margin-top: 30px; display: flex; align-items: center; gap: 15px; }
        .bet-input { background: #000; border: 2px solid var(--gold); color: var(--gold); padding: 12px; border-radius: 10px; font-size: 20px; width: 100px; text-align: center; }
        .btn-deal { background: linear-gradient(135deg, #d4af37 0%, #aa8919 100%); border: none; padding: 15px 50px; border-radius: 10px; font-weight: bold; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; }
        
        /* Announcements */
        .overlay { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 70px; font-weight: bold; color: var(--gold); text-shadow: 0 0 20px #000; pointer-events: none; z-index: 100; opacity: 0; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['transfer_complete'])): ?>
    <div class="bank-overlay">
        <div class="bank-modal">
            <h2>Bank Transfer</h2>
            <p>Enter your starting credit to enter the table.</p>
            <form method="POST">
                <div class="bank-input-group">
                    <span>$</span>
                    <input type="number" name="transfer_amount" class="bank-input" placeholder="0.00" min="100" max="100000" required autofocus>
                </div>
                <button type="submit" class="btn-transfer">Transfer Funds</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="casino-wrapper">
    <div class="header">
        <div class="balance-box">BANKROLL: $<?php echo $_SESSION['balance']; ?></div>
        <form method="POST"><button name="reset_bankroll" style="background:none; border:1px solid #444; color:#666; cursor:pointer; border-radius:5px;">Reset Game</button></form>
    </div>

    <div class="history-tab">
        <?php foreach($_SESSION['history'] as $win): ?>
            <?php 
                $class = ($win == 'player') ? 'hist-p' : (($win == 'banker') ? 'hist-b' : 'hist-t');
                $label = strtoupper($win[0]);
            ?>
            <div class="hist-dot <?php echo $class; ?>"><?php echo $label; ?></div>
        <?php endforeach; ?>
    </div>

    <form id="gameForm" method="POST">
        <div class="table">
            <div class="shoe">BACCARAT</div>

            <div class="hand-container">
                <div class="card-slot">
                    <div class="card-area" id="p-cards"></div>
                    <div class="score-pill">PLAYER: <span id="p-score-val">0</span></div>
                </div>
                <div class="card-slot">
                    <div class="card-area" id="b-cards"></div>
                    <div class="score-pill">BANKER: <span id="b-score-val">0</span></div>
                </div>
            </div>

            <div class="betting-grid">
                <label>
                    <input type="radio" name="bet_on" value="player" required <?php if(isset($_POST['bet_on']) && $_POST['bet_on'] == 'player') echo 'checked'; ?>>
                    <div class="bet-box p"><h3>PLAYER</h3><span>1 : 1</span></div>
                </label>
                <label>
                    <input type="radio" name="bet_on" value="tie" <?php if(isset($_POST['bet_on']) && $_POST['bet_on'] == 'tie') echo 'checked'; ?>>
                    <div class="bet-box t"><h3>TIE</h3><span>8 : 1</span></div>
                </label>
                <label>
                    <input type="radio" name="bet_on" value="banker" <?php if(isset($_POST['bet_on']) && $_POST['bet_on'] == 'banker') echo 'checked'; ?>>
                    <div class="bet-box b"><h3>BANKER</h3><span>1 : 1</span></div>
                </label>
            </div>
        </div>

        <div class="controls">
            <input type="number" name="bet_amount" class="bet-input" value="<?php echo $_POST['bet_amount'] ?? 100; ?>" min="10" max="<?php echo $_SESSION['balance']; ?>">
            <button type="submit" class="btn-deal" id="dealBtn">Deal Cards</button>
        </div>
    </form>
</div>

<div id="announcement" class="overlay"></div>

<script>
    const data = <?php echo json_encode($gameData); ?>;

    if (data) {
        document.getElementById('dealBtn').disabled = true;
        const pArea = document.getElementById('p-cards');
        const bArea = document.getElementById('b-cards');
        const shoe = document.querySelector('.shoe');

        function createCard(c, container) {
            const el = document.createElement('div');
            el.className = `card ${c.color}`;
            el.innerHTML = `<div>${c.v}</div><div style="font-size:30px">${c.s}</div>`;
            container.appendChild(el);
            return el;
        }

        async function runGame() {
            const queue = [];
            queue.push({card: data.pHand[0], area: pArea});
            queue.push({card: data.bHand[0], area: bArea});
            queue.push({card: data.pHand[1], area: pArea});
            queue.push({card: data.bHand[1], area: bArea});
            
            if(data.pHand[2]) queue.push({card: data.pHand[2], area: pArea});
            if(data.bHand[2]) queue.push({card: data.bHand[2], area: bArea});

            for (let i = 0; i < queue.length; i++) {
                const item = queue[i];
                const cardEl = createCard(item.card, item.area);
                const rect = cardEl.getBoundingClientRect();
                const shoeRect = shoe.getBoundingClientRect();

                gsap.set(cardEl, {
                    visibility: 'visible',
                    x: shoeRect.left - rect.left,
                    y: shoeRect.top - rect.top,
                    rotation: 30,
                    scale: 0.2,
                    opacity: 0
                });

                await gsap.to(cardEl, {
                    x: 0, y: 0, rotation: 0, scale: 1, opacity: 1,
                    duration: 0.6, ease: "power2.out"
                });
            }

            document.getElementById('p-score-val').innerText = data.pScore;
            document.getElementById('b-score-val').innerText = data.bScore;

            const ann = document.getElementById('announcement');
            ann.innerText = data.winner.toUpperCase() + " WINS!";
            gsap.to(ann, { opacity: 1, scale: 1.2, duration: 0.5, yoyo: true, repeat: 1 });

            setTimeout(() => {
                document.getElementById('dealBtn').disabled = false;
            }, 2000);
        }

        runGame();
    }
</script>

</body>
</html>