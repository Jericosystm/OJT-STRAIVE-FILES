<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Training Room"; 
$total_seats = 22; 

// --- SWAP HANDLER ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    $stmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $sourceId);
    $stmt->execute();
    $sourceData = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $targetData = $stmt->get_result()->fetch_assoc();

    $updateSource = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateSource->bind_param("sssi", $targetData['hostname'], $targetData['status'], $targetData['switch_port'], $sourceId);
    
    $updateTarget = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateTarget->bind_param("sssi", $sourceData['hostname'], $sourceData['status'], $sourceData['switch_port'], $targetId);

    if($updateSource->execute() && $updateTarget->execute()) {
        echo json_encode(['success' => true]);
        exit();
    }
}


// --- UPDATE HANDLER ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];
    $switch_port = $_POST['switch_port'] ?? 'Not Set'; 
    $status = $_POST['status']; 

    if($status === 'Vacant') {
        $hostname = '';
    }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: training_room.php");
        exit();
    }
}

// --- DATA FETCHING (Corrected) ---
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

// --- RENDER FUNCTION (Corrected) ---
function renderSeat($index, $stations) {
    // Look up the station by its position in the array (0-21)
    $row = isset($stations[$index - 1]) ? $stations[$index - 1] : null;

    $num_display = str_pad($index, 4, '0', STR_PAD_LEFT);
    $name = "TRN-" . $num_display;

    $id = $row['id'] ?? null; 
    $status = $row['status'] ?? 'Vacant';
    $host = $row['hostname'] ?? '';
    $port = $row['switch_port'] ?? 'Not Set'; 
    
    $tooltip = "Cubicle: $name\nStatus: $status\nHost: " . ($host ?: 'N/A') . "\nPort: $port";
    ?>
    <div class="seat-box <?php echo $status; ?>" 
         id="seat-<?php echo $id; ?>"
         data-id="<?php echo $id; ?>"
         data-hostname="<?php echo strtolower($host); ?>"
         title="<?php echo $tooltip; ?>"
         <?php if($id): ?>
         onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $name; ?>', '<?php echo $host; ?>', '<?php echo $port; ?>', '<?php echo $status; ?>')"
         <?php endif; ?>>
        <strong style="font-size: 0.75rem;"><?php echo $name; ?></strong>
        <div style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $port; ?></div>
        <div style="font-size: 0.7rem; font-weight: 700; margin-top:4px;"><?php echo $host ?: 'Available'; ?></div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Training Room</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #2196f3;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --occupied-text: #15803d;
            --vacant-hover: #e2e8f0;
            --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        
        .navbar { background: #2196f3; padding: 0 2.5rem; display: flex; align-items: center; height: 60px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: white; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }

.container { 
        height: calc(100vh - 60px); 
        padding: 1rem 2rem; 
        display: flex; 
        flex-direction: column; 
        box-sizing: border-box; 
        max-width: 100%; /* Allow container to use full width for scrolling */
        margin: 0; 
        position: relative; 
    }        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; }

        #searchInput { width: 300px; padding: 0.7rem 1rem 0.7rem 2.5rem; border-radius: 12px; border: 1px solid var(--border); outline: none; box-shadow: var(--shadow-soft); }

        .layout-wrapper {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
        }

        .column {
            display: grid;
            grid-template-rows: repeat(6, 90px); 
            gap: 12px;
        }

        .col-5 { padding-top: 51px; margin-right: 80px}
        
        .seat-box { 
            width: 130px; 
            height: 90px; 
            border-radius: 12px; 
            background: var(--card-bg); 
            transition: all 0.3s ease; 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            border: 1px solid var(--border); 
            box-shadow: var(--shadow-soft);
            padding: 10px;
            box-sizing: border-box;
        }
        
        .seat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--primary); }
        .seat-box.Vacant:hover { background-color: var(--vacant-hover); }
        .seat-box.Occupied:hover { filter: brightness(0.95); }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border-color: #bbf7d0; }
        .dimmed { opacity: 0.15; filter: grayscale(1); }

        .edit-sidebar { position: fixed; right: 20px; top: 80px; background: white; padding: 1.2rem; border-radius: 18px; box-shadow: var(--shadow-soft); width: 160px; text-align: center; border: 1px solid var(--border); z-index: 100; }
        .swap-desc { font-size: 0.65rem; color: var(--text-muted); margin-top: 10px; line-height: 1.3; }
        
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #e0f2fe !important; transform: scale(1.05) !important; border: 2px solid var(--primary) !important; }

        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        .status-footer { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); background: white; padding: 8px 25px; border-radius: 50px; display: flex; gap: 20px; box-shadow: var(--shadow-soft); border: 1px solid var(--border); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="color:white; font-weight: 900; font-size: 1.4rem;">OJTBox | Training Room Plan</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.85rem; margin-bottom: 10px;">Swap Mode</div>
    <label class="switch">
        <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
        <span class="slider"></span>
    </label>
    <div id="toggleText" style="font-size: 0.7rem; font-weight: 700; margin-top: 5px;">OFF</div>
    <div class="swap-desc">Drag one cubicle onto another to swap hostnames and status.</div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Training Room</h1>
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="layout-wrapper">
        <div class="column col-5">
            <?php for($i=1; $i<=5; $i++) renderSeat($i, $stations); ?>
        </div>

        <div class="column">
            <?php for($i=6; $i<=11; $i++) renderSeat($i, $stations); ?>
        </div>

        <div class="column">
            <?php 
            $col3 = [17, 16, 15, 14, 13, 12];
            foreach($col3 as $i) renderSeat($i, $stations); 
            ?>
        </div>

        <div class="column">
            <?php for($i=18; $i<=22; $i++) renderSeat($i, $stations); ?>
        </div>
    </div>

    <div class="status-footer">
        <div style="font-weight: 700;">Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-weight: 700;">Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-weight: 700;">Total: 22</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.5rem; font-weight:800;">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label style="font-size:0.7rem; font-weight:700;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc;">
            
            <label style="font-size:0.7rem; font-weight:700;">PORT</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">

            <label style="font-size:0.7rem; font-weight:700;">STATUS</label>
            <select name="status" id="seatStatus" onchange="toggleHostname()" style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <label style="font-size:0.7rem; font-weight:700;">HOSTNAME</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.7rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="update_seat" style="flex:2; padding: 0.8rem; background: var(--primary); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">SAVE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding: 0.8rem; background:#eee; border:none; border-radius:10px; cursor:pointer;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        document.getElementById('toggleText').innerText = isEditMode ? 'ON' : 'OFF';
        const body = document.getElementById('body');
        const seats = document.querySelectorAll('.seat-box');

        if (isEditMode) {
            body.classList.add('edit-mode-active');
            seats.forEach(seat => {
                seat.setAttribute('draggable', true);
                seat.addEventListener('dragstart', handleDragStart);
                seat.addEventListener('dragover', handleDragOver);
                seat.addEventListener('dragleave', handleDragLeave);
                seat.addEventListener('drop', handleDrop);
            });
        } else {
            body.classList.remove('edit-mode-active');
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
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');

        if (sourceId && targetId && sourceId !== targetId) {
            const formData = new FormData();
            formData.append('swap_seats', true);
            formData.append('source_id', sourceId);
            formData.append('target_id', targetId);

            fetch('training_room.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(data.success) location.reload(); });
        }
    }

    function handleSeatClick(e, id, cubicle, host, sw, status) {
        if (isEditMode) return;
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatSwitch').value = sw === 'Not Set' ? '' : sw;
        document.getElementById('seatStatus').value = status;
        toggleHostname();
    }

    function toggleHostname() {
        const status = document.getElementById('seatStatus').value;
        const hostInput = document.getElementById('seatHost');
        hostInput.disabled = (status === 'Vacant');
        hostInput.style.opacity = (status === 'Vacant') ? '0.5' : '1';
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box').forEach(s => {
            s.classList.toggle('dimmed', val && !s.getAttribute('data-hostname').includes(val));
        });
    }
</script>
</body>
</html>