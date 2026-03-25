<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables for header.php
$page_title = "TORONTO DEPARTMENT";
$back_link = "prod_map.php"; 

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
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root { 
            --primary: #2196f3; 
            --nav-green: #22c55e; 
            --bg: #f1f5f9; 
            --card-bg: #ffffff; 
            --text-dark: #1e293b; 
            --text-muted: #94a3b8; 
            --border: #e2e8f0; 
            --occupied-bg: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
            --occupied-text: #15803d; 
            --occupied-border: #bbf7d0; 
            --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05); 
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
        
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; transition: background 0.3s, color 0.3s; }
        
        .container { 
            height: calc(100vh - 72px); 
            padding: 0.5rem 2rem; 
            display: flex; 
            flex-direction: column; 
            box-sizing: border-box; 
            max-width: 1600px; 
            margin: 0 auto; 
            position: relative; 
        }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; width: 100%; flex-shrink: 0; }
        .header-row h1 { font-weight: 800; font-size: 1.4rem; margin: 0; color: var(--text-dark); }
        
        .map-grid-container {
            background: var(--card-bg);
            padding: 1.5rem; 
            border-radius: 24px;
            flex: 1; 
            display: flex;
            align-items: stretch;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 75px; 
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
            max-height: 78vh; 
        }

        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(8, 1fr); 
            grid-template-rows: repeat(9, 1fr);
            gap: 12px; /* Increased gap for breathing air */
            width: 100%;
            height: 100%; 
        }

        .seat-box { 
            border-radius: 12px; /* Smoother, less boxy corners */
            background: #ffffff; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            position: relative; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.06); 
            border: 1px solid var(--border); 
            padding: 8px; 
            color: var(--text-dark);
            box-sizing: border-box;
            min-height: 0; 
        }

        [data-theme='dark'] .seat-box { background: #1e293b; }

        .seat-box:hover { 
            transform: translateY(-4px) scale(1.02); 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            border-color: var(--primary); 
            z-index: 10; 
        }

        .seat-box strong { font-size: 0.75rem; font-weight: 800; margin-bottom: 3px; letter-spacing: -0.02em; }
        .seat-box .port-label { font-size: 0.55rem; color: var(--text-muted); font-weight: 600; }
        .seat-box .host-label { 
            font-size: 0.6rem; 
            font-weight: 700; 
            color: var(--primary); 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            max-width: 90%;
            margin-top: 2px;
            padding: 2px 6px;
            background: rgba(33, 150, 243, 0.08);
            border-radius: 6px;
        }

        .edit-mode-active .seat-box:not(.pillar) { 
            cursor: grab; 
            border: 2px dashed var(--primary) !important; 
            background: var(--bg); 
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .pillar { 
            background: #334155 !important; 
            color: #94a3b8 !important; 
            cursor: not-allowed; 
            border: none; 
            font-weight: 800; 
            font-size: 0.65rem;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.2);
        }
        
        .Occupied { 
            background: var(--occupied-bg) !important; 
            color: var(--occupied-text); 
            border: 1px solid var(--occupied-border); 
        }
        .Occupied .host-label { 
            color: var(--occupied-text); 
            background: rgba(21, 128, 61, 0.1);
        }
        
        .drag-over { background: #eff6ff !important; border: 2px solid var(--primary) !important; transform: scale(1.08) !important; }
        .dimmed { opacity: 0.1; filter: grayscale(1); }

        .edit-sidebar { position: fixed; right: 30px; top: 100px; background: var(--card-bg); padding: 1rem; border-radius: 20px; box-shadow: var(--shadow-soft); width: 130px; border: 1px solid var(--border); z-index: 110; color: var(--text-dark); }
        .status-footer { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); background: var(--card-bg); padding: 8px 30px; border-radius: 50px; display: flex; gap: 20px; box-shadow: var(--shadow-soft); border: 1px solid var(--border); z-index: 20; color: var(--text-dark); font-size: 0.8rem; }
        
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(20px); }
    </style>
</head>
<body id="body">

<?php include 'header.php'; ?>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.8rem; margin-bottom: 6px;">Swap Mode</div>
    <div style="display:flex; align-items:center; gap:8px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="toggleText" style="font-size:0.65rem; font-weight:700; color:var(--text-muted);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Toronto Floor Plan</h1>
        <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()" style="width: 250px; padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--card-bg); color: var(--text-dark); font-size: 0.85rem; outline: none;">
    </div>

    <div class="map-grid-container">
        <div class="map-grid">
            <?php 
            $cubicle_counter = 1;
            $rows = 9; 
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
                        <strong><?php echo $cubicle_name; ?></strong>
                        <div class="port-label"><?php echo $port; ?></div>
                        <div class="host-label"><?php echo $hostname ?: 'Available'; ?></div>
                    </div>
                    <?php 
                    $cubicle_counter++;
                }
            }
            ?>
        </div>
    </div>

    <div class="status-footer">
        <div style="font-weight:700;"><i class="fa-solid fa-user-check" style="color:var(--nav-green); margin-right:5px;"></i> Occupied: <?php echo $occupied_count; ?></div>
        <div style="font-weight:700;"><i class="fa-solid fa-circle-dot" style="color:var(--text-muted); margin-right:5px;"></i> Vacant: <?php echo $vacant_count; ?></div>
        <div style="font-weight:700; border-left: 1px solid var(--border); padding-left:20px;">Total Seats: 55</div>
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