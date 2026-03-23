<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "TRAINING ROOM PLAN";
$back_link = "prod_map.php";

$department_name = "Training Room"; 
$total_seats = 22; 

// --- SWAP HANDLER (Synced with Inventory) ---
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

// --- UPDATE HANDLER (Metadata only) ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $switch_port = $_POST['switch_port'] ?? 'Not Set'; 
    $stmt = $conn->prepare("UPDATE production_floor_map SET switch_port=? WHERE id=?");
    $stmt->bind_param("si", $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: training_room.php");
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

// --- RENDER FUNCTION ---
function renderSeat($index, $stations) {
    $row = isset($stations[$index - 1]) ? $stations[$index - 1] : null;

    $num_display = str_pad($index, 4, '0', STR_PAD_LEFT);
    $name = $row['cubicle_no'] ?? "TRN-" . $num_display;

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
         onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $name; ?>', '<?php echo addslashes($host); ?>', '<?php echo addslashes($port); ?>', '<?php echo $status; ?>')"
         <?php endif; ?>>
        <strong style="font-size: 0.75rem;"><?php echo $name; ?></strong>
        <div style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $port; ?></div>
        <div class="seat-host-text" style="font-size: 0.7rem; font-weight: 700; margin-top:4px;"><?php echo $host ?: 'Available'; ?></div>
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
            --table-surface: #cbd5e1;
            --table-shadow: #94a3b8;
        }

        [data-theme='dark'] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --occupied-bg: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            --occupied-text: #4ade80;
            --vacant-hover: #334155;
            --table-surface: #334155;
            --table-shadow: #0f172a;
        }

        html, body { min-height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); transition: background 0.3s ease; }
        
        .container { 
            padding: 1.5rem 2rem; 
            display: flex; 
            flex-direction: column; 
            box-sizing: border-box; 
            max-width: 100%; 
            margin: 0; 
            position: relative; 
        }       
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; color: var(--text-dark); }

        #searchInput { width: 300px; padding: 0.7rem 1rem 0.7rem 2.5rem; border-radius: 12px; border: 1px solid var(--border); outline: none; background: var(--card-bg); color: var(--text-dark); box-shadow: var(--shadow-soft); }

        /* Modified Layout to include Table Design */
        .layout-wrapper {
            display: flex;
            gap: 40px;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            padding: 40px 20px 100px; 
            min-height: min-content;
            perspective: 1000px; /* Adds depth */
        }

        .table-container {
            background: var(--table-surface);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 15px 0 0 var(--table-shadow), var(--shadow-hover);
            display: flex;
            gap: 20px;
            transform: rotateX(5deg); /* Slight tilt for interaction feel */
            border: 1px solid rgba(255,255,255,0.1);
        }

        .column {
            display: grid;
            grid-template-rows: repeat(6, 90px); 
            gap: 12px;
            flex-shrink: 0;
        }

        .col-5 { padding-top: 51px; }
        
        .seat-box { 
            width: 130px; 
            height: 90px; 
            border-radius: 8px; 
            background: var(--card-bg); 
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            border: 1px solid var(--border); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 10px;
            box-sizing: border-box;
            color: var(--text-dark);
            position: relative;
        }
        
        .seat-box:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); border-color: var(--primary); z-index: 10; }
        .seat-box.Vacant:hover { background-color: var(--vacant-hover); }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border-color: transparent; }
        .dimmed { opacity: 0.15; filter: grayscale(1); }

        .edit-sidebar { position: fixed; right: 20px; top: 100px; background: var(--card-bg); color: var(--text-dark); padding: 1.2rem; border-radius: 18px; box-shadow: var(--shadow-soft); width: 160px; text-align: center; border: 1px solid var(--border); z-index: 100; }
        .swap-desc { font-size: 0.65rem; color: var(--text-muted); margin-top: 10px; line-height: 1.3; }
        
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #e0f2fe !important; transform: scale(1.1) rotate(2deg) !important; border: 2px solid var(--primary) !important; }

        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        .status-footer { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); background: var(--card-bg); color: var(--text-dark); padding: 8px 25px; border-radius: 50px; display: flex; gap: 20px; box-shadow: var(--shadow-soft); border: 1px solid var(--border); z-index: 1000;}

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: var(--card-bg); color: var(--text-dark); width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid var(--border); }
    </style>
</head>
<body id="body">

<?php include 'header.php'; ?>

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
        <div class="table-container">
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
    </div>

    <div class="status-footer">
        <div style="font-weight: 700;">Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-weight: 700;">Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-weight: 700;">Total: 22</div>
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