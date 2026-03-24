<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "DALLAS DEPARTMENT";
$back_link = "prod_map.php";

$department_name = "Dallas"; 
$total_seats = 35; 

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
        $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
        $updateMap->execute();
        $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
        $updateMap->execute();

        // 3. SWAP IN INVENTORY (Physical Port & Cubicle Sync)
        $tempHostSuffix = "_SWAP_" . time();

        if (!empty($srcHost)) {
            $stmtP1 = $conn->prepare("SELECT switch_port FROM production_floor_map WHERE cubicle_no = ?");
            $stmtP1->bind_param("s", $tgtCubicle);
            $stmtP1->execute();
            $tgtPort = $stmtP1->get_result()->fetch_assoc()['switch_port'] ?? '';

            $tmpName = $srcHost . $tempHostSuffix;
            $upd1 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, hostname = ?, switch_port = ? WHERE hostname = ?");
            $upd1->bind_param("ssss", $tgtCubicle, $tmpName, $tgtPort, $srcHost);
            $upd1->execute();
        }

        if (!empty($tgtHost)) {
            $stmtP2 = $conn->prepare("SELECT switch_port FROM production_floor_map WHERE cubicle_no = ?");
            $stmtP2->bind_param("s", $srcCubicle);
            $stmtP2->execute();
            $srcPort = $stmtP2->get_result()->fetch_assoc()['switch_port'] ?? '';

            $upd2 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, switch_port = ? WHERE hostname = ?");
            $upd2->bind_param("sss", $srcCubicle, $srcPort, $tgtHost);
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
    $switch_port = $_POST['switch_port'] ?? ''; 
    $status = $_POST['status']; 

    if($status === 'Vacant') { $hostname = ''; }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=?, department=? WHERE id=?");
    $stmt->bind_param("ssssi", $hostname, $status, $switch_port, $department_name, $id);
    
    if($stmt->execute()) {
        header("Location: dallas.php");
        exit();
    }
}

// --- FETCH STATIONS ---
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

        :root {
            --primary: #3b82f6; 
            --primary-dark: #2563eb;
            --primary-light: #eff6ff;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            --occupied-text: #15803d;
            --occupied-border: #bbf7d0;
            --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        [data-theme='dark'] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --occupied-bg: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            --occupied-text: #34d399;
            --occupied-border: #065f46;
        }

        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; transition: background 0.3s ease; }
        
        .container { height: calc(100vh - 72px); padding: 1.5rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1400px; margin: 0 auto; }

        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-shrink: 0; }
        .header-row h1 { font-weight: 800; font-size: 1.6rem; letter-spacing: -0.03em; margin: 0; background: linear-gradient(to right, var(--text-dark), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        #searchInput { width: 300px; padding: 0.6rem 1rem 0.6rem 2.5rem; border-radius: 12px; border: 1px solid var(--border); background: var(--card-bg); color: var(--text-dark); font-size: 0.85rem; transition: all 0.3s; outline: none; box-shadow: var(--shadow-soft); }

        .map-grid-container { background: var(--card-bg); padding: 1.5rem; border-radius: 24px; border: 1px solid var(--border); flex-grow: 1; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-soft); overflow: hidden; margin-bottom: 1rem; }

        /* Modified Grid for better spacing between row pairs */
        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(5, 1fr); 
            /* Added 15px gap between rows 1-2, 3-4, etc. and larger 30px for existing walkways */
            grid-template-rows: 1fr 2px 1fr 30px 1fr 2px 1fr 30px 1fr 2px 1fr 30px 1fr; 
            gap: 15px; 
            width: 100%; 
            height: 100%; 
            max-width: 1200px;
        }

        .walkway { grid-column: span 5; background: transparent; pointer-events: none; }

        .seat-box { border-radius: 12px; text-align: center; border: 1px solid transparent; background: var(--card-bg); color: var(--text-dark); transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: var(--shadow-soft); padding: 8px; height: 100%; min-height: 0; }
        .seat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--primary); }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: var(--card-bg); border: 1px solid var(--border); }

        .edit-sidebar { position: fixed; right: 20px; top: 85px; background: var(--card-bg); color: var(--text-dark); padding: 15px; border-radius: 18px; box-shadow: var(--shadow-hover); border: 1px solid var(--border); z-index: 100; display: flex; flex-direction: column; gap: 8px; }
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: var(--primary-light) !important; transform: scale(1.02) !important; border: 2px solid var(--primary) !important; }
        
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        .status-legend { display: flex; justify-content: center; gap: 25px; background: var(--card-bg); color: var(--text-dark); padding: 0.6rem 1.5rem; border-radius: 50px; width: fit-content; margin: 0 auto; box-shadow: var(--shadow-soft); border: 1px solid var(--border); flex-shrink: 0; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.8rem; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dimmed { opacity: 0.1; filter: grayscale(1); transform: scale(0.95); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: var(--card-bg); color: var(--text-dark); width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3); border: 1px solid var(--border); }
    </style>
</head>
<body id="body">

<?php include 'header.php'; ?>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.8rem; color: var(--text-dark);">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Dallas Floor Plan</h1>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid" id="mapGrid">
            <?php 
            for($i = 0; $i < 35; $i++): 
                $row_data = $stations[$i] ?? null;
                $id = $row_data['id'] ?? ($i + 1);
                $dal_num = $i + 1;
                $cubicle_name = "DAL-" . str_pad($dal_num, 4, '0', STR_PAD_LEFT);
                $status = $row_data['status'] ?? 'Vacant';
                $hostname = $row_data['hostname'] ?? '';
                $switch_port = $row_data['switch_port'] ?? 'Not Set';
                
                $tooltip = "Cubicle: $cubicle_name\nStatus: $status\nHostname: " . ($hostname ?: 'None') . "\nPort: $switch_port";
            ?>
                <?php if ($dal_num > 1 && ($dal_num - 1) % 5 == 0 && ($dal_num - 1) % 10 != 0): ?>
                    <div style="grid-column: span 5; height: 1px; background: transparent;"></div>
                <?php endif; ?>

                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     title="<?php echo $tooltip; ?>"
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    <strong style="font-size: 0.75rem; display:block;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 600;"><?php echo $switch_port; ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; margin-top:2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 90%;">
                        <?php echo $hostname ?: 'Available'; ?>
                    </div>
                </div>

                <?php 
                if ($dal_num % 10 == 0 && $dal_num < 35) {
                    echo '<div class="walkway"></div>';
                }
                ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div class="legend-item"><div class="dot" style="background: #22c55e;"></div> Occupied: <?php echo $occupied_count; ?></div>
        <div class="legend-item"><div class="dot" style="background: #cbd5e1;"></div> Vacant: <?php echo $vacant_count; ?></div>
        <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 15px;">Total: 35</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.2rem; font-weight:800; font-size: 1.4rem;">Station Details</h2>
        
        <form>
            <input type="hidden" id="seatId">
            
            <label style="font-size:0.65rem; font-weight:700; display:block; margin-bottom:4px;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.6rem; margin-bottom:0.8rem; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text-dark); box-sizing: border-box;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label style="font-size:0.65rem; font-weight:700; color: var(--text-muted);">PORT</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <input type="text" id="seatSwitch" readonly style="width:100%; padding:0.6rem; margin-bottom:0.8rem; border-radius:8px; border:1px solid var(--border); background:var(--bg); color: var(--text-muted); box-sizing: border-box;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label style="font-size:0.65rem; font-weight:700; color: var(--text-muted);">STATUS</label>
                <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
            </div>
            <select id="seatStatus" readonly style="width:100%; padding:0.6rem; margin-bottom:0.8rem; border-radius:8px; border:1px solid var(--border); background:var(--bg); pointer-events: none; color: var(--text-muted); box-sizing: border-box;">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label style="font-size:0.65rem; font-weight:700; color: var(--text-muted);">HOSTNAME</label>
                    <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                </div>
                <input type="text" id="seatHost" readonly style="width:100%; padding:0.6rem; margin-bottom:1.2rem; border-radius:8px; border:1px solid var(--border); background:var(--bg); color: var(--text-muted); box-sizing: border-box;">
            </div>

            <div style="padding-top: 5px;">
                <button type="button" onclick="closeModal()" 
                        style="width: 100%; padding: 0.7rem; background: var(--primary); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer;">
                    CLOSE
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;

    window.onload = function() {
        const savedMode = localStorage.getItem('swapModeEnabled');
        if (savedMode === 'true') {
            document.getElementById('editToggle').checked = true;
            toggleEditMode();
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
                seat.removeEventListener('dragstart', handleDragStart);
                seat.removeEventListener('dragover', handleDragOver);
                seat.removeEventListener('dragleave', handleDragLeave);
                seat.removeEventListener('drop', handleDrop);
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

        fetch('dallas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) location.reload(); 
        });
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode) return; 
        openEdit(id, cubicle, host, sw, status);
    }

    function openEdit(id, cubicle, host, sw, status) {
        if(!id) { alert("This seat is not initialized."); return; }
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
        if(status === 'Vacant') {
            hostInput.value = '';
            hostInput.style.opacity = '0.5';
        } else {
            hostInput.style.opacity = '1';
        }
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            seats[i].classList.toggle('dimmed', !host.includes(input));
        }
    }
</script>
</body>
</html>