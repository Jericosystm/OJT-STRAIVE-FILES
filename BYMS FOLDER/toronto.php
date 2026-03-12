<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Toronto"; 

// --- SWAP HANDLER (Mirrored from Boston) ---
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

// --- UPDATE HANDLER (Mirrored from Boston) ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];
    $switch_port = $_POST['switch_port'] ?? ''; 
    $status = $_POST['status']; 

    if($status === 'Vacant') {
        $hostname = '';
    }

    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, campaign=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: toronto.php");
        exit();
    }
}

// --- DATA FETCHING (Mirrored from Boston) ---
$stations = []; 
$occupied_count = 0;
$vacant_count = 0;
$total_seats = 55; // Toronto total

$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC LIMIT ?");
$stmt->bind_param("si", $department_name, $total_seats);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $stations[] = $row;
    if($row['status'] === 'Occupied') $occupied_count++;
    else $vacant_count++;
}
// Account for missing database rows
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
            --primary: #2196f3;
            --nav-green: #90ee90;
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

        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
        .navbar { background: var(--nav-green); padding: 0 2.5rem; display: flex; align-items: center; height: 60px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: #1e293b; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
        .nav-back-btn:hover { transform: scale(1.1); }
        .container { height: calc(100vh - 60px); padding: 1.5rem 2.5rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1600px; margin: 0 auto; position: relative; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; width: 100%; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; color: #1e293b; }
        #searchInput { width: 320px; padding: 0.7rem 1rem 0.7rem 2.5rem; border-radius: 12px; border: 1px solid var(--border); background: white; outline: none; box-shadow: var(--shadow-soft); font-family: inherit; }
        .map-grid-container { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 24px; flex-grow: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 50px; }
        .map-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; width: 100%; height: 100%; max-height: calc(100vh - 230px); }
        .seat-box { border-radius: 12px; text-align: center; background: var(--card-bg); transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow-soft); border: 1px solid var(--border); padding: 5px; }
        .seat-box:not(.pillar):hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--primary); }
        .edit-mode-active .seat-box:not(.pillar) { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #fff7ed !important; transform: scale(1.05) !important; border: 2px solid var(--primary) !important; }
        .pillar { background: #334155 !important; color: white !important; cursor: not-allowed; border: none; font-weight: 800; font-size: 0.7rem; }
        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: white; }
        .dimmed { opacity: 0.1; filter: grayscale(1); }
        .edit-sidebar { position: fixed; right: 10px; top: 140px; background: white; padding: 1.2rem; border-radius: 18px; box-shadow: var(--shadow-soft); width: 140px; text-align: center; border: 1px solid var(--border); z-index: 100; }
        .swap-header { display: flex; flex-direction: column; align-items: center; gap: 8px; font-weight: 800; font-size: 0.85rem; }
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }
        .status-footer { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); background: white; padding: 8px 25px; border-radius: 50px; display: flex; gap: 20px; box-shadow: var(--shadow-soft); border: 1px solid var(--border); z-index: 20; }
        .status-item { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700; color: #334155; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 400px; padding: 2rem; border-radius: 24px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="font-weight: 900; font-size: 1.4rem;">OJTBox | Toronto Floor Plan</div>
</nav>

<div class="edit-sidebar">
    <div class="swap-header">
        <span>Swap Mode</span>
        <div style="display:flex; align-items:center; gap:8px;">
            <label class="switch">
                <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
                <span class="slider"></span>
            </label>
            <span id="toggleText" style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">OFF</span>
        </div>
    </div>
    <div style="font-size: 0.65rem; color: var(--text-muted); margin-top:10px;">Drag one cubicle onto another to swap hosts.</div>
</div>

<div class="container">
    <div class="header-row">
        <h1>Toronto Floor Plan</h1>
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid">
            <?php 
            $cubicle_counter = 1;
            $total_cells = 72; // Layout size

            for($i = 0; $i < $total_cells; $i++): 
                $currentRow = ceil(($i+1) / 8);
                $currentCol = ($i % 8) + 1;

                // Handle Pillar Position
                if ($cubicle_counter == 29 && !isset($pillar_placed)) {
                    echo '<div class="seat-box pillar">PILLAR</div>';
                    $pillar_placed = true;
                    continue; 
                }

                // Handle Grid Whitespace for Layout
                if ($cubicle_counter > 39 && $currentCol > 4) {
                    echo '<div></div>';
                    continue;
                }

                if ($cubicle_counter > $total_seats) {
                    echo '<div></div>';
                    continue;
                }

                // Database mapping
                $row = $stations[$cubicle_counter - 1] ?? null;
                $id = $row['id'] ?? $cubicle_counter; 
                $status = $row['status'] ?? 'Vacant';
                $hostname = $row['hostname'] ?? '';
                $port = $row['campaign'] ?? 'Not Set';
                $cubicle_name = "TOR-" . str_pad($cubicle_counter, 4, '0', STR_PAD_LEFT);
                
                $tooltip = "Cubicle: $cubicle_name\nStatus: $status\nHostname: " . ($hostname ?: 'None') . "\nPort: $port";
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     title="<?php echo $tooltip; ?>"
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $port; ?>', '<?php echo $status; ?>')">
                    
                    <strong style="font-size: 0.7rem;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.55rem; color: var(--text-muted);"><?php echo $port; ?></div>
                    <div style="font-size: 0.65rem; font-weight: 700; margin-top:2px;">
                        <?php echo $hostname ?: 'Available'; ?>
                    </div>
                </div>
            <?php 
                $cubicle_counter++;
            endfor; 
            ?>
        </div>
    </div>

    <div class="status-footer">
        <div class="status-item"><div class="dot" style="background: #22c55e;"></div> Occupied: <?php echo $occupied_count; ?></div>
        <div class="status-item"><div class="dot" style="background: #cbd5e1;"></div> Vacant: <?php echo $vacant_count; ?></div>
        <div class="status-item" style="border-left: 1px solid var(--border); padding-left: 15px;">Total: 55</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin:0 0 1.5rem; font-weight:800;">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">CUBICLE</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc;">

            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">PORT</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">

            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">STATUS</label>
            <select name="status" id="seatStatus" onchange="toggleHostname()" style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border);">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">HOSTNAME</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.7rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="update_seat" style="flex:2; padding: 0.8rem; background: var(--primary); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer;">SAVE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding: 0.8rem; background:#eee; border:none; border-radius:10px; cursor:pointer;">CANCEL</button>
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
        this.style.opacity = '1';
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');

        if (sourceId !== targetId) {
            const formData = new FormData();
            formData.append('swap_seats', true);
            formData.append('source_id', sourceId);
            formData.append('target_id', targetId);

            fetch('toronto.php', { method: 'POST', body: formData })
            .then(response => response.json())
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
        if(status === 'Vacant') {
            hostInput.value = '';
            hostInput.disabled = true;
            hostInput.style.opacity = '0.5';
        } else {
            hostInput.disabled = false;
            hostInput.style.opacity = '1';
        }
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box:not(.pillar)').forEach(s => {
            s.classList.toggle('dimmed', val && !s.getAttribute('data-hostname').includes(val));
        });
    }
</script>
</body>
</html>