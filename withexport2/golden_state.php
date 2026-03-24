<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "GOLDEN STATE DEPARTMENT";
$back_link = "prod_map.php";

$department_name = "Golden State";

// --- SWAP HANDLER ---
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

$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC");
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if ($row['status'] === 'Occupied') $occupiedCount++;
    else $vacantCount++;
}

function renderSeat($index, $stations) {
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
            --primary: #22c55e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --occupied-bg: #f0fdf4;
            --occupied-border: #86efac;
            --vacant-hover-border: #22c55e;
            --primary-orange: #ff6b00;
            /* Adjusted sizes to fit dashboard without scrolling */
            --seat-w: 85px;
            --seat-h: 58px;
            --gap: 8px;
            --table-color: #cbd5e1;
            --table-shadow: #94a3b8;
        }

        [data-theme='dark'] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --occupied-bg: #064e3b;
            --occupied-border: #065f46;
            --table-color: #334155;
            --table-shadow: #0f172a;
        }

        body, html { 
            margin: 0; 
            padding: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            height: 100vh;
            overflow: hidden; /* Prevents body scroll */
            transition: background 0.3s ease;
        }

        .edit-sidebar { position: fixed; right: 20px; top: 80px; background: var(--card-bg); color: var(--text-dark); padding: 15px; border-radius: 15px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); z-index: 100; text-align: center; width: 140px; }
        .swap-desc { font-size: 0.65rem; color: var(--text-muted); margin-top: 8px; line-height: 1.2; font-weight: 500; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1000px; margin-bottom: 10px; }
        #searchInput { width: 250px; padding: 8px 15px 8px 40px; border-radius: 12px; border: 1px solid var(--border); outline: none; background: var(--card-bg); color: var(--text-dark); font-family: inherit; }

        .main-container { 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            height: calc(100vh - 80px); /* Fits inside viewport minus header space */
            width: 100%; 
            justify-content: center; /* Centers layout vertically */
            box-sizing: border-box;
        }         
        
        .floor-plan-wrapper { 
            background: var(--card-bg); 
            border-radius: 20px; 
            padding: 25px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
            border: 1px solid var(--border); 
            display: flex; 
            align-items: flex-start; 
            flex-shrink: 1; /* Allows shrinking to fit screen */
            transform: scale(0.95); /* Slight scale down to ensure fit on smaller monitors */
        }

        .table-surface {
            background: var(--table-color);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 6px 0 0 var(--table-shadow);
            display: flex;
            gap: 25px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .column { display: flex; flex-direction: column; gap: var(--gap); }
        .double-column-group { display: flex; gap: 10px; background: rgba(0,0,0,0.03); padding: 8px; border-radius: 12px; }

        .seat-box { 
            background: var(--card-bg); 
            border: 1.5px solid var(--border); 
            border-radius: 6px; 
            padding: 4px; 
            height: var(--seat-h); 
            width: var(--seat-w); 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            position: relative; 
            box-sizing: border-box; 
            color: var(--text-dark);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .seat-box.Occupied { background: var(--occupied-bg); border-color: var(--occupied-border); }
        
        .seat-box:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            z-index: 10;
            border-color: var(--primary-orange);
        }

        .aircon-unit { background: #e2e8f0; border: 1.5px solid var(--border); border-radius: 8px; height: var(--seat-h); width: var(--seat-w); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.6rem; font-weight: 800; cursor: default; }

        .seat-label { font-size: 0.5rem; font-weight: 800; color: var(--text-muted); }
        .seat-host { font-size: 0.65rem; color: var(--text-dark); font-weight: 700; margin: 1px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .seat-port { font-size: 0.45rem; color: var(--text-muted); font-weight: 600; }

        .edit-mode-active .seat-box { cursor: grab !important; border: 2px dashed var(--primary-orange) !important; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        .drag-over { background: rgba(255, 107, 0, 0.1) !important; border: 2px solid var(--primary-orange) !important; transform: scale(1.05); }
        .dimmed { opacity: 0.05; filter: grayscale(1); pointer-events: none; }

        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #ccc; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-orange); }
        input:checked + .slider:before { transform: translateX(20px); }

        .status-bar { margin-top: 15px; display: flex; gap: 20px; background: var(--card-bg); color: var(--text-dark); padding: 8px 25px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); font-size: 0.75rem; border: 1px solid var(--border); }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1000; }
        .modal-content { background: var(--card-bg); color: var(--text-dark); width: 340px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid var(--border); }
        .walkway { width: 10px; }
    </style>
</head>
<body id="body">

<?php include 'header.php'; ?>

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
        <h1 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark); margin: 0;">Golden State Floor Plan</h1>
        <div class="search-container" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="floor-plan-wrapper">
        <div class="table-surface">
            <div class="column">
                <div style="height: var(--seat-h);"></div> <?php for($i=7; $i>=1; $i--) echo renderSeat($i, $stations); ?>
            </div>
            
            <div class="double-column-group">
                <div class="column"><?php for($i=15; $i>=8; $i--) echo renderSeat($i, $stations); ?></div>
                <div class="walkway"></div>
                <div class="column"><?php for($i=23; $i>=16; $i--) echo renderSeat($i, $stations); ?></div>
            </div>
            
            <div class="column">
                <div class="aircon-unit"><i class="fa-solid fa-snowflake"></i>&nbsp; AIRCON</div>
                <?php for($i=24; $i<=31; $i++) echo renderSeat($i, $stations); ?>
            </div>
        </div>
    </div>

    <div class="status-bar">
        <div style="display:flex; align-items:center; gap:8px;"><div style="width:8px; height:8px; border-radius:50%; background:var(--primary);"></div> Occupied: <b><?php echo $occupiedCount; ?></b></div>
        <div style="display:flex; align-items:center; gap:8px;"><div style="width:8px; height:8px; border-radius:50%; background:#cbd5e1;"></div> Vacant: <b><?php echo $vacantCount; ?></b></div>
        <div style="font-weight: 800; border-left: 2px solid var(--border); padding-left: 20px;">TOTAL: 31</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.5rem; font-weight:800;">Station Details</h2>
        <form>
            <input type="hidden" id="seatId">
            <label style="font-size:0.7rem; font-weight:700; display:block; margin-bottom:5px;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:var(--bg); color:var(--text-dark); box-sizing: border-box;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">PORT</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <input type="text" id="seatSwitch" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:var(--bg); color: var(--text-muted); box-sizing: border-box;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">STATUS</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <select id="seatStatus" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:var(--bg); pointer-events: none; color: var(--text-muted); box-sizing: border-box;">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">HOSTNAME</label>
                    <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                </div>
                <input type="text" id="seatHost" readonly style="width:100%; padding:0.7rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border); background:var(--bg); color: var(--text-muted); box-sizing: border-box;">
            </div>

            <div style="padding-top: 10px;">
                <button type="button" onclick="closeModal()" style="width: 100%; padding: 0.8rem; background: var(--primary); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">CLOSE</button>
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