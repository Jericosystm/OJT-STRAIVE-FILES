<?php
// --- CRITICAL: PRESERVING ORIGINAL PHP LOGIC ---
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Phoenix"; 
$total_seats = 35; 

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

        // 1. SWAP ON FLOOR MAP
        $updateMap = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=? WHERE id=?");
        $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
        $updateMap->execute();
        $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
        $updateMap->execute();

        // 2. SWAP IN INVENTORY
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
    $hostname = trim($_POST['hostname']);
    $switch_port = $_POST['switch_port'] ?? 'Not Set'; 
    $status = $_POST['status']; 

    if($status === 'Vacant') { $hostname = ''; }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: phoenix.php");
        exit();
    }
}

// --- DATA FETCHING ---
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
$back_link = "prod_map.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | <?php echo $department_name; ?> Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-orange: #FF6600;
            --bg-color: #0F172A;
            --card-bg: rgba(30, 41, 59, 0.45); /* Glass Effect */
            --text-main: #F8FAFC;
            --text-dim: #94A3B8;
            --border-color: rgba(255, 255, 255, 0.08);
            --occupied-color: #22C55E;
            --vacant-color: #64748B;
        }

        [data-theme="light"] {
            --bg-color: #F8FAFC;
            --card-bg: rgba(255, 255, 255, 0.8);
            --text-main: #1E293B;
            --text-dim: #64748B;
            --border-color: #E2E8F0;
        }

        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body { 
            margin: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-color); 
            color: var(--text-main); 
            min-height: 100vh;
        }

        .container { 
            padding: 2.5rem; 
            max-width: 1600px; 
            margin: 0 auto; 
        }

        /* Search & Header Styling */
        .header-row h1 { font-weight: 800; font-size: 2.2rem; letter-spacing: -0.04em; margin: 0; }
        
        #searchInput { 
            width: 350px; 
            padding: 0.8rem 1rem 0.8rem 3rem; 
            border-radius: 14px; 
            border: 1px solid var(--border-color); 
            background: var(--card-bg); 
            backdrop-filter: blur(10px);
            color: var(--text-main);
            outline: none; 
            transition: 0.3s;
        }
        #searchInput:focus { border-color: var(--primary-orange); box-shadow: 0 0 15px rgba(255,102,0,0.2); }

        /* Grid Styling - Ginaya ang San Antonio Spacing */
        .map-grid-container { 
            background: var(--card-bg); 
            backdrop-filter: blur(20px);
            padding: 3rem; 
            border-radius: 32px; 
            border: 1px solid var(--border-color); 
            margin-top: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            gap: 20px; 
        }

        /* Seat Box - Glass Effect + Bottom Glow */
        .seat-box { 
            border-radius: 20px; 
            padding: 22px 15px;
            background: rgba(15, 23, 42, 0.3); 
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            position: relative;
        }

        .seat-box:hover { 
            transform: translateY(-5px); 
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary-orange);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        /* Bottom Border Accent like San Antonio */
        .Occupied { border-bottom: 4px solid var(--occupied-color) !important; }
        .Vacant { border-bottom: 4px solid var(--vacant-color) !important; }

        .seat-box strong { color: var(--primary-orange); font-size: 0.9rem; letter-spacing: 0.05em; }
        .port-label { font-size: 0.65rem; color: var(--text-dim); margin: 6px 0; font-weight: 600; }
        .host-label { font-size: 0.85rem; font-weight: 700; color: var(--text-main); }

        /* Status Legend Bottom Pill */
        .status-legend { 
            display: flex; 
            justify-content: center; 
            gap: 40px; 
            margin-top: 3rem; 
            background: var(--card-bg); 
            backdrop-filter: blur(10px);
            padding: 1.2rem 3rem; 
            border-radius: 100px; 
            border: 1px solid var(--border-color);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        /* Floating Sidebar/Toggle UI */
        .edit-sidebar { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            background: var(--card-bg); 
            backdrop-filter: blur(20px);
            padding: 15px 25px; 
            border-radius: 20px; 
            border: 1px solid var(--primary-orange); 
            z-index: 100;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #334155; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-orange); }
        input:checked + .slider:before { transform: translateX(22px); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1000; }
        .modal-content { background: #1E293B; width: 400px; padding: 2.5rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid var(--border-color); }
        
        .dimmed { opacity: 0.1; transform: scale(0.9); }
        .drag-over { border: 2px dashed var(--primary-orange) !important; background: rgba(255,102,0,0.1) !important; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.75rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase;">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 12px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.8rem; font-weight: 800; color: var(--text-dim);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row" style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="color: var(--primary-orange); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 8px;">Department Layout</div>
            <h1>Phoenix Floor Plan</h1>
        </div>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid" id="mapGrid">
            <?php 
            for($i = 0; $i < 35; $i++): 
                $row_data = $stations[$i] ?? null;
                $id = $row_data['id'] ?? ($i + 1);
                $phx_num = $i + 1;
                $cubicle_name = "PHX-" . str_pad($phx_num, 4, '0', STR_PAD_LEFT);
                $status = $row_data['status'] ?? 'Vacant';
                $hostname = $row_data['hostname'] ?? '';
                $switch_port = $row_data['switch_port'] ?? 'Not Set';
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    
                    <strong><?php echo $cubicle_name; ?></strong>
                    <div class="port-label"><?php echo $switch_port; ?></div>
                    <div class="host-label"><?php echo $hostname ?: '<span style="opacity:0.3; font-weight:400;">Available</span>'; ?></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:12px; height:12px; border-radius:50%; background: #22c55e; box-shadow: 0 0 10px #22c55e;"></div> 
            <span>Occupied: <strong><?php echo $occupied_count; ?></strong></span>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:12px; height:12px; border-radius:50%; background: #64748B;"></div> 
            <span>Vacant: <strong><?php echo $vacant_count; ?></strong></span>
        </div>
        <div style="border-left: 1px solid var(--border-color); padding-left: 20px;">Total: <strong>35</strong></div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin:0 0 1.5rem; font-weight:800;">Station Info</h2>
        <form>
            <input type="hidden" id="seatId">
            <label style="font-size:0.7rem; font-weight:700; color:var(--primary-orange); display:block; margin-bottom:5px;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border-color); background:rgba(0,0,0,0.2); color:white;">
            
            <label style="font-size:0.7rem; font-weight:700; color:var(--primary-orange); display:block; margin-bottom:5px;">HOSTNAME</label>
            <input type="text" id="seatHost" readonly style="width:100%; padding:0.8rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border-color); background:rgba(0,0,0,0.2); color:white;">

            <button type="button" onclick="closeModal()" style="width: 100%; padding: 0.8rem; background: var(--primary-orange); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">CLOSE</button>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box');

        if (isEditMode) {
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
            label.innerText = "OFF";
            label.style.color = "var(--text-dim)";
            seats.forEach(seat => seat.setAttribute('draggable', false));
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
        if (sourceId !== targetId) performSwap(sourceId, targetId);
    }

    function performSwap(src, tgt) {
        const formData = new FormData();
        formData.append('swap_seats', true);
        formData.append('source_id', src);
        formData.append('target_id', tgt);

        fetch('phoenix.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if(data.success) location.reload(); });
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode) return; 
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host || 'Available';
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            seats[i].classList.toggle('dimmed', input !== '' && !host.includes(input));
        }
    }
</script>
</body>
</html>