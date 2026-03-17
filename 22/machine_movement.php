<?php
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Initialize Game Logic & Persistent Stats
if (!isset($_SESSION['game_history'])) {
    $_SESSION['game_history'] = [];
}
if (!isset($_SESSION['best_score'])) {
    $_SESSION['best_score'] = "--";
}

if (!isset($_SESSION['game_target']) || isset($_GET['reset'])) {
    $_SESSION['game_target'] = rand(1, 100);
    $_SESSION['game_attempts'] = 0;
    $_SESSION['game_status'] = "playing";
    $_SESSION['game_history'] = []; // Clear log for new round
    header("Location: machine_movement.php"); 
    exit();
}

$message = "Identify the secret Machine ID (1-100)";

if (isset($_POST['guess']) && $_SESSION['game_status'] == "playing") {
    $guess = (int)$_POST['guess'];
    $_SESSION['game_attempts']++;
    
    // Add to History Log
    $hint = "";
    if ($guess < $_SESSION['game_target']) {
        $hint = "Higher";
        $message = "System Note: ID is HIGHER than " . $guess;
    } elseif ($guess > $_SESSION['game_target']) {
        $hint = "Lower";
        $message = "System Note: ID is LOWER than " . $guess;
    } else {
        $hint = "MATCH";
        $_SESSION['game_status'] = "won";
        
        // Update Best Score (Lowest attempts)
        if ($_SESSION['best_score'] == "--" || $_SESSION['game_attempts'] < $_SESSION['best_score']) {
            $_SESSION['best_score'] = $_SESSION['game_attempts'];
        }
    }
    
    // Prepend to history so latest is on top
    array_unshift($_SESSION['game_history'], ["guess" => $guess, "hint" => $hint]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Machine Guard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ojt-orange: #ff6600;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --success-green: #2ecc71;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #0f172a, #1e293b, #334155, #0f172a);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            color: white;
            overflow: hidden;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Scoreboard & History Sidebars --- */
        .side-panel {
            position: absolute;
            top: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 10;
        }
        
        .right-panel { right: 20px; width: 180px; height: 90vh; }
        .left-panel { left: 20px; }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            padding: 12px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .stat-card small { display: block; font-size: 0.7rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 1px; }
        .stat-card span { font-size: 1.4rem; font-weight: 700; color: var(--ojt-orange); }

        /* --- History Log --- */
        .history-container {
            flex-grow: 1;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 15px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .history-container h4 { margin: 0 0 10px 0; font-size: 0.8rem; opacity: 0.8; text-align: center; border-bottom: 1px solid var(--glass-border); padding-bottom: 5px; }
        .log-entry {
            display: flex;
            justify-content: space-between;
            padding: 8px 10px;
            background: rgba(255,255,255,0.05);
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 0.85rem;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .log-val { font-weight: 700; color: var(--ojt-orange); }
        .log-hint { opacity: 0.7; font-size: 0.75rem; }

        /* --- Main Game Card --- */
        .game-card {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 3rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 2;
        }

        .btn-back {
            color: white; text-decoration: none; background: var(--glass-bg);
            padding: 10px 20px; border-radius: 50px; font-size: 0.9rem;
            border: 1px solid var(--glass-border); transition: 0.3s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: white; color: var(--ojt-orange); }

        input[type="number"] {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--glass-border);
            border-radius: 15px; padding: 15px; width: 100%;
            box-sizing: border-box; color: white; font-size: 1.8rem;
            text-align: center; outline: none; transition: 0.3s; margin-bottom: 1.5rem;
        }
        input[type="number"]:focus { border-color: var(--ojt-orange); background: rgba(255,255,255,0.1); }

        .btn-guess {
            background: var(--ojt-orange);
            color: white; border: none; padding: 15px;
            border-radius: 15px; font-weight: 600; cursor: pointer;
            transition: 0.3s; width: 100%; font-size: 1rem;
        }
        .btn-guess:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255, 102, 0, 0.4); }

        /* --- Win Modal Overlay --- */
        .win-overlay {
            position: absolute; inset: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(15px);
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; border-radius: 30px; z-index: 100;
            animation: modalPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes modalPop { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
        .win-icon { font-size: 5rem; color: var(--success-green); margin-bottom: 1.5rem; filter: drop-shadow(0 0 15px var(--success-green)); }
        .btn-restart {
            background: var(--success-green); color: white; text-decoration: none;
            padding: 15px 35px; border-radius: 12px; font-weight: 700;
            margin-top: 2rem; transition: 0.3s; text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="side-panel left-panel">
        <a href="index_user.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <div class="stat-card">
            <small>Personal Best</small>
            <span><?php echo $_SESSION['best_score']; ?></span>
        </div>
    </div>

    <div class="side-panel right-panel">
        <div class="stat-card">
            <small>Current Attempts</small>
            <span><?php echo $_SESSION['game_attempts']; ?></span>
        </div>
        
        <div class="history-container">
            <h4>ACTIVITY LOG</h4>
            <?php foreach ($_SESSION['game_history'] as $log): ?>
                <div class="log-entry">
                    <span class="log-val">#<?php echo $log['guess']; ?></span>
                    <span class="log-hint"><?php echo $log['hint']; ?></span>
                </div>
            <?php endforeach; ?>
            <?php if(empty($_SESSION['game_history'])): ?>
                <p style="font-size: 0.7rem; opacity: 0.5; text-align: center;">No activity detected.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="game-card">
        
        <?php if ($_SESSION['game_status'] == "won"): ?>
            <div class="win-overlay">
                <i class="fa-solid fa-circle-check win-icon"></i>
                <h2 style="margin:0; font-size: 1.8rem;">ACCESS GRANTED</h2>
                <p style="margin-top:10px; opacity: 0.8;">Machine ID <strong>#<?php echo $_SESSION['game_target']; ?></strong> Verified</p>
                <div style="background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 10px; margin-top: 10px;">
                    <small style="display:block; opacity: 0.6;">EFFICIENCY RATING</small>
                    <span style="font-size: 1.2rem; font-weight: 700; color: var(--success-green);">
                        <?php echo $_SESSION['game_attempts']; ?> Attempts
                    </span>
                </div>
                <a href="machine_movement.php?reset=1" class="btn-restart">New Session</a>
            </div>
        <?php endif; ?>

        <i class="fa-solid fa-microchip" style="font-size: 3.5rem; margin-bottom: 1.5rem; color: var(--ojt-orange);"></i>
        <h2 style="letter-spacing: 2px;">MACHINE GUARD</h2>
        <p id="hint-text" style="min-height: 3em;"><?php echo $message; ?></p>

        <form method="POST">
            <input type="number" name="guess" placeholder="00" min="1" max="100" required autofocus 
                   <?php echo ($_SESSION['game_status'] == "won") ? 'disabled' : ''; ?>>
            
            <?php if ($_SESSION['game_status'] == "playing"): ?>
                <button type="submit" class="btn-guess">VERIFY IDENTITY</button>
            <?php endif; ?>
        </form>

        <a href="machine_movement.php?reset=1" style="display:block; margin-top:25px; color:white; opacity:0.4; font-size:0.75rem; text-decoration:none; text-transform: uppercase; letter-spacing: 1px;">Initialize Emergency Reset</a>
    </div>

</body>
</html>

<?php include 'footer.php'; ?>