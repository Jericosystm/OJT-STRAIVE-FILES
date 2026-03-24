<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Sacramento";

// --- SWAP HANDLER (Syncs with Inventory) ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT hostname, status, cubicle_no FROM production_floor_map WHERE id = ?");
        $stmt->bind_param("i", $sourceId);
        $stmt->execute();
        $sourceMap = $stmt->get_result()->fetch_assoc();

        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $targetMap = $stmt->get_result()->fetch_assoc();

        $srcHost = $sourceMap['hostname'];
        $tgtHost = $targetMap['hostname'];
        $srcCubicle = $sourceMap['cubicle_no'];
        $tgtCubicle = $targetMap['cubicle_no'];

        // 1. Update Floor Map Table
        $updateMap = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=? WHERE id=?");
        $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
        $updateMap->execute();
        $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
        $updateMap->execute();

        // 2. Sync with Inventory Table (Temporary Hostname to avoid key conflicts)
        $tempHostSuffix = "_SWAP_" . time();

        if (!empty($srcHost)) {
            $tmpName = $srcHost . $tempHostSuffix;
            $upd1 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, hostname = ? WHERE hostname = ?");
            $upd1->bind_param("sss", $tgtCubicle, $tmpName, $srcHost);
            $upd1->execute();
        }

        if (!empty($tgtHost)) {
            $upd2 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ? WHERE hostname = ?");
            $upd2->bind_param("ss", $srcCubicle, $tgtHost);
            $upd2->execute();
        }

        if (!empty($srcHost)) {
            $tmpName = $srcHost . $tempHostSuffix;
            $upd3 = $conn->prepare("UPDATE inventory_items SET hostname = ? WHERE hostname = ?");
            $upd3->bind_param("ss", $srcHost, $tmpName);
            $upd3->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// --- UPDATE HANDLER ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = trim($_POST['hostname'] ?? '');
    $switch_port = $_POST['switch_port'] ?? ''; 
    $status = $_POST['status'] ?? 'Vacant'; 

    if($status === 'Vacant') { $hostname = ''; }

    // Logic: Save using campaign column as per Sacramento's table structure
    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: sacramento.php");
        exit();
    }
}

// Fetch Stations
$stations = []; 
$occupiedCount = 0;
$vacantCount = 0;

$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC");
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if ($row['status'] === 'Occupied') $occupiedCount++;
    else $vacantCount++;
}

// Helper to render layout items
function renderSeat($index, $stations) {
    $row = $stations[$index-1] ?? null;
    $id = $row['id'] ?? 0;
    $status = $row['status'] ?? 'Vacant';
    $hostname = $row['hostname'] ?? '';
    $port = $row['switch_port'] ?? '';
    
    $isPortOnly = ($index > 16);
    $label = $isPortOnly ? "PORT-" . ($index - 16) : "SAC-" . str_pad($index, 4, '0', STR_PAD_LEFT);
    $activeClass = (!$isPortOnly && $status === 'Occupied') ? 'Occupied' : 'Vacant';
    
    if($isPortOnly) {
        return "<div class='port-item' data-id='$id' data-hostname='".strtolower($hostname)."' onclick=\"handleSeatClick(event, '$id', '$label', '$hostname', '$port', '$status', true)\">
                    <i class='fa-solid fa-plug-circle-bolt'></i>
                    <span>$label</span>
                </div>";
    } else {
        return "<div class='seat-box $activeClass' data-id='$id' data-hostname='".strtolower($hostname)."' onclick=\"handleSeatClick(event, '$id', '$label', '$hostname', '$port', '$status', false)\">
                    <div class='seat-label'>$label</div>
                    <div class='seat-host'>".($hostname ?: 'Available')."</div>
                    <div class='seat-port'>$port</div>
                </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Sacramento Layout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #22c55e;
            --bg: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --occupied-bg: #f0fdf4;
            --occupied-border: #86efac;
            /* Updated Vacant Hover Colors to Match Boston Professionalism */
            --vacant-hover-bg: #f0fdf4; 
            --vacant-hover-border: #22c55e;
            --primary-orange: #ff6b00;
            --seat-w: 7.2vw;
            --seat-h: 10.5vh; 
            --gap: 1.2vh;
        }

        body, html { margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); height: 100vh; overflow: hidden; }

        .navbar { background: var(--primary); padding: 0 2rem; display: flex; align-items: center; height: 7vh; color: white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: white; text-decoration: none; margin-right: 15px; font-size: 1.2rem; }

        .edit-sidebar { position: fixed; right: 20px; top: 15vh; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); z-index: 100; text-align: center; width: 120px; }
        .swap-mode-title { font-weight: 800; font-size: 0.8rem; color: var(--text-dark); margin-bottom: 8px; }
        .swap-mode-desc { font-size: 0.6rem; color: var(--text-muted); margin-top: 8px; line-height: 1.3; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; grid-column: 1 / -1; margin-bottom: 2vh; }
        .floor-title { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); }
        .search-container { position: relative; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
        #searchInput { width: 300px; padding: 10px 15px 10px 35px; border-radius: 12px; border: 1px solid var(--border); outline: none; background: #f8fafc; font-family: inherit; }

        .edit-mode-active .seat-box, .edit-mode-active .port-item { cursor: grab !important; border: 2px dashed var(--primary-orange) !important; }
        .drag-over { background: #fff7ed !important; transform: scale(1.05) !important; border: 2px solid var(--primary-orange) !important; }
        .dimmed { opacity: 0.15; filter: grayscale(1); pointer-events: none; }

        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-orange); }
        input:checked + .slider:before { transform: translateX(22px); }

        .main-container { height: 93vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2vh 5vw; box-sizing: border-box; }
        .floor-plan-wrapper { background: white; border-radius: 30px; padding: 5vh 4vw; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.05); display: grid; grid-template-columns: 80px repeat(8, var(--seat-w)); grid-template-rows: auto auto 4vh auto; gap: var(--gap); width: fit-content; }

        .managers-container { grid-column: 7 / 9; grid-row: 2; display: flex; flex-direction: column; align-items: center; }
        .managers-row-flex { display: flex; gap: var(--gap); align-items: flex-end; }
        .managers-footer-title { margin-top: 15px; font-size: 0.65rem; font-weight: 800; color: #94a3b8; letter-spacing: 1.5px; text-transform: uppercase; }

        .seat-box { background: white; border: 1.5px solid var(--border); border-radius: 12px; padding: 0.5vh; height: var(--seat-h); width: var(--seat-w); display: flex; flex-direction: column; justify-content: center; text-align: center; cursor: pointer; transition: 0.2s; box-sizing: border-box; }
        
        /* Updated Hover State for Vacant Seats */
        .seat-box.Vacant:hover { 
            background: var(--vacant-hover-bg); 
            border-color: var(--vacant-hover-border); 
            transform: translateY(-2px); 
        }
        
        .seat-box.Occupied { background: var(--occupied-bg); border-color: var(--occupied-border); }

        .port-trigger-container { grid-column: 1; grid-row: 3; align-self: center; position: relative; }
        .port-main-box { background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; height: 7vh; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 800; color: var(--text-muted); cursor: pointer; font-size: 0.6rem; }
        .port-popup { visibility: hidden; opacity: 0; position: absolute; left: 110%; top: 50%; transform: translateY(-50%); background: white; border-radius: 15px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 100; width: 220px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid var(--border); transition: 0.2s; }
        .port-trigger-container:hover .port-popup { visibility: visible; opacity: 1; }
        .port-item { background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 6px; display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: 0.2s; }

        .samsung-tv-box { background: radial-gradient(circle at center, #ff29f0 0%, #9d0c79 100%); color: white; border-radius: 8px 8px 3px 3px; padding: 8px 4px; font-weight: 800; font-size: 0.6rem; text-align: center; margin-bottom: 6px; border: 2px solid #5a0446; display: flex; align-items: center; justify-content: center; gap: 6px; letter-spacing: 1px; text-transform: uppercase; cursor: default; }

        .horizontal-row-container { grid-column: 2 / 10; grid-row: 4; display: flex; gap: var(--gap); }
        .seat-label { font-size: 0.65rem; font-weight: 800; color: var(--text-muted); }
        .seat-host { font-size: 0.7rem; color: var(--text-dark); font-weight: 700; margin: 2px 0; }
        .seat-port { font-size: 0.55rem; color: #94a3b8; font-weight: 700; }

        .status-bar { margin-top: 3vh; display: flex; justify-content: center; gap: 30px; background: white; padding: 10px 40px; border-radius: 50px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .status-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.85rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1000; }
        .modal-content { background: white; width: 340px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-arrow-left"></i></a>
    <div style="font-weight: 800; letter-spacing: -0.5px;">OJTBox | Sacramento Live Map</div>
</nav>

<div class="edit-sidebar">
    <div class="swap-mode-title">Swap Mode</div>
    <label class="switch">
        <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
        <span class="slider"></span>
    </label>
    <div id="statusLabel" style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); margin-top: 5px;">OFF</div>
    <div class="swap-mode-desc">Drag one cubicle onto another to swap hosts.</div>
</div>

<div class="main-container">
    <div class="floor-plan-wrapper">
        <div class="header-flex">
            <div class="floor-title">Sacramento Floor Plan</div>
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
            </div>
        </div>

        <div class="port-trigger-container">
            <div class="port-main-box">
                <i class="fa-solid fa-network-wired" style="font-size: 1rem; margin-bottom: 4px;"></i>
                PORTS
            </div>
            <div class="port-popup">
                <?php for($i=17; $i<=21; $i++) echo renderSeat($i, $stations); ?>
            </div>
        </div>

        <div class="managers-container">
            <div class="managers-row-flex">
                <div class="stack-9-12">
                    <?php for($i=9; $i<=12; $i++) echo renderSeat($i, $stations); ?>
                </div>
                <div class="cell-tv-group" style="display:flex; flex-direction:column; width:var(--seat-w);">
                    <div class="samsung-tv-box">
                        <i class="fa-solid fa-display" style="font-size: 0.7rem;"></i> SAMSUNG TV
                    </div>
                    <div class="stack-13-16">
                        <?php for($i=13; $i<=16; $i++) echo renderSeat($i, $stations); ?>
                    </div>
                </div>
            </div>
            <div class="managers-footer-title">MANAGERS AREA</div>
        </div>

        <div class="horizontal-row-container">
            <?php for($i=1; $i<=8; $i++) echo renderSeat($i, $stations); ?>
        </div>
    </div>

    <div class="status-bar">
        <div class="status-item"><div class="dot" style="background: var(--primary);"></div> Occupied: <?php echo $occupiedCount; ?></div>
        <div class="status-item"><div class="dot" style="background: #cbd5e1;"></div> Vacant: <?php echo $vacantCount; ?></div>
        <div class="status-item" style="color: var(--text-muted); font-weight: 800; border-left: 2px solid var(--border); padding-left: 20px;">
            TOTAL: <?php echo ($occupiedCount + $vacantCount); ?>
        </div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.5rem; font-weight:800;">Station Details</h2>
        
        <form>
            <input type="hidden" id="seatId">
            
            <label style="font-size:0.7rem; font-weight:700; display:block; margin-bottom:5px;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; box-sizing: border-box;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">PORT</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <input type="text" id="seatSwitch" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; color: var(--text-muted); box-sizing: border-box;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">STATUS</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <select id="seatStatus" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; pointer-events: none; color: var(--text-muted); box-sizing: border-box;">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">HOSTNAME</label>
                    <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                </div>
                <input type="text" id="seatHost" readonly style="width:100%; padding:0.7rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; color: var(--text-muted); box-sizing: border-box;">
            </div>

            <div style="padding-top: 10px;">
                <button type="button" onclick="closeModal()" 
                        style="width: 100%; padding: 0.8rem; background: var(--primary); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">
                    CLOSE
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    let isEditMode = false;

    window.onload = function() {
        const savedMode = localStorage.getItem('sacSwapMode');
        if (savedMode === 'true') {
            document.getElementById('editToggle').checked = true;
            toggleEditMode();
        }
    };

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        localStorage.setItem('sacSwapMode', isEditMode);

        const body = document.getElementById('body');
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box, .port-item');

        if (isEditMode) {
            body.classList.add('edit-mode-active');
            label.innerText = "ON";
            label.style.color = "var(--primary-orange)";
            seats.forEach(seat => {
                seat.setAttribute('draggable', true);
                seat.addEventListener('dragstart', handleDragStart);
                seat.addEventListener('dragover', handleDragOver);
                seat.addEventListener('dragleave', handleDragLeave);
                seat.addEventListener('drop', handleDrop);
            });
        } else {
            body.classList.remove('edit-mode-active');
            label.innerText = "OFF";
            label.style.color = "var(--text-muted)";
            seats.forEach(seat => {
                seat.setAttribute('draggable', false);
            });
        }
    }

    function handleDragStart(e) { e.dataTransfer.setData('sourceId', this.getAttribute('data-id')); this.style.opacity = '0.4'; }
    function handleDragOver(e) { e.preventDefault(); this.classList.add('drag-over'); }
    function handleDragLeave() { this.classList.remove('drag-over'); }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        this.style.opacity = '1';
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');
        if (sourceId && targetId && sourceId !== targetId) {
            const formData = new FormData();
            formData.append('swap_seats', true);
            formData.append('source_id', sourceId);
            formData.append('target_id', targetId);
            fetch('sacramento.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
        }
    }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box, .port-item').forEach(s => {
            s.classList.toggle('dimmed', val && !s.getAttribute('data-hostname').includes(val));
        });
    }

    function handleSeatClick(event, id, label, host, sw, status, isPort) {
        if (isEditMode) return;
        event.stopPropagation();
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = label;
        document.getElementById('seatSwitch').value = sw;
        if (isPort) { document.getElementById('standardFields').style.display = 'none'; } 
        else {
            document.getElementById('standardFields').style.display = 'block';
            document.getElementById('seatHost').value = host;
            document.getElementById('seatStatus').value = status;
            toggleHostname();
        }
    }

    function toggleHostname() {
        const status = document.getElementById('seatStatus').value;
        const hostInput = document.getElementById('seatHost');
        hostInput.disabled = (status === 'Vacant');
        if(status === 'Vacant') hostInput.value = '';
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    window.onclick = function(e) { if (e.target == document.getElementById('modalOverlay')) closeModal(); }
</script>
</body>
</html>