<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Binago sa Atlanta
$department_name = "Atlanta";

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

if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];
    $switch_port = $_POST['switch_port'] ?? ''; 
    $status = $_POST['status']; 

    if($status === 'Vacant') { $hostname = ''; }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: atlanta.php");
        exit();
    }
}

$stations = []; 
$occupied_count = 0;
$vacant_count = 0;

// Binago sa LIMIT 99 para sa Atlanta
$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC LIMIT 99");
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if($row['status'] === 'Occupied') $occupied_count++;
    else $vacant_count++;
}
$vacant_count += (99 - count($stations));
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
            --primary-light: #fff7ed;
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

        html, body { 
            height: 100vh; margin: 0; padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); color: var(--text-dark); overflow: hidden; 
        }
        
        .navbar { 
            background: #22c55e; padding: 0 2.5rem; 
            display: flex; align-items: center; height: 70px; box-sizing: border-box; 
            gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative; z-index: 10;
        }

        .nav-back-btn {
            color: white; text-decoration: none; font-size: 1.5rem;
            display: flex; align-items: center; transition: transform 0.2s;
        }
        .nav-back-btn:hover { transform: scale(1.1); }

        .container { 
            height: calc(100vh - 70px);
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            max-width: 1800px;
            margin: 0 auto;
        }

        .header-row {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;
        }

        .header-row h1 { 
            font-weight: 800; font-size: 1.8rem; letter-spacing: -0.03em; margin: 0;
            background: linear-gradient(to right, #0f172a, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        #searchInput {
            width: 320px; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 14px;
            border: 1px solid var(--border); background: white; font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); outline: none; box-shadow: var(--shadow-soft);
        }

        .map-grid-container { 
            background: rgba(255, 255, 255, 0.6); 
            backdrop-filter: blur(10px);
            padding: 1rem; border-radius: 30px; 
            border: 1px solid rgba(255,255,255,0.8); flex-grow: 1;
            display: flex; align-items: center; justify-content: center;
            box-shadow: var(--shadow-soft); overflow-y: auto;
        }

        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(11, 1fr); /* Mas malapad para sa 99 stations */
            gap: 8px; width: 100%;
        }

        .seat-box {
            padding: 10px 5px;
            border-radius: 12px; text-align: center; border: 1px solid transparent; 
            background: var(--card-bg); transition: all 0.3s ease; 
            cursor: pointer; display: flex; flex-direction: column; align-items: center; 
            justify-content: center; position: relative; box-shadow: var(--shadow-soft);
        }

        .seat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); z-index: 5; }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: rgba(255, 255, 255, 0.6); border: 1px solid var(--border); }

        .status-legend {
            display: flex; justify-content: center; gap: 30px; margin-top: 1rem;
            background: white; padding: 0.8rem 2rem; border-radius: 50px;
            width: fit-content; margin-left: auto; margin-right: auto;
            box-shadow: var(--shadow-soft); border: 1px solid var(--border);
        }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.85rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }

        .dimmed { opacity: 0.15; filter: blur(2px); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { 
            background: #fff; width: 400px; padding: 2rem; border-radius: 25px; 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        }

        /* Swap Mode Sidebar */
        .edit-sidebar {
            position: fixed; right: 20px; top: 90px;
            background: white; padding: 15px; border-radius: 20px;
            box-shadow: var(--shadow-hover); border: 1px solid var(--border);
            z-index: 100; display: flex; flex-direction: column; gap: 8px;
        }
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #fff7ed !important; transform: scale(1.05) !important; border: 2px solid var(--primary) !important; }
        
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(20px); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem;">OJTBox | Atlanta Dashboard</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.8rem;">Swap Mode</div>
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
        <h1>Atlanta Floor Plan</h1>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid" id="mapGrid">
            <?php 
            for($i = 0; $i < 99; $i++): 
                $row = $stations[$i] ?? null;
                $id = $row['id'] ?? ($i + 1);
                $cubicle_num = $i + 1;
                $cubicle_name = "ATL-" . str_pad($cubicle_num, 4, '0', STR_PAD_LEFT);
                $status = $row['status'] ?? 'Vacant';
                $hostname = $row['hostname'] ?? '';
                $switch_port = $row['campaign'] ?? 'Not Set';
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    <strong style="font-size: 0.75rem;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.6rem; color: var(--text-muted);"><?php echo $switch_port; ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; margin-top:2px;"><?php echo $hostname ?: 'Available'; ?></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div class="legend-item"><div class="dot" style="background: #22c55e;"></div>Occupied: <?php echo $occupied_count; ?></div>
        <div class="legend-item"><div class="dot" style="background: #cbd5e1;"></div>Vacant: <?php echo $vacant_count; ?></div>
        <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 20px;">Total: 99</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin-top:0; font-weight:800;">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc;">
            
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">SWITCH/PORT</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">
            
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">STATUS</label>
            <select name="status" id="seatStatus" onchange="toggleHostname()" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">HOSTNAME</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="update_seat" style="flex:2; padding:0.8rem; background:var(--primary); color:white; border:none; border-radius:10px; font-weight:800; cursor:pointer;">UPDATE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding:0.8rem; background:#f1f5f9; border:none; border-radius:10px; cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;
    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box');
        label.innerText = isEditMode ? "ON" : "OFF";
        label.style.color = isEditMode ? "var(--primary)" : "var(--text-muted)";
        document.getElementById('body').classList.toggle('edit-mode-active', isEditMode);
        
        seats.forEach(seat => {
            seat.setAttribute('draggable', isEditMode);
            if(isEditMode) {
                seat.addEventListener('dragstart', handleDragStart);
                seat.addEventListener('dragover', handleDragOver);
                seat.addEventListener('dragleave', handleDragLeave);
                seat.addEventListener('drop', handleDrop);
            }
        });
    }

    function handleDragStart(e) { e.dataTransfer.setData('sourceId', this.getAttribute('data-id')); }
    function handleDragOver(e) { e.preventDefault(); this.classList.add('drag-over'); }
    function handleDragLeave() { this.classList.remove('drag-over'); }
    function handleDrop(e) {
        e.preventDefault();
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');
        if (sourceId !== targetId) performSwap(sourceId, targetId);
    }

    function performSwap(src, tgt) {
        const formData = new FormData();
        formData.append('swap_seats', true);
        formData.append('source_id', src);
        formData.append('target_id', tgt);
        fetch('atlanta.php', { method: 'POST', body: formData }).then(() => location.reload());
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
        const isVacant = document.getElementById('seatStatus').value === 'Vacant';
        const hostInput = document.getElementById('seatHost');
        hostInput.value = isVacant ? '' : hostInput.value;
        hostInput.disabled = isVacant;
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }

    function searchMap() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box').forEach(seat => {
            seat.classList.toggle('dimmed', !seat.getAttribute('data-hostname').includes(input));
        });
    }
</script>
</body>
</html>