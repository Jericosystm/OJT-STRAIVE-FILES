<?php
session_start();
require_once 'db.php';

// --- FETCH DATA (Unique Cubicle Fix) ---
$stations = [];
$query = "SELECT * FROM production_floor_map 
          WHERE department = 'San Antonio' 
          OR cubicle_no LIKE 'SA-%' 
          ORDER BY cubicle_no ASC"; // Sort by cubicle name instead of ID

$res = $conn->query($query);

if ($res) {
    while($row = $res->fetch_assoc()) {
        // Use cubicle_no as the key to naturally prevent duplicates in the array
        $stations[$row['cubicle_no']] = $row;
    }
}

// Reset counts based on the unique list
$occupied_count = 0;
foreach($stations as $s) {
    if($s['status'] === 'Occupied') $occupied_count++;
}
$vacant_count = count($stations) - $occupied_count;
// 2. COUNTS
$occupied_count = 0;
foreach($stations as $s) {
    if($s['status'] === 'Occupied') $occupied_count++;
}
$vacant_count = count($stations) - $occupied_count;

/**
 * HELPER: Syncs hostname location to inventory
 * Ensures a PC is either 'Onsite' at a cubicle or 'WFH'
 */
function syncInventory($conn, $hostname, $cubicle, $dept, $status) {
    $hostname = trim($hostname);
    if (empty($hostname)) return;

    // 1. First, set any device currently claiming this cubicle to WFH 
    // This prevents two PCs from having the same seat number.
    $clearOldSeat = $conn->prepare("UPDATE inventory_items SET cubicle_number = '', location = 'WFH' WHERE cubicle_number = ?");
    $clearOldSeat->bind_param("s", $cubicle);
    $clearOldSeat->execute();

    // 2. Now, update the specific PC we are moving
    if ($status === 'Occupied') {
        // We use UPPER() to ensure 'pc-01' matches 'PC-01'
        $stmt = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, department = ?, location = 'Onsite' WHERE UPPER(hostname) = UPPER(?)");
        $stmt->bind_param("sss", $cubicle, $dept, $hostname);
    } else {
        $stmt = $conn->prepare("UPDATE inventory_items SET cubicle_number = '', location = 'WFH' WHERE UPPER(hostname) = UPPER(?)");
        $stmt->bind_param("s", $hostname);
    }

    if (!$stmt->execute()) {
        error_log("Inventory Sync Failed for Hostname: " . $hostname);
    }
}

// --- SWAP HANDLER ---
if(isset($_POST['swap_seats'])) {
    $sourceId = $_POST['source_id'];
    $targetId = $_POST['target_id'];

    // Fetch data for both seats
    $sStmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $sStmt->bind_param("i", $sourceId);
    $sStmt->execute();
    $sourceData = $sStmt->get_result()->fetch_assoc();

    $tStmt = $conn->prepare("SELECT hostname, status, switch_port FROM production_floor_map WHERE id = ?");
    $tStmt->bind_param("i", $targetId);
    $tStmt->execute();
    $targetData = $tStmt->get_result()->fetch_assoc();

    // Perform the swap in the Floor Map
    $updateSource = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateSource->bind_param("sssi", $targetData['hostname'], $targetData['status'], $targetData['switch_port'], $sourceId);
    
    $updateTarget = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $updateTarget->bind_param("sssi", $sourceData['hostname'], $sourceData['status'], $sourceData['switch_port'], $targetId);

    if($updateSource->execute() && $updateTarget->execute()) {
        $sourceCubicle = "SA-" . str_pad($sourceId, 4, '0', STR_PAD_LEFT);
        $targetCubicle = "SA-" . str_pad($targetId, 4, '0', STR_PAD_LEFT);

        // Sync Inventory: Source PC moves to Target Cubicle, Target PC moves to Source Cubicle
        syncInventory($conn, $targetData['hostname'], $sourceCubicle, $department_name, $targetData['status']);
        syncInventory($conn, $sourceData['hostname'], $targetCubicle, $department_name, $sourceData['status']);

        echo json_encode(['success' => true]);
        exit();
    }
}

// --- UPDATE SEAT HANDLER ---
if(isset($_POST['update_seat'])) {
    $id = $_POST['id'];
    $hostname = trim($_POST['hostname']);
    $port_value = $_POST['switch_port'] ?? 'Not Set'; 
    $status = $_POST['status']; 
    $cubicle_name = "SA-" . str_pad($id, 4, '0', STR_PAD_LEFT);

    // Get the OLD hostname to clear it if it's being replaced or removed
    $oldQuery = $conn->prepare("SELECT hostname FROM production_floor_map WHERE id = ?");
    $oldQuery->bind_param("i", $id);
    $oldQuery->execute();
    $oldHost = $oldQuery->get_result()->fetch_assoc()['hostname'] ?? '';

    // Update Floor Map
    $stmt = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=?, switch_port=? WHERE id=?");
    $stmt->bind_param("sssi", $hostname, $status, $port_value, $id);
    
    if($stmt->execute()) {
        // If the seat became Vacant or the PC changed, move the old PC to WFH
        if (!empty($oldHost) && ($oldHost !== $hostname || $status === 'Vacant')) {
            syncInventory($conn, $oldHost, '', $department_name, 'Vacant');
        }

        // If the seat is now Occupied, sync the new PC to this cubicle
        if ($status === 'Occupied') {
            syncInventory($conn, $hostname, $cubicle_name, $department_name, 'Occupied');
        }
        
        header("Location: san_antonio.php");
        exit();
    }
}
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
    /* Allow the body to scroll so you can reach seat #49 */
    overflow-y: auto !important; 
    height: auto;
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
    /* Change height to auto or a large min-height to allow scrolling */
    height: auto; 
    min-height: calc(100vh - 70px);
    overflow-y: visible; 
}

        .header-row {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;
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
        #searchInput:focus { 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.15);
            width: 350px;
        }

     .map-grid-container { 
    background: rgba(255, 255, 255, 0.6); 
    backdrop-filter: blur(10px);
    padding: 1.5rem; border-radius: 30px; 
    border: 1px solid rgba(255,255,255,0.8); 
    flex-grow: 1;
    box-shadow: var(--shadow-soft); 
    /* CHANGED: Allow vertical scrolling if seats exceed height */
    overflow-y: auto; 
    display: block; 
}

.map-grid { 
    display: grid; 
    grid-template-columns: repeat(7, 1fr); 
    /* auto-rows ensures the grid grows as more seats are added */
    grid-auto-rows: minmax(100px, auto); 
    gap: 12px; 
    width: 100%;
    padding-bottom: 100px; /* Space for the legend at the bottom */
}
        .walkway {
            grid-column: span 7;
            background: transparent;
            pointer-events: none;
        }

        .seat-box {
            border-radius: 16px; text-align: center; border: 1px solid transparent; 
            background: var(--card-bg); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; display: flex; flex-direction: column; align-items: center; 
            justify-content: center; position: relative; box-shadow: var(--shadow-soft);
        }

        .seat-box:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: var(--shadow-hover);
            z-index: 10;
        }

        @keyframes pulse-vacant {
            0% { box-shadow: 0 0 0 0 rgba(148, 163, 184, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(148, 163, 184, 0); }
            100% { box-shadow: 0 0 0 0 rgba(148, 163, 184, 0); }
        }

        .Occupied { 
            background: var(--occupied-bg); color: var(--occupied-text); 
            border: 1px solid var(--occupied-border); 
        }

        .Vacant { 
            background: rgba(255, 255, 255, 0.6); 
            border: 1px solid var(--border);
            animation: pulse-vacant 2s infinite;
        }

        .Vacant:hover {
            border: 2px solid #fbbf24 !important;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }

        .status-legend {
            display: flex; justify-content: center; gap: 30px; margin-top: 1.5rem;
            background: white; padding: 0.8rem 2rem; border-radius: 50px;
            width: fit-content; margin-left: auto; margin-right: auto;
            box-shadow: var(--shadow-soft); border: 1px solid var(--border);
        }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }

        .dimmed { opacity: 0.15; filter: blur(2px); transform: scale(0.9); }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; animation: fadeIn 0.3s; }
        .modal-content { 
            background: #fff; width: 420px; padding: 2.5rem; border-radius: 32px; 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3);
        }

        /* --- EDIT SIDEBAR STYLES --- */
        .edit-sidebar {
            position: fixed; right: 20px; top: 90px;
            background: white; padding: 20px; border-radius: 20px;
            box-shadow: var(--shadow-hover); border: 1px solid var(--border);
            z-index: 100; display: flex; flex-direction: column; gap: 10px;
            transition: all 0.3s ease;
        }
        .edit-mode-active .seat-box {
            cursor: grab;
            border: 2px dashed var(--primary) !important;
        }
        .edit-mode-active .seat-box:active { cursor: grabbing; }
        .drag-over {
            background: #fff7ed !important;
            transform: scale(1.1) !important;
            border: 2px solid var(--primary) !important;
        }
        .switch {
            position: relative; display: inline-block; width: 50px; height: 24px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn" title="Back to Overview">
        <i class="fa-solid fa-circle-arrow-left"></i>
    </a>
    <div style="color: white; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.02em;">OJTBox | San Antonio Dashboard</div>
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
    <div style="font-size: 0.65rem; color: var(--text-muted); max-width: 120px; line-height: 1.4;">
        Drag one cubicle onto another to swap hosts.
    </div>
</div>

<div class="container">
    <div class="header-row">
        <div>
            <h1>San Antonio Floor Plan</h1>
        </div>
        <div class="search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid" id="mapGrid">
    <?php 
    // We want to display exactly 49 seats
    for ($i = 1; $i <= 49; $i++): 
        $cubicle_key = "SA-" . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        // Check if this seat exists in our database array
        if (isset($stations[$cubicle_key])) {
            $row = $stations[$cubicle_key];
            $id = $row['id'];
            $status = $row['status'];
            $hostname = $row['hostname'];
            $switch_port = $row['switch_port'];
        } else {
            // Placeholder data for seats not yet in the database
            $id = $i; 
            $status = 'Vacant';
            $hostname = '';
            $switch_port = 'Not Set';
        }
    ?>
        <div class="seat-box <?php echo $status; ?>" 
             id="seat-<?php echo $id; ?>"
             data-id="<?php echo $id; ?>"
             data-hostname="<?php echo strtolower($hostname); ?>"
             onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_key; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
            
            <strong style="font-size: 0.85rem; display:block;"><?php echo $cubicle_key; ?></strong>
            <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600;"><?php echo $switch_port; ?></div>
            <div style="font-size: 0.75rem; font-weight: 700; margin-top:4px;"><?php echo $hostname ?: 'Available'; ?></div>
        </div>

        <?php 
        // Logic for walkways every 14 seats (2 rows of 7)
        if ($i % 14 == 0 && $i < 49) {
            echo '<div class="walkway"></div>';
        }
        ?>
    <?php endfor; ?>
</div>
    </div>

    <div class="status-legend">
        <div class="legend-item">
            <div class="dot" style="background: #22c55e; box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);"></div>
            Occupied: <span style="color: #166534;"><?php echo $occupied_count; ?></span>
        </div>
        <div class="legend-item">
            <div class="dot" style="background: #cbd5e1; box-shadow: 0 0 8px rgba(148, 163, 184, 0.5);"></div>
            Vacant: <span style="color: #64748b;"><?php echo $vacant_count; ?></span>
        </div>
        <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 20px; color: var(--text-dark);">
            Total: 49
        </div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalHeader" style="margin-top:0; color:var(--text-dark); font-size:1.6rem; font-weight:800;">Update Station</h2>
        <div style="margin-top: 10px; text-align: center;">
    <a id="viewInInventory" href="#" target="_blank" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 700;">
        <i class="fa-solid fa-external-link"></i> View Full Asset Details
    </a>
</div>
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

    function handleDragOver(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }

    function handleDragLeave() {
        this.classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        this.style.opacity = '1';
        
        const sourceId = e.dataTransfer.getData('sourceId');
        const targetId = this.getAttribute('data-id');

        if (sourceId !== targetId) {
            performSwap(sourceId, targetId);
        }
        
        document.querySelectorAll('.seat-box').forEach(s => s.style.opacity = '1');
    }

    function performSwap(src, tgt) {
        // Function to update the inventory via the CRUD API
async function notifyInventoryAPI(hostname, cubicle, action) {
    const data = {
        action: 'update_location', // You can add a custom action to your inventory_crud.php
        hostname: hostname,
        cubicle_number: cubicle,
        location: (action === 'Vacant' ? 'WFH' : 'Onsite'),
        department: 'San Antonio'
    };

    await fetch('inventory_crud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
}
        const formData = new FormData();
        formData.append('swap_seats', true);
        formData.append('source_id', src);
        formData.append('target_id', tgt);

        fetch('san_antonio.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload(); 
            }
        });
    }

    function handleSeatClick(event, id, cubicle, host, sw, status) {
        if (isEditMode) return; 
        openEdit(id, cubicle, host, sw, status);
        const link = document.getElementById('viewInInventory');
    if(host) {
        link.style.display = 'inline';
        link.href = `inventory_view.php?search=${host}`; // Point this to your inventory search/view page
    } else {
        link.style.display = 'none';
    }
    }

    function openEdit(id, cubicle, host, sw, status) {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatSwitch').value = sw === 'Not Set' ? '' : sw;
        document.getElementById('seatStatus').value = status;
        document.getElementById('modalHeader').innerText = "Manage " + cubicle;
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
        let input = document.getElementById('searchInput').value.toLowerCase();
        let seats = document.getElementsByClassName('seat-box');
        for (let i = 0; i < seats.length; i++) {
            let host = seats[i].getAttribute('data-hostname');
            if (host.includes(input)) {
                seats[i].classList.remove('dimmed');
            } else {
                seats[i].classList.add('dimmed');
            }
        }
    }
</script>

</body>
</html>