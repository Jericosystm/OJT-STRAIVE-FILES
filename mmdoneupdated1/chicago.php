<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Chicago"; 
$total_seats = 28; 

// --- SWAP HANDLER ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    // Logic: Retrieve using switch_port column
    $stmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $sourceId);
    $stmt->execute();
    $sourceData = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $targetData = $stmt->get_result()->fetch_assoc();

    $updateSource = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateSource->bind_param("sssi", $targetData['hostname'], $targetData['status'], $targetData['switch_port'], $sourceId);
    
    $updateTarget = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateTarget->bind_param("sssi", $sourceData['hostname'], $sourceData['status'], $sourceData['switch_port'], $targetId);

    if($updateSource->execute() && $updateTarget->execute()) {
        echo json_encode(['success' => true]);
        exit();
    }
}


// --- UPDATE HANDLER ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];
    $switch_port = $_POST['switch_port'] ?? 'Not Set'; 
    $status = $_POST['status']; 

    if($status === 'Vacant') {
        $hostname = '';
    }

    // Logic: Save using switch_port column
    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $switch_port, $id);
    
    if($stmt->execute()) {
        header("Location: chicago.php");
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

$stmt->execute();
$result = $stmt->get_result();



$stations = []; 
$occupied_count = 0;
$vacant_count = 0;

// 1. Prepare and Bind
$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC LIMIT ?");
$stmt->bind_param("si", $department_name, $total_seats);

// 2. Execute and Get Result (ONLY ONCE)
$stmt->execute();
$result = $stmt->get_result();

// 3. The Debugging Block
if ($result->num_rows === 0) {
    // This is styled to be visible on your page
    echo "<div style='color: white; background: red; padding: 20px; text-align: center; font-weight: bold;'>";
    echo "DEBUG: No rows found for department: '" . $department_name . "' in table production_floor_map.";
    echo "</div>";
}

// 4. Fetch the data
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
            --primary: #2196f3;
            --primary-light: #f0f7ff;
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

        /* 1. Prevent the whole page from bouncing/scrolling */
        html, body { 
            height: 100vh; 
            margin: 0; 
            padding: 0; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg); 
            color: var(--text-dark); 
            overflow: hidden; 
        }

        .navbar { background: #2196f3; padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: white; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
        .nav-back-btn:hover { transform: scale(1.1); }

        /* 2. Fix the container height and layout */
        .container { 
            height: calc(100vh - 70px); 
            padding: 1rem 2rem; 
            display: flex; 
            flex-direction: column; 
            box-sizing: border-box; 
            max-width: 1400px; 
            margin: 0 auto; 
        }

        .header-row { flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.03em; margin: 0; background: linear-gradient(to right, #0f172a, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        #searchInput { width: 320px; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 14px; border: 1px solid var(--border); background: white; font-size: 0.9rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); outline: none; box-shadow: var(--shadow-soft); }
        #searchInput:focus { border-color: var(--primary); width: 350px; }

        /* 3. This is the scrollable area */
        .map-grid-container { 
            background: rgba(255, 255, 255, 0.6); 
            backdrop-filter: blur(10px); 
            padding: 2rem; 
            border-radius: 30px; 
            border: 1px solid rgba(255,255,255,0.8); 
            flex-grow: 1; 
            overflow-y: auto; /* Enable scroll */
            box-shadow: var(--shadow-soft);
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        /* Scrollbar styling for Chrome/Edge */
        .map-grid-container::-webkit-scrollbar { width: 6px; }
        .map-grid-container::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* 4. Fixed grid rows to auto to allow growth */
        .map-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            gap: 15px; 
            width: 100%; 
        }

        .walkway { grid-column: span 7; height: 30px; background: transparent; pointer-events: none; }

        .seat-box { 
            min-height: 100px; /* Ensure boxes don't shrink */
            border-radius: 16px; 
            text-align: center; 
            border: 1px solid transparent; 
            background: var(--card-bg); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            position: relative; 
            box-shadow: var(--shadow-soft); 
        }
        
        .seat-box:hover { transform: translateY(-5px) scale(1.03); box-shadow: var(--shadow-hover); z-index: 10; }

        @keyframes pulse-vacant {
            0% { box-shadow: 0 0 0 0 rgba(148, 163, 184, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(148, 163, 184, 0); }
            100% { box-shadow: 0 0 0 0 rgba(148, 163, 184, 0); }
        }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: rgba(255, 255, 255, 0.6); border: 1px solid var(--border); animation: pulse-vacant 2s infinite; }
        .Vacant:hover { border: 2px solid #fbbf24 !important; box-shadow: 0 0 15px rgba(251, 191, 36, 0.4); }

        .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow-hover); border: 1px solid var(--border); z-index: 100; display: flex; flex-direction: column; gap: 10px; }
        .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
        .drag-over { background: #f0f7ff !important; transform: scale(1.1) !important; border: 2px solid var(--primary) !important; }
        
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }

        /* 5. Ensure legend stays at the bottom */
        .status-legend { 
            flex-shrink: 0;
            display: flex; 
            justify-content: center; 
            gap: 30px; 
            margin-top: 1rem; 
            background: white; 
            padding: 0.8rem 2rem; 
            border-radius: 50px; 
            width: fit-content; 
            margin-left: auto; 
            margin-right: auto; 
            box-shadow: var(--shadow-soft); 
            border: 1px solid var(--border); 
        }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dimmed { opacity: 0.15; filter: blur(2px); transform: scale(0.9); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 420px; padding: 2.5rem; border-radius: 32px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn" title="Back to Overview">
        <i class="fa-solid fa-circle-arrow-left"></i>
    </a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.02em;">OJTBox | Chicago Dashboard</div>
</nav>

<div class="edit-sidebar">
    <div style="font-weight: 800; font-size: 0.9rem; color: var(--text-dark);">Swap Mode</div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <label class="switch">
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
            <span class="slider"></span>
        </label>
        <span id="statusLabel" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">OFF</span>
    </div>
</div>

<div class="container">
    <div class="header-row">
        <div><h1>Chicago Floor Plan</h1></div>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid" id="mapGrid">
            <?php 
            for($i = 0; $i < $total_seats; $i++): 
                $row = $stations[$i] ?? null;
                $id = $row['id'] ?? ($i + 50); 
                $cubicle_num = $i + 1;
                $cubicle_name = "CHI-" . str_pad($cubicle_num, 4, '0', STR_PAD_LEFT);
                $status = $row['status'] ?? 'Vacant';
                $hostname = $row['hostname'] ?? '';
                $switch_port = $row['campaign'] ?? 'Not Set';
                
                // --- HOVER FEATURE START ---
                $tooltip = "Cubicle: $cubicle_name\nStatus: $status\nHostname: " . ($hostname ?: 'None') . "\nPort: $switch_port";
                // --- HOVER FEATURE END ---
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     title="<?php echo $tooltip; ?>" 
                     onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                    <strong style="font-size: 0.85rem; display:block;"><?php echo $cubicle_name; ?></strong>
                    <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600;"><?php echo $switch_port; ?></div>
                    <div style="font-size: 0.75rem; font-weight: 700; margin-top:4px;"><?php echo $hostname ?: 'Available'; ?></div>
                </div>

                <?php 
                if ($cubicle_num % 7 == 0 && $cubicle_num < $total_seats) {
                    echo '<div class="walkway"></div>';
                }
                ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="status-legend">
        <div class="legend-item"><div class="dot" style="background: #22c55e;"></div>Occupied: <span style="color: #166534;"><?php echo $occupied_count; ?></span></div>
        <div class="legend-item"><div class="dot" style="background: #cbd5e1;"></div>Vacant: <span style="color: #64748b;"><?php echo $vacant_count; ?></span></div>
        <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 20px;">Total: 35</div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin-top:0; color:var(--text-dark); font-size:1.6rem; font-weight:800;">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label style="font-weight:700; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Cubicle ID</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border); background:#f8fafc; font-weight:700;">

            <label style="font-weight:700; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Switch & Port</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border);">

            <label style="font-weight:700; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Current Status</label>
            <select name="status" id="seatStatus" onchange="toggleHostname()" style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border);">
                <option value="Occupied">Occupied (Online)</option>
                <option value="Vacant">Vacant (Available)</option>
            </select>

            <div id="hostnameWrapper">
                <label style="font-weight:700; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">Hostname</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 1rem;">
                <button type="submit" name="update_seat" style="flex:2; padding: 1rem; background: var(--primary); color: #fff; border:none; border-radius:14px; font-weight:800; cursor:pointer;">UPDATE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding: 1rem; background:#f1f5f9; color:#64748b; border:none; border-radius:14px; font-weight:700; cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Javascript logic remains exactly as provided in Chicago code
    let isEditMode = false;

    window.onload = function() {
        const savedMode = localStorage.getItem('swapModeEnabled_Chicago');
        if (savedMode === 'true') {
            document.getElementById('editToggle').checked = true;
            toggleEditMode();
        }
    };

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        localStorage.setItem('swapModeEnabled_Chicago', isEditMode);
        const body = document.getElementById('body');
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box');

        if (isEditMode) {
            body.classList.add('edit-mode-active');
            label.innerText = "ON"; label.style.color = "var(--primary)";
            seats.forEach(seat => {
                seat.setAttribute('draggable', true);
                seat.addEventListener('dragstart', handleDragStart);
                seat.addEventListener('dragover', handleDragOver);
                seat.addEventListener('dragleave', handleDragLeave);
                seat.addEventListener('drop', handleDrop);
            });
        } else {
            body.classList.remove('edit-mode-active');
            label.innerText = "OFF"; label.style.color = "var(--text-muted)";
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
        if (sourceId !== targetId) performSwap(sourceId, targetId);
        document.querySelectorAll('.seat-box').forEach(s => s.style.opacity = '1');
    }

    function performSwap(src, tgt) {
        const formData = new FormData();
        formData.append('swap_seats', true);
        formData.append('source_id', src);
        formData.append('target_id', tgt);
        fetch('chicago.php', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => { if(data.success) location.reload(); });
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode) return;
        openEdit(id, cubicle, host, sw, status);
    }

    function openEdit(id, cubicle, host, sw, status) {
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
        if(status === 'Vacant') hostInput.value = '';
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