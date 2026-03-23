<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Toronto"; 
$total_seats = 55; 

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
        :root { --primary: #2196f3; --nav-green: #22c55e; --bg: #f1f5f9; --card-bg: #ffffff; --text-dark: #1e293b; --text-muted: #94a3b8; --border: #e2e8f0; --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); --occupied-text: #15803d; --occupied-border: #bbf7d0; --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); overflow: hidden; }
        .navbar { background: var(--nav-green); padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 100; }
        .nav-back-btn { color: white; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; }
        
        .container { height: calc(100vh - 70px); padding: 1.5rem 2.5rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1600px; margin: 0 auto; position: relative; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; width: 100%; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; }
        
        .map-grid-container {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 24px;
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 60px; /* Space for the floating footer */
        }

        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(8, 1fr); 
            gap: 10px; 
            width: 100%;
        }

        .seat-box { border-radius: 12px; background: var(--card-bg); transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow-soft); border: 1px solid var(--border); padding: 8px; min-height: 70px; }
        .edit-mode-active .seat-box:not(.pillar) { cursor: grab; border: 2px dashed var(--primary) !important; background: #f8fafc; }
        .pillar { background: #334155 !important; color: white !important; cursor: not-allowed; border: none; font-weight: 800; font-size: 0.7rem; }
        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .drag-over { background: #eff6ff !important; transform: scale(1.05); border: 2px solid var(--primary) !important; }
        .dimmed { opacity: 0.1; filter: grayscale(1); }

        .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 1.2rem; border-radius: 18px; box-shadow: var(--shadow-soft); width: 150px; border: 1px solid var(--border); z-index: 110; }
        .status-footer { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: white; padding: 10px 30px; border-radius: 50px; display: flex; gap: 20px; box-shadow: var(--shadow-soft); border: 1px solid var(--border); z-index: 20; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem;">OJTBox | Toronto Map</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.85rem; margin-bottom: 8px;">Swap Mode</div>
    <div style="display:flex; align-items:center; gap:8px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="toggleText" style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Toronto Floor Plan</h1>
        <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()" style="width: 300px; padding: 10px; border-radius: 12px; border: 1px solid var(--border);">
    </div>

    <div class="map-grid-container">
        <div class="map-grid">
            <?php 
            $cubicle_counter = 1;
            $rows = 12; 
            $cols = 8; 

            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $cols; $c++) {
                    if ($r == 4 && $c == 5) {
                        echo '<div class="seat-box pillar">PILLAR</div>';
                        continue; 
                    }
                    if ($r >= 6 && $c >= 5) {
                        echo '<div></div>';
                        continue;
                    }
                    if ($cubicle_counter > $total_seats) {
                        echo '<div></div>';
                        continue;
                    }

                    $row = $stations[$cubicle_counter - 1] ?? null;
                    $db_id = $row['id'] ?? 0; 
                    $status = $row['status'] ?? 'Vacant';
                    $hostname = $row['hostname'] ?? '';
                    $port = $row['switch_port'] ?? 'Not Set';
                    $cubicle_name = $row['cubicle_no'] ?? "TOR-" . str_pad($cubicle_counter, 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="seat-box <?php echo $status; ?>" 
                         data-id="<?php echo $db_id; ?>"
                         data-hostname="<?php echo strtolower($hostname); ?>"
                         onclick="handleSeatClick(event, '<?php echo $db_id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo addslashes($hostname); ?>', '<?php echo addslashes($port); ?>', '<?php echo $status; ?>')">
                        <strong style="font-size: 0.65rem;"><?php echo $cubicle_name; ?></strong>
                        <div style="font-size: 0.5rem; color: var(--text-muted);"><?php echo $port; ?></div>
                        <div style="font-size: 0.6rem; font-weight: 700;"><?php echo $hostname ?: 'Available'; ?></div>
                    </div>
                    <?php 
                    $cubicle_counter++;
                }
            }
            ?>
        </div>
    </div>

    <div class="status-footer">
        <div style="font-weight:700;">Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-weight:700;">Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-weight:700; border-left: 1px solid #ddd; padding-left:15px;">Total: 55</div>
    </div>
</div>

<script>
    let isEditMode = false;

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        document.getElementById('toggleText').innerText = isEditMode ? 'ON' : 'OFF';
        const body = document.getElementById('body');
        const seats = document.querySelectorAll('.seat-box:not(.pillar)');

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
            seats.forEach(seat => seat.setAttribute('draggable', false));
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
            fetch('toronto.php', { method: 'POST', body: formData }).then(() => location.reload());
        }
    }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box:not(.pillar)').forEach(s => {
            s.classList.toggle('dimmed', val && !s.getAttribute('data-hostname').includes(val));
        });
    }

    function handleSeatClick(e, id, cubicle, host, sw, status) {
        if (isEditMode) return;
        alert("Cubicle: " + cubicle + "\nHostname: " + (host || 'None'));
    }
</script>
</body>
</html>