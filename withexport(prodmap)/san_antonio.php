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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | <?php echo $department_name; ?> Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root { --primary: #ff6b00; --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b; --text-muted: #94a3b8; --border: #e2e8f0; --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); --occupied-text: #15803d; --occupied-border: #bbf7d0; --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05); --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        .navbar { background: #22c55e; padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 100; }
        .nav-back-btn { color: white; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
        .container { height: calc(100vh - 70px); padding: 1.5rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1600px; margin: 0 auto; overflow-y: auto; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; }
        .map-grid { display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: minmax(80px, auto); gap: 12px; width: 100%; }
        .walkway { grid-column: span 7; height: 20px; }
        
        .seat-box { 
            border-radius: 16px; 
            background: var(--card-bg); 
            transition: all 0.3s ease; 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            box-shadow: var(--shadow-soft); 
            padding: 10px;
            border: 2px solid transparent; 
        }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: white; border: 1px solid var(--border); }

        /* STATIC DASHED BORDER FOR EDIT MODE */
        .edit-mode-active .seat-box {
            cursor: grab;
            border: 2px dashed #3b82f6 !important; /* Static scattered lines */
            background-color: #f8fafc;
        }

        .drag-over {
            transform: scale(1.05);
            background-color: #eff6ff !important;
            border-style: solid !important; /* Make solid when hovering to drop */
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow-hover); z-index: 110; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #3b82f6; }
        input:checked + .slider:before { transform: translateX(26px); }
        .status-legend { display: flex; justify-content: center; gap: 30px; margin-top: 2rem; background: white; padding: 10px 30px; border-radius: 50px; width: fit-content; margin: 20px auto; box-shadow: var(--shadow-soft); }
        .dimmed { opacity: 0.2; filter: grayscale(1); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem;">OJTBox | San Antonio Dashboard</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.9rem;">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.75rem; font-weight: 700;">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>San Antonio Floor Plan</h1>
        <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()" style="padding: 10px; border-radius: 10px; border: 1px solid #ccc; width: 300px;">
    </div>

    <div class="map-grid">
        <?php 
        for($i = 0; $i < 49; $i++): 
            $row = $stations[$i] ?? null;
            $db_id = $row['id'] ?? "new_" . ($i + 1); 
            $cubicle_num = $i + 1;
            $cubicle_name = $row['cubicle_no'] ?? "SA-" . str_pad($cubicle_num, 4, '0', STR_PAD_LEFT);
            $status = $row['status'] ?? 'Vacant';
            $hostname = $row['hostname'] ?? '';
            $port = $row['switch_port'] ?? 'Not Set';
        ?>
            <div class="seat-box <?php echo $status; ?>" 
                 data-id="<?php echo $db_id; ?>"
                 data-hostname="<?php echo strtolower($hostname); ?>"
                 onclick="handleSeatClick(event, '<?php echo $db_id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo addslashes($hostname); ?>', '<?php echo addslashes($port); ?>', '<?php echo $status; ?>')">
                <strong style="font-size: 0.85rem;"><?php echo $cubicle_name; ?></strong>
                <div style="font-size: 0.65rem; color: var(--text-muted);"><?php echo $port; ?></div>
                <div style="font-size: 0.75rem; font-weight: 700;"><?php echo $hostname ?: 'Available'; ?></div>
            </div>
            <?php if ($cubicle_num % 14 == 0 && $cubicle_num < 49) echo '<div class="walkway"></div>'; ?>
        <?php endfor; ?>
    </div>

    <div class="status-legend">
        <div style="font-weight: 700;">Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-weight: 700;">Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-weight: 700;">Total: 49</div>
    </div>
</div>

<script>
    let isEditMode = false;

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
            label.style.color = "#3b82f6";
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
                seat.removeEventListener('dragstart', handleDragStart);
                seat.removeEventListener('dragover', handleDragOver);
                seat.removeEventListener('dragleave', handleDragLeave);
                seat.removeEventListener('drop', handleDrop);
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
        alert("Cubicle: " + cubicle + "\nHostname: " + (host || 'None'));
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