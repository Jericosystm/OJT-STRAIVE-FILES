<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "San Antonio"; 
$total_seats = 49; 

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

        $updateMap = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=? WHERE id=?");
        $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
        $updateMap->execute();
        $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
        $updateMap->execute();

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

$stations = []; 
$occupied_count = 0;
$vacant_count = 0;
$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC LIMIT ?");
$stmt->bind_param("si", $department_name, $total_seats);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if($row['status'] === 'Occupied') $occupied_count++;
    else $vacant_count++;
}
$vacant_count += ($total_seats - count($stations));

// ADDED FOR NAVIGATION: Eto ang magsasabi sa header.php (kung ginagamit nito ang variable) na bumalik sa prod_map.php
$back_link = "prod_map.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | <?php echo $department_name; ?> Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=JetBrains+Mono:wght@500&family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Default Dark Mode Variables */
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.4);
            --bg: #050505;
            --card-bg: rgba(255, 255, 255, 0.04);
            --card-hover: rgba(255, 255, 255, 0.07);
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
            --card-shadow: rgba(0, 0, 0, 0.5);
            --occupied-glow: rgba(0, 255, 136, 0.2);
        }

        /* LIGHT MODE IMPLEMENTATION - Matching Index Admin */
        [data-theme="light"] {
            --bg: #f5f5f7;
            --card-bg: #ffffff;
            --card-hover: #eeeeee;
            --border: rgba(0, 0, 0, 0.08);
            --text-main: #1d1d1f;
            --text-muted: #6e6e73;
            --card-shadow: rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(255, 102, 0, 0.1), transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(255, 102, 0, 0.05), transparent 35%);
            background-attachment: fixed;
            height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* DASHBOARD HEADER MATCHING INDEX */
        .dashboard-header {
            padding: 50px 40px 0px;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .welcome-banner p {
            color: var(--primary);
            font-size: 0.7rem;
            margin: 0 0 5px 0;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 5px;
            opacity: 0.8;
        }

        .welcome-banner h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -2px;
            color: var(--text-main);
        }

        /* CONTROLS AREA */
        .map-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 0 40px;
            max-width: 1400px;
            margin: 20px auto;
        }

        #searchInput {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 12px 20px;
            border-radius: 15px;
            outline: none;
            width: 300px;
            transition: 0.3s;
        }

        #searchInput:focus { border-color: var(--primary); }

        /* BENTO GRID MAP */
        .map-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
            padding: 20px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .seat-box {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: pointer;
            min-height: 80px;
            box-shadow: 0 4px 15px var(--card-shadow);
        }

        .seat-box:hover {
            background: var(--card-hover);
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 20px var(--card-shadow), 0 0 15px var(--primary-glow);
        }

        .seat-box strong { font-size: 0.9rem; color: var(--primary); margin-bottom: 5px; }
        .seat-box .port-label { font-family: 'JetBrains Mono'; font-size: 0.65rem; color: var(--text-muted); }
        .seat-box .host-label { font-size: 1rem; font-weight: 700; margin-top: 10px; color: var(--text-main); }

        /* STATUS STYLES */
        .Occupied { border-left: 4px solid #00ff88; }
        .Vacant { opacity: 0.8; }
        .Vacant .host-label { color: var(--text-muted); font-style: italic; font-weight: 400; }

        /* SWAP MODE STYLES */
        .edit-mode-active .seat-box {
            border: 2px dashed var(--primary) !important;
            cursor: grab;
        }
        .drag-over {
            background: var(--primary-glow) !important;
            transform: scale(1.05);
        }

        /* SIDEBAR / SWITCH */
        .swap-panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }

        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background-color: #333; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 14px; width: 14px;
            left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(20px); }

        .status-legend {
            display: flex; gap: 30px; padding: 20px 40px; max-width: 1400px; margin: 0 auto;
            color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
        }
        .walkway { grid-column: span 7; height: 30px; }
        .dimmed { opacity: 0.1; filter: blur(2px); }
        
        ::-webkit-scrollbar { width: 0; }
    </style>
</head>
<body id="body">

    <?php include 'header.php'; ?>

    <header class="dashboard-header">
        <div class="welcome-banner">
            <p>Production Map / <?php echo $department_name; ?></p>
            <h1>Floor Visualization</h1>
        </div>
        
        <div class="swap-panel">
            <span style="font-size: 0.7rem; font-weight: 800; letter-spacing: 1px; color: var(--text-main);">SWAP MODE</span>
            <label class="switch">
                <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
                <span class="slider"></span>
            </label>
            <span id="statusLabel" style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted);">OFF</span>
        </div>
    </header>

    <div class="map-controls">
        <input type="text" id="searchInput" placeholder="Search workstation..." onkeyup="searchMap()">
    </div>

    <div class="container">
        <div class="map-grid">
            <?php 
            for($i = 0; $i < 49; $i++): 
                $row = $stations[$i] ?? null;
                $db_id = $row['id'] ?? "new_" . ($i + 1); 
                $cubicle_num = $i + 1;
                $cubicle_name = $row['cubicle_no'] ?? "SA-" . str_pad($cubicle_num, 4, '0', STR_PAD_LEFT);
                $status = $row['status'] ?? 'Vacant';
                $hostname = $row['hostname'] ?? '';
                $port = $row['switch_port'] ?? '---';
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     data-id="<?php echo $db_id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     onclick="handleSeatClick(event, '<?php echo $db_id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo addslashes($hostname); ?>', '<?php echo addslashes($port); ?>', '<?php echo $status; ?>')">
                    
                    <strong><?php echo $cubicle_name; ?></strong>
                    <span class="port-label"><i class="fa-solid fa-ethernet"></i> <?php echo $port; ?></span>
                    <div class="host-label"><?php echo $hostname ?: 'VACANT'; ?></div>
                </div>
                <?php if ($cubicle_num % 14 == 0 && $cubicle_num < 49) echo '<div class="walkway"></div>'; ?>
            <?php endfor; ?>
        </div>

        <div class="status-legend">
            <div><span style="color:#00ff88">●</span> Occupied: <?php echo $occupied_count; ?></div>
            <div><span style="color:var(--text-muted)">●</span> Vacant: <?php echo $vacant_count; ?></div>
            <div>Total Assets: 49</div>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
<script>
    // --- NEW FEATURE: THEME & NAVIGATION HANDLER ---
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Sync Theme with Local Storage (Kagaya ng sa index admin)
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // 2. Ensure Back Button leads to prod_map.php
        // Hinahanap ang back button sa header (karaniwang .nav-back-btn o link na may fa-arrow-left)
        const headerBackBtn = document.querySelector('header a, .navbar a, .nav-back-btn');
        if (headerBackBtn) {
            headerBackBtn.setAttribute('href', 'prod_map.php');
        }
    });

    let isEditMode = false;

    // Initialize VanillaTilt for the "Bento" look
    VanillaTilt.init(document.querySelectorAll(".seat-box"), {
        max: 5,
        speed: 400,
        glare: true,
        "max-glare": 0.1,
    });

    window.onload = function() {
        const savedMode = localStorage.getItem('swapModeEnabled');
        if (savedMode === 'true') {
            const toggle = document.getElementById('editToggle');
            if (toggle) {
                toggle.checked = true;
                toggleEditMode();
            }
        }
    };

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        localStorage.setItem('swapModeEnabled', isEditMode);

        const body = document.getElementById('body');
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box');

        if (isEditMode) {
            body.classList.add('edit-mode-active');
            label.innerText = "ON";
            label.style.color = "var(--primary)";
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
                seat.style.opacity = '1';
                seat.classList.remove('drag-over');
            });
        }
    }

    function handleDragStart(e) {
        e.dataTransfer.setData('sourceId', this.getAttribute('data-id'));
        this.style.opacity = '0.4';
    }

    function handleDragOver(e) { e.preventDefault(); this.classList.add('drag-over'); }
    function handleDragLeave() { this.classList.remove('drag-over'); }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        this.style.opacity = '1';
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');

        if (sourceId && targetId && sourceId !== targetId) {
            performSwap(sourceId, targetId);
        }
    }

    function performSwap(src, tgt) {
        const formData = new FormData();
        formData.append('swap_seats', true);
        formData.append('source_id', src);
        formData.append('target_id', tgt);

        fetch('san_antonio.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.success) location.reload(); });
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode) return; 
        console.log("Clicked:", cubicle);
    }

    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname') || "";
            if (host.includes(input)) seats[i].classList.remove('dimmed');
            else seats[i].classList.add('dimmed');
        }
    }
</script>
</body>
</html>