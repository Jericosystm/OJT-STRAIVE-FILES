<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "SAN ANTONIO DEPARTMENT";
$back_link = "prod_map.php"; 

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
        
        :root { 
            --primary: #ff6b00; 
            --bg: #f1f5f9; 
            --card-bg: #ffffff; 
            --text-dark: #1e293b; 
            --text-muted: #94a3b8; 
            --border: #e2e8f0; 
            --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
            --occupied-text: #15803d; 
            --occupied-border: #bbf7d0; 
            --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05); 
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1); 
            --table-surface: #e2e8f0;
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
            --table-surface: #334155;
        }
        
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; transition: background 0.3s, color 0.3s; }
        
        .container { height: calc(100vh - 72px); padding: 1rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1400px; margin: 0 auto; justify-content: space-between; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-shrink: 0; }
        .header-row h1 { font-weight: 800; font-size: 1.5rem; margin: 0; color: var(--text-dark); }
        
        /* Table Container for Cubicles */
        .table-container {
            position: relative;
            background: var(--table-surface);
            padding: 20px;
            border-radius: 16px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05), 0 10px 15px -3px rgba(0,0,0,0.1);
            border-bottom: 4px solid rgba(0,0,0,0.1);
            flex-grow: 1;
            display: flex;
            align-items: center;
            overflow: hidden;
            perspective: 1000px;
        }

        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            grid-auto-rows: 1fr; 
            gap: 12px; 
            width: 100%; 
            height: 100%;
            transform: rotateX(2deg);
        }
        
        .walkway { grid-column: span 7; height: 15px; }
        
        .seat-box { 
            border-radius: 8px; 
            background: var(--card-bg); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            padding: 5px;
            border: 1px solid var(--border); 
            color: var(--text-dark);
            min-height: 0;
            position: relative;
        }

        .seat-box:hover {
            border-color: var(--primary) !important;
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: var(--card-bg); border: 1px solid var(--border); }
        .dimmed { opacity: 0.15; filter: grayscale(1); }

        .edit-mode-active .seat-box {
            cursor: grab;
            border: 2px dashed #3b82f6 !important;
            background-color: var(--bg);
        }

        .drag-over {
            transform: scale(1.02);
            background-color: #eff6ff !important;
            border-style: solid !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
        }

        /* MODAL STYLES */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: var(--card-bg); width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid var(--border); color: var(--text-dark); }

        .edit-sidebar { position: fixed; right: 20px; top: 85px; background: var(--card-bg); padding: 15px; border-radius: 15px; box-shadow: var(--shadow-hover); z-index: 110; border: 1px solid var(--border); color: var(--text-dark); }
        .switch { position: relative; display: inline-block; width: 44px; height: 20px; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #3b82f6; }
        input:checked + .slider:before { transform: translateX(24px); }
        
        .status-legend { display: flex; justify-content: center; gap: 30px; background: var(--card-bg); padding: 8px 25px; border-radius: 50px; width: fit-content; margin: 10px auto 0; box-shadow: var(--shadow-soft); border: 1px solid var(--border); color: var(--text-dark); flex-shrink: 0; }
    </style>
</head>
<body id="body">

<?php include 'header.php'; ?>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.8rem;">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.7rem; font-weight: 700;">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>San Antonio Floor Plan</h1>
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()" style="padding: 8px 12px 8px 35px; border-radius: 8px; border: 1px solid var(--border); width: 250px; background: var(--card-bg); color: var(--text-dark); font-size: 0.85rem;">
        </div>
    </div>

    <div class="table-container">
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
                    <strong style="font-size: 0.75rem;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $port; ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%;"><?php echo $hostname ?: 'Available'; ?></div>
                </div>
                <?php if ($cubicle_num % 14 == 0 && $cubicle_num < 49) echo '<div class="walkway"></div>'; ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div style="font-size: 0.85rem; font-weight: 700;">Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-size: 0.85rem; font-weight: 700;">Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-size: 0.85rem; font-weight: 700;">Total: 49</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.5rem; font-weight:800;">Station Details</h2>
        <form>
            <input type="hidden" id="seatId">
            <label style="font-size:0.7rem; font-weight:700; display:block; margin-bottom:5px;">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:var(--bg); box-sizing: border-box; color: var(--text-dark);">
            
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

    function handleSeatClick(e, id, cubicle, host, sw, status) {
        if (isEditMode) return;
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatSwitch').value = sw === 'Not Set' ? '' : sw;
        document.getElementById('seatStatus').value = status;
        
        const hostInput = document.getElementById('seatHost');
        hostInput.style.opacity = (status === 'Vacant') ? '0.5' : '1';
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box').forEach(s => {
            let host = s.getAttribute('data-hostname') || "";
            s.classList.toggle('dimmed', val && !host.includes(val));
        });
    }
</script>
</body>
</html>