<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Golden State";

// --- SWAP HANDLER ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    $conn->begin_transaction();

    try {
        // 1. Fetch current data for both seats
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

        // 2. SWAP ON FLOOR MAP
        $updateMap = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=? WHERE id=?");
        // Move Target info to Source ID
        $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
        $updateMap->execute();
        // Move Source info to Target ID
        $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
        $updateMap->execute();

        // 3. SWAP IN INVENTORY (The Fixed Sync)
        // We use a unique temporary hostname to break the duplication link
        $tempHostSuffix = "_SWAP_" . time();

        if (!empty($srcHost)) {
            // Step A: Move Source to Target's Cubicle but give it a TEMP Hostname
            $tmpName = $srcHost . $tempHostSuffix;
            $upd1 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, hostname = ? WHERE hostname = ?");
            $upd1->bind_param("sss", $tgtCubicle, $tmpName, $srcHost);
            $upd1->execute();
        }

        if (!empty($tgtHost)) {
            // Step B: Move Target to Source's Cubicle (Safe now because Source changed its hostname)
            $upd2 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ? WHERE hostname = ?");
            $upd2->bind_param("ss", $srcCubicle, $tgtHost);
            $upd2->execute();
        }

        if (!empty($srcHost)) {
            // Step C: Restore Source's original hostname
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

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: golden_state.php");
        exit();
    }
}

// Fetch Stations
$stations = []; 
$occupiedCount = 0;
$vacantCount = 0;

// Order by ID ensures we can map indices to database rows accurately
$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC");
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if ($row['status'] === 'Occupied') $occupiedCount++;
    else $vacantCount++;
}

/**
 * Renders a seat based on the visual index (1-31)
 */
function renderSeat($index, $stations) {
    // Note: This assumes IDs 1-31 in DB correspond to seats 1-31 on floor.
    // If IDs are different, we find the row where 'id' matches or use $stations[$index-1]
    $row = $stations[$index - 1] ?? null;
    
    $label = "GSW-" . str_pad($index, 4, '0', STR_PAD_LEFT);
    $id = $row['id'] ?? 0;
    $status = $row['status'] ?? 'Vacant';
    $hostname = $row['hostname'] ?? '';
    $port = $row['switch_port'] ?? '';
    $activeClass = ($status === 'Occupied') ? 'Occupied' : 'Vacant';
    
    $tooltip = "Location: $label\nStatus: $status\nHostname: " . ($hostname ?: 'Available') . "\nPort: $port";
    
    return "<div class='seat-box $activeClass' 
                 data-id='$id' 
                 data-hostname='".strtolower($hostname)."' 
                 title='$tooltip' 
                 onclick=\"handleSeatClick(event, '$id', '$label', '".addslashes($hostname)."', '".addslashes($port)."', '$status')\">
                <div class='seat-label'>$label</div>
                <div class='seat-host'>".($hostname ?: 'Available')."</div>
                <div class='seat-port'>$port</div>
            </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Golden State Layout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --nav-green: #15803d; 
            --primary: #22c55e;
            --bg: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --occupied-bg: #f0fdf4;
            --occupied-border: #86efac;
            --vacant-hover-border: #22c55e;
            --primary-orange: #ff6b00;
            --seat-w: 100px;
            --seat-h: 70px;
            --gap: 10px;
        }

body, html { 
    margin: 0; 
    padding: 0; 
    font-family: 'Plus Jakarta Sans', sans-serif; 
    background: var(--bg); 
    min-height: 100vh; /* Changed from height to min-height */
    overflow-y: auto;  /* Allow vertical scroll */
    overflow-x: auto;  /* Allow horizontal scroll */
}
        .navbar { background: var(--nav-green); padding: 0 2rem; display: flex; align-items: center; height: 60px; color: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); position: relative; z-index: 100; }
        .nav-back-btn { color: white; text-decoration: none; margin-right: 15px; font-size: 1.2rem; }

        .edit-sidebar { position: fixed; right: 20px; top: 80px; background: white; padding: 15px; border-radius: 15px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); z-index: 100; text-align: center; width: 140px; }
        .swap-desc { font-size: 0.65rem; color: #94a3b8; margin-top: 8px; line-height: 1.2; font-weight: 500; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1100px; margin-bottom: 15px; margin-top: 10px; }
        .search-container { position: relative; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        #searchInput { width: 300px; padding: 10px 15px 10px 40px; border-radius: 12px; border: 1px solid var(--border); outline: none; background: white; font-family: inherit; }

.main-container { 
    padding: 40px; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    min-height: calc(100vh - 60px); 
    width: max-content; /* Ensures container expands to fit the floor plan */
    min-width: 100%;    /* But stays at least full screen width */
    justify-content: flex-start; /* Changed from center to prevent clipping at the top */
}        

.floor-plan-wrapper { 
    background: white; 
    border-radius: 25px; 
    padding: 30px; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
    display: flex;
    align-items: flex-start;
    flex-shrink: 0; /* Prevents the map from squishing on small screens */
    margin-bottom: 20px;
}
        
        .column { display: flex; flex-direction: column; gap: var(--gap); }
        /* Style for the back-to-back columns with no walkway between them */
        .double-column-group { display: flex; gap: 0; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; }

        .seat-box { background: white; border: 1.5px solid var(--border); border-radius: 10px; padding: 5px; height: var(--seat-h); width: var(--seat-w); display: flex; flex-direction: column; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; position: relative; box-sizing: border-box; }
        .seat-box.Occupied { background: var(--occupied-bg); border-color: var(--occupied-border); }
        .seat-box.Vacant:hover { border-color: var(--vacant-hover-border); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15); }

        .aircon-unit { background: #e2e8f0; border: 1.5px solid #cbd5e1; border-radius: 10px; height: var(--seat-h); width: var(--seat-w); display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.65rem; font-weight: 800; cursor: default; }

        .seat-label { font-size: 0.55rem; font-weight: 800; color: var(--text-muted); }
        .seat-host { font-size: 0.7rem; color: var(--text-dark); font-weight: 700; margin: 1px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .seat-port { font-size: 0.5rem; color: #94a3b8; font-weight: 600; }

        /* Effects */
        .edit-mode-active .seat-box { cursor: grab !important; border: 2px dashed var(--primary-orange) !important; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        .drag-over { background: #fff7ed !important; border: 2px solid var(--primary-orange) !important; }
        .dimmed { opacity: 0.05; filter: grayscale(1); pointer-events: none; }

        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #e2e8f0; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-orange); }
        input:checked + .slider:before { transform: translateX(20px); }

        .status-bar { margin-top: 15px; display: flex; gap: 25px; background: white; padding: 10px 30px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); font-size: 0.8rem; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1000; }
        .modal-content { background: white; width: 340px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        
        .walkway { width: 30px; }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-arrow-left"></i></a>
    <div style="font-weight: 800; letter-spacing: -0.5px;">OJTBox | Golden State Live Map</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.8rem; margin-bottom: 5px;">Swap Mode</div>
    <label class="switch">
        <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
        <span class="slider"></span>
    </label>
    <div id="statusLabel" style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); margin-top: 2px;">OFF</div>
    <div class="swap-desc">Drag one cubicle onto another to swap hosts.</div>
</div>

<div class="main-container">
    <div class="header-flex">
        <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin: 0;">Golden State Floor Plan</h1>
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="floor-plan-wrapper">
        <div class="column">
            <div style="height: var(--seat-h);"></div> <?php for($i=7; $i>=1; $i--) echo renderSeat($i, $stations); ?>
        </div>

        <div class="walkway"></div>

        <div class="double-column-group">
            <div class="column">
                <?php for($i=15; $i>=8; $i--) echo renderSeat($i, $stations); ?>
            </div>
            <div class="column">
                <?php for($i=23; $i>=16; $i--) echo renderSeat($i, $stations); ?>
            </div>
        </div>

        <div class="walkway"></div>

        <div class="column">
            <div class="aircon-unit"><i class="fa-solid fa-snowflake"></i>&nbsp; AIRCON</div>
            <?php for($i=24; $i<=31; $i++) echo renderSeat($i, $stations); ?>
        </div>
    </div>

    <div class="status-bar">
        <div style="display:flex; align-items:center; gap:8px;"><div style="width:10px; height:10px; border-radius:50%; background:var(--primary);"></div> Occupied: <b><?php echo $occupiedCount; ?></b></div>
        <div style="display:flex; align-items:center; gap:8px;"><div style="width:10px; height:10px; border-radius:50%; background:#cbd5e1;"></div> Vacant: <b><?php echo $vacantCount; ?></b></div>
        <div style="font-weight: 800; border-left: 2px solid var(--border); padding-left: 20px;">TOTAL: 31</div>
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

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        const body = document.getElementById('body');
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box');

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
            seats.forEach(seat => seat.setAttribute('draggable', false));
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
            fetch('golden_state.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
        }
    }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box').forEach(s => {
            s.classList.toggle('dimmed', val && !s.getAttribute('data-hostname').includes(val));
        });
    }

    function handleSeatClick(event, id, label, host, sw, status) {
        if (isEditMode) return;
        event.stopPropagation();
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = label;
        document.getElementById('seatSwitch').value = sw;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatStatus').value = status;
        toggleHostname();
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