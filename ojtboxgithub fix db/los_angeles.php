<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Los Angeles"; 
$total_seats = 63; // Standardized for LA

// --- SWAP HANDLER ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    $stmt = $conn->prepare("SELECT hostname, status, campaign FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $sourceId);
    $stmt->execute();
    $sourceData = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT hostname, status, campaign FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $targetData = $stmt->get_result()->fetch_assoc();

    $updateSource = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=? WHERE id=?");
    $updateSource->bind_param("sssi", $targetData['hostname'], $targetData['status'], $targetData['campaign'], $sourceId);
    
    $updateTarget = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=? WHERE id=?");
    $updateTarget->bind_param("sssi", $sourceData['hostname'], $sourceData['status'], $sourceData['campaign'], $targetId);

    if($updateSource->execute() && $updateTarget->execute()) {
        echo json_encode(['success' => true]);
        exit();
    }
}

// --- UPDATE HANDLER ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = trim($_POST['hostname']);
    $switch_port = $_POST['switch_port'] ?? ''; 
    $status = $_POST['status']; 
    // Capture the cubicle name to sync back to inventory
    $get_cubicle = $conn->prepare("SELECT cubicle_no FROM production_floor_map WHERE id = ?");
    $get_cubicle->bind_param("i", $id);
    $get_cubicle->execute();
    $cubicle_name = $get_cubicle->get_result()->fetch_assoc()['cubicle_no'];

    $conn->begin_transaction();

    try {
        if($status === 'Vacant') {
            $getOld = $conn->prepare("SELECT hostname FROM production_floor_map WHERE id = ?");
            $getOld->bind_param("i", $id);
            $getOld->execute();
            $oldHost = $getOld->get_result()->fetch_assoc()['hostname'];

            if(!empty($oldHost)) {
                $syncInv = $conn->prepare("UPDATE inventory_items SET status = 'Vacant', cubicle_number = 'N/A' WHERE host_name = ?");
                $syncInv->bind_param("s", $oldHost);
                $syncInv->execute();
            }
            $hostname = ''; 
        } else {
            $syncInv = $conn->prepare("UPDATE inventory_items SET status = 'Active', location = 'Onsite', cubicle_number = ? WHERE host_name = ?");
            $syncInv->bind_param("ss", $cubicle_name, $hostname);
            $syncInv->execute();
        }

        $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=? WHERE id=?");
        $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
        $stmt->execute();

        $conn->commit();
        header("Location: los_angeles.php"); 
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error updating record: " . $e->getMessage();
    }
}

// --- DATA FETCHING (CRITICAL FIX) ---
// We fetch by department and map them to an associative array using cubicle_no as the key
$query = "SELECT * FROM production_floor_map WHERE department = ? ORDER BY cubicle_no ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();

$station_data = [];
$occupied_count = 0;
$vacant_count = 0;

while($row = $result->fetch_assoc()) {
    $station_data[$row['cubicle_no']] = $row;
    if($row['status'] === 'Occupied') $occupied_count++;
    else $vacant_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | <?php echo $department_name; ?> Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [KEEP YOUR EXISTING CSS HERE - NO CHANGES NEEDED] */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root { --primary: #90be6d; --primary-light: #f7fee7; --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b; --text-muted: #94a3b8; --border: #e2e8f0; --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); --occupied-text: #15803d; --occupied-border: #bbf7d0; --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        .navbar { background: #90be6d; padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: white; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
        .container { height: calc(100vh - 70px); padding: 1rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1600px; margin: 0 auto; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.03em; margin: 0; background: linear-gradient(to right, #0f172a, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        #searchInput { width: 320px; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 14px; border: 1px solid var(--border); background: white; font-size: 0.9rem; outline: none; box-shadow: var(--shadow-soft); }
        .map-grid-container { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 30px; border: 1px solid rgba(255,255,255,0.8); flex-grow: 1; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-soft); overflow: auto; }
        .map-grid { display: grid; grid-template-columns: repeat(9, 1fr); gap: 10px; width: 100%; height: 100%; max-height: 800px; }
        .seat-box { border-radius: 12px; text-align: center; border: 1px solid transparent; background: var(--card-bg); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow-soft); padding: 5px; }
        .seat-box:hover { transform: translateY(-3px) scale(1.02); box-shadow: var(--shadow-hover); z-index: 10; border-color: var(--primary); }
        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: rgba(255, 255, 255, 0.6); border: 1px solid var(--border); }
        .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow-hover); border: 1px solid var(--border); z-index: 100; display: flex; flex-direction: column; gap: 10px; }
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #ecfdf5 !important; transform: scale(1.05) !important; border: 2px solid var(--primary) !important; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }
        .status-legend { display: flex; justify-content: center; gap: 30px; margin-top: 1rem; background: white; padding: 0.8rem 2rem; border-radius: 50px; width: fit-content; margin-left: auto; margin-right: auto; box-shadow: var(--shadow-soft); border: 1px solid var(--border); }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dimmed { opacity: 0.15; filter: blur(2px); transform: scale(0.9); }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 420px; padding: 2.5rem; border-radius: 32px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem;">OJTBox | Los Angeles Dashboard</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.9rem;">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <label class="switch"><input type="checkbox" id="editToggle" onchange="toggleEditMode()"><span class="slider"></span></label>
        <span id="statusLabel" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Los Angeles Floor Plan</h1>
        <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
    </div>

    <div class="map-grid-container">
        <div class="map-grid">
            <?php 
            for($i = 1; $i <= $total_seats; $i++): 
                $c_name = "LAL-" . str_pad($i, 4, '0', STR_PAD_LEFT);
                $info = $station_data[$c_name] ?? null; // Match cubicle name to DB row
                
                $id = $info['id'] ?? 0;
                $status = $info['status'] ?? 'Vacant';
                $hostname = $info['hostname'] ?? '';
                $switch_port = $info['campaign'] ?? 'Not Set';
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $c_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    <strong style="font-size: 0.75rem;"><?php echo $c_name; ?></strong>
                    <div style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $switch_port; ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700;"><?php echo $hostname ?: 'Available'; ?></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div class="legend-item"><div class="dot" style="background: #22c55e;"></div> Occupied: <?php echo $occupied_count; ?></div>
        <div class="legend-item"><div class="dot" style="background: #cbd5e1;"></div> Vacant: <?php echo $vacant_count; ?></div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label>Cubicle ID</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:12px; background:#f8fafc;">
            
            <label>Switch & Port</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:12px; border:1px solid var(--border);">

            <label>Current Status</label>
            <select name="status" id="seatStatus" onchange="toggleHostname()" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:12px; border:1px solid var(--border);">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <label>Hostname</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:12px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="submit" name="update_seat" style="flex:2; padding: 1rem; background: var(--primary); color: #fff; border:none; border-radius:14px; font-weight:800;">UPDATE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding: 1rem; background:#f1f5f9; border:none; border-radius:14px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;
    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        document.getElementById('statusLabel').innerText = isEditMode ? "ON" : "OFF";
        document.getElementById('body').classList.toggle('edit-mode-active', isEditMode);
    }
    function handleSeatClick(event, id, cubicle, host, sw, status) {
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
        if(status === 'Vacant') hostInput.value = '';
    }
    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.querySelectorAll('.seat-box');
        seats.forEach(s => {
            let host = s.getAttribute('data-hostname');
            s.classList.toggle('dimmed', !host.includes(input));
        });
    }
</script>
</body>
</html>