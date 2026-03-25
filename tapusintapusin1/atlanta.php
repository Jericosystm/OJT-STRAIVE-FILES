<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "ATLANTA DEPARTMENT";
$back_link = "prod_map.php"; 

$department_name = "Atlanta"; 
$total_seats = 99;

/**
 * HELPER: Syncs hostname location to inventory
 */
function syncInventory($conn, $hostname, $cubicle, $dept, $status, $switch_port = '') { 
    $hostname = !empty($hostname) ? strtoupper(trim($hostname)) : 'N/A';
    $cubicle  = strtoupper(trim($cubicle)); 
    $dept     = trim($dept);
    $location = ($status === 'Occupied' && $hostname !== 'N/A') ? 'Onsite' : 'N/A';

    $stmt = $conn->prepare("UPDATE inventory_items SET hostname = ?, department = ?, location = ?, switch_port = ? WHERE UPPER(trim(cubicle_number)) = ?");
    $stmt->bind_param("sssss", $hostname, $dept, $location, $switch_port, $cubicle);    
    $stmt->execute();
}

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
    $switch_port = $_POST['switch_port'] ?? 'Not Set'; 
    $status = $_POST['status']; 
    $cubicle_name = "ATL-" . str_pad($id, 4, '0', STR_PAD_LEFT);

    $oldQuery = $conn->prepare("SELECT hostname FROM production_floor_map WHERE id = ?");
    $oldQuery->bind_param("i", $id);
    $oldQuery->execute();
    $oldHost = $oldQuery->get_result()->fetch_assoc()['hostname'] ?? '';

    if($status === 'Vacant') { $hostname = ''; }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=?, department=? WHERE id=?");
    $stmt->bind_param("ssssi", $hostname, $status, $switch_port, $department_name, $id);
    
    if($stmt->execute()) {
        if (!empty($oldHost) && $oldHost !== $hostname) {
            syncInventory($conn, 'N/A', $cubicle_name, $department_name, 'Vacant', '');
        }
        syncInventory($conn, $hostname, $cubicle_name, $department_name, $status, $switch_port);
        header("Location: atlanta.php");
        exit();
    }
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
    
    <?php include 'header.php'; ?>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #22c55e; 
            --primary-light: #f0fdf4;
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
            --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        html, body { 
            height: 100vh; 
            margin: 0; 
            padding: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            color: var(--text-dark); 
            overflow: hidden; 
            transition: background 0.3s, color 0.3s;
        }
        
        .container { 
            height: calc(100vh - 72px); 
            padding: 1rem 2rem; 
            display: flex; 
            flex-direction: column; 
            box-sizing: border-box; 
            max-width: 100%; 
            align-items: center; /* Centers the whole map block */
        }

        .header-row { width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; max-width: 1400px; }
        .header-row h1 { font-weight: 800; font-size: 1.4rem; letter-spacing: -0.03em; margin: 0; color: var(--text-dark); }

        #searchInput { 
            width: 260px; 
            padding: 0.5rem 1rem 0.5rem 2.2rem; 
            border-radius: 10px; 
            border: 1px solid var(--border); 
            background: var(--card-bg); 
            color: var(--text-dark);
            font-size: 0.8rem; 
            outline: none; 
            box-shadow: var(--shadow-soft); 
        }

        /* IMPROVED: This container now "hugs" the grid exactly */
        .map-grid-container { 
            background: var(--card-bg); 
            padding: 1.25rem; 
            border-radius: 20px; 
            border: 1px solid var(--border); 
            display: inline-flex; /* Changed from flex:1 to hug the content */
            align-items: center; 
            justify-content: center; 
            box-shadow: var(--shadow-soft); 
            overflow: hidden;
            margin: auto; /* Centers horizontally and vertically in flex column */
            max-width: fit-content; 
        }
        
        /* IMPROVED: Defined strict sizing to keep cubicles proportioned to their background */
        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(8, 1fr) 12px repeat(3, 1fr); 
            grid-template-rows: repeat(9, 1fr); 
            gap: 6px; 
            width: 85vw; /* Fills screen width but container follows */
            height: 70vh; /* Scaled to fit screen without being cramped */
            max-width: 1300px; 
            max-height: 650px;
        }

        .vertical-divider {
            grid-column: 9;
            grid-row: 1 / span 9;
            background: #64748b;
            width: 2px;
            justify-self: center;
            border-radius: 10px;
            opacity: .6;
        }

        .seat-box { 
            border-radius: 6px; 
            text-align: center; 
            border: 1px solid var(--border); 
            background: var(--card-bg); 
            transition: all 0.2s ease; 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            position: relative; 
            padding: 4px; 
        }
        .seat-box:hover { transform: scale(1.05); z-index: 10; border-color: #facc15; box-shadow: var(--shadow-hover); }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: var(--card-bg); border: 1px solid var(--border); opacity: 0.6; }

        .edit-sidebar { position: fixed; right: 25px; bottom: 85px; background: var(--card-bg); padding: 10px 15px; border-radius: 12px; box-shadow: var(--shadow-hover); border: 1px solid var(--border); z-index: 100; display: flex; flex-direction: column; gap: 4px; }
        
        .status-legend { display: flex; justify-content: center; gap: 20px; margin-top: 0.8rem; background: var(--card-bg); padding: 0.4rem 1.2rem; border-radius: 50px; width: fit-content; box-shadow: var(--shadow-soft); border: 1px solid var(--border); }
        .legend-item { display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 0.75rem; color: var(--text-dark); }
        .dot { width: 8px; height: 8px; border-radius: 50%; }

        .dimmed { opacity: 0.1 !important; filter: grayscale(1); pointer-events: none; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 2000; }
        .modal-content { 
            background: var(--card-bg); width: 360px; padding: 1.8rem; border-radius: 20px; 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body id="body">

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.75rem; color: var(--text-dark);">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <label class="switch" style="position: relative; display: inline-block; width: 34px; height: 18px;">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()" style="opacity: 0; width: 0; height: 0;">
            <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">OFF</span>
    </div>
</div>

<style>
    .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--primary); }
    input:checked + .slider:before { transform: translateX(16px); }
</style>

<div class="container">
    <div class="header-row">
        <div><h1>Atlanta Floor Plan</h1></div>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid" id="mapGrid">
            <div class="vertical-divider"></div>

            <?php 
            for($i = 0; $i < $total_seats; $i++): 
                $row = $stations[$i] ?? null;
                $id = $row['id'] ?? null; 
                $cubicle_num = $i + 1;
                $cubicle_name = "ATL-" . str_pad($cubicle_num, 4, '0', STR_PAD_LEFT);
                $status = $row['status'] ?? 'Vacant';
                $hostname = $row['hostname'] ?? '';
                $switch_port = $row['switch_port'] ?? 'Not Set';                    
                
                $col = ($i % 11) + 1;
                $grid_col = ($col > 8) ? $col + 1 : $col;
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                    id="seat-<?php echo $id ?: 'temp-'.$i; ?>"
                    data-id="<?php echo $id; ?>"
                    data-hostname="<?php echo strtolower($hostname); ?>"
                    style="grid-column: <?php echo $grid_col; ?>;"
                    onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    <strong style="font-size: 0.65rem;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.55rem; font-weight: 700; margin-top:2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 90%;">
                        <?php echo $hostname ?: '---'; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div class="legend-item">
            <div class="dot" style="background: #22c55e;"></div>
            Occupied: <span><?php echo $occupied_count; ?></span>
        </div>
        <div class="legend-item">
            <div class="dot" style="background: #cbd5e1;"></div>
            Vacant: <span><?php echo $vacant_count; ?></span>
        </div>
        <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 15px;">
            Total: 99
        </div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin:0 0 1.2rem; font-weight:800; font-size: 1.1rem; color: var(--text-dark);">Station Details</h2>
        <form>
            <input type="hidden" id="seatId">
            <div style="margin-bottom: 0.7rem;">
                <label style="font-size:0.6rem; font-weight:700; color: var(--text-muted);">CUBICLE</label>
                <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.5rem; border-radius:8px; border:1px solid var(--border); background: var(--bg); color: var(--text-dark);">
            </div>
            <div style="margin-bottom: 0.7rem;">
                <label style="font-size:0.6rem; font-weight:700; color: var(--text-muted);">PORT</label>
                <input type="text" id="seatSwitch" readonly style="width:100%; padding:0.5rem; border-radius:8px; border:1px solid var(--border); background: var(--bg); color: var(--text-dark);">
            </div>
            <div style="margin-bottom: 0.7rem;">
                <label style="font-size:0.6rem; font-weight:700; color: var(--text-muted);">STATUS</label>
                <select id="seatStatus" readonly style="width:100%; padding:0.5rem; border-radius:8px; border:1px solid var(--border); background: var(--bg); color: var(--text-dark); pointer-events:none;">
                    <option value="Occupied">Occupied</option>
                    <option value="Vacant">Vacant</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="font-size:0.6rem; font-weight:700; color: var(--text-muted);">HOSTNAME</label>
                <input type="text" id="seatHost" readonly style="width:100%; padding:0.5rem; border-radius:8px; border:1px solid var(--border); background: var(--bg); color: var(--text-dark);">
            </div>
            <button type="button" onclick="closeModal()" style="width: 100%; padding: 0.7rem; background: var(--primary); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer;">CLOSE</button>
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
            label.style.color = "var(--primary)";
            seats.forEach(seat => {
                seat.setAttribute('draggable', true);
                seat.addEventListener('dragstart', handleDragStart);
                seat.addEventListener('dragover', handleDragOver);
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
                seat.removeEventListener('drop', handleDrop);
            });
        }
    }

    function handleDragStart(e) { e.dataTransfer.setData('sourceId', this.getAttribute('data-id')); }
    function handleDragOver(e) { e.preventDefault(); }
    function handleDrop(e) {
        e.preventDefault();
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');
        if (sourceId && targetId && sourceId !== targetId) {
            const formData = new FormData();
            formData.append('swap_seats', true);
            formData.append('source_id', sourceId);
            formData.append('target_id', targetId);
            fetch('atlanta.php', { method: 'POST', body: formData })
            .then(() => location.reload());
        }
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode || !id) return; 
        openEdit(id, cubicle, host, sw, status);
    }

    function openEdit(id, cubicle, host, sw, status) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatSwitch').value = sw === 'Not Set' ? '' : sw;
        document.getElementById('seatStatus').value = status;
        document.getElementById('modalHeader').innerText = "Details: " + cubicle;
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let seat of seats) {
            let host = seat.getAttribute('data-hostname');
            if (input === "") {
                seat.classList.remove('dimmed');
            } else {
                seat.classList.toggle('dimmed', !host.includes(input));
            }
        }
    }
</script>
</body>
</html>