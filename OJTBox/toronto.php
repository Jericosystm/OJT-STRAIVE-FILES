<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$department_name = "Toronto"; 

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
        header("Location: toronto.php");
        exit();
    }
}

$stations = []; 
$occupied_count = 0;
$vacant_count = 0;
$total_cubicles = 55;

$stmt = $conn->prepare("SELECT * FROM production_floor_map WHERE department = ? ORDER BY id ASC");
$stmt->bind_param("s", $department_name);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $stations[] = $row; 
    if($row['status'] === 'Occupied') $occupied_count++;
    else $vacant_count++;
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
        
        .navbar { background: var(--nav-green); padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .nav-back-btn { color: #1e293b; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
        .nav-back-btn:hover { transform: scale(1.1); }

        .container { height: calc(100vh - 70px); padding: 1.5rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1400px; margin: 0 auto; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header-row h1 { font-weight: 800; font-size: 1.8rem; margin: 0; background: linear-gradient(to right, #0f172a, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        #searchInput { width: 320px; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 14px; border: 1px solid var(--border); background: white; outline: none; box-shadow: var(--shadow-soft); transition: all 0.3s ease; }
        #searchInput:focus { width: 350px; border-color: var(--primary); }

        .map-grid-container { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 30px; flex-grow: 1; display: flex; align-items: center; justify-content: center; overflow-y: auto; box-shadow: var(--shadow-soft); border: 1px solid rgba(255,255,255,0.8); }
        .map-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; width: 100%; max-width: 1100px; padding-bottom: 20px; }

        .seat-box { height: 75px; border-radius: 12px; text-align: center; background: var(--card-bg); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow-soft); border: 1px solid var(--border); }
        .seat-box:hover:not(.pillar) { transform: translateY(-5px) scale(1.03); box-shadow: var(--shadow-hover); z-index: 10; border-color: var(--primary); }

        .pillar { background: #334155 !important; color: white !important; cursor: not-allowed !important; border: none; font-weight: 800; font-size: 0.7rem; pointer-events: none; }

        .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
        .Vacant { background: white; }

        .dimmed { opacity: 0.15; filter: blur(2px); transform: scale(0.9); }
        .drag-over { background: #e0f2fe !important; transform: scale(1.1) !important; border: 2px solid var(--primary) !important; }
        .edit-mode-active .seat-box:not(.pillar) { cursor: grab; border: 2px dashed var(--primary) !important; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; }
        .modal-content { background: #fff; width: 400px; padding: 2.5rem; border-radius: 32px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3); }

        .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow-hover); z-index: 100; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 8px; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body id="body">

<nav class="navbar">
    <a href="prod_map.php" class="nav-back-btn"><i class="fa-solid fa-circle-arrow-left"></i></a>
    <div style="font-weight: 900; font-size: 1.4rem; color: #1e293b;">OJTBox | Toronto Dashboard</div>
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
        <h1>Toronto Floor Plan</h1>
        <div style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;"></i>
            <input type="text" id="searchInput" placeholder="Search hostname..." onkeyup="searchMap()">
        </div>
    </div>

    <div class="map-grid-container">
        <div class="map-grid">
            <?php 
            $seat_index = 0;
            // We need enough cells to represent the rows (8 columns wide)
            // Rows 1-5 = 40 cells. Rows 6+ use only 4 cols, so we iterate until all 55 seats are placed.
            $current_cell = 1;

            while ($seat_index < $total_cubicles || $current_cell <= 40) {
                // Determine Row and Column
                $row_number = ceil($current_cell / 8);
                $col_number = ($current_cell - 1) % 8 + 1;

                // RULE: Rows 6 and above only occupy first 4 columns
                if ($row_number >= 6 && $col_number > 4) {
                    echo '<div></div>'; // Spacer for empty columns
                    $current_cell++;
                    continue;
                }

                // Place Pillar at Cell 29 (Row 4, Col 5)
                if ($current_cell == 29) {
                    echo '<div class="seat-box pillar">PILLAR</div>';
                    $current_cell++;
                    continue; 
                }

                // Stop if we ran out of database records
                if ($seat_index >= $total_cubicles) {
                    echo '<div></div>';
                    $current_cell++;
                    continue;
                }

                $row = $stations[$seat_index];
                $id = $row['id'];
                $status = $row['status'];
                $hostname = $row['hostname'];
                $port = $row['campaign'] ?? 'Not Set';
                $cubicle_label = "TOR-" . str_pad($seat_index + 1, 4, '0', STR_PAD_LEFT);
                
                $seat_index++;
            ?>
                <div class="seat-box <?php echo $status; ?>" 
                     id="seat-<?php echo $id; ?>"
                     data-id="<?php echo $id; ?>"
                     data-hostname="<?php echo strtolower($hostname); ?>"
                     onclick="handleSeatClick(this, '<?php echo $id; ?>', '<?php echo $cubicle_label; ?>', '<?php echo $hostname; ?>', '<?php echo $port; ?>', '<?php echo $status; ?>')">
                    
                    <strong style="font-size: 0.75rem;"><?php echo $cubicle_label; ?></strong>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 600;"><?php echo $port; ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; margin-top:2px;"><?php echo $hostname ?: 'Available'; ?></div>
                </div>
            <?php 
                $current_cell++;
            } 
            ?>
        </div>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin:0 0 1.5rem; font-weight:800; color:var(--text-dark);">Update Station</h2>
        <form method="POST">
            <input type="hidden" name="id" id="seatId">
            <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Cubicle</label>
            <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border); background:#f8fafc; font-weight:700;">

            <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Switch Port</label>
            <input type="text" name="switch_port" id="seatSwitch" style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border);">

            <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Status</label>
            <select name="status" id="seatStatus" onchange="toggleHostnameField()" style="width:100%; padding:0.8rem; margin:0.5rem 0 1rem; border-radius:12px; border:1px solid var(--border);">
                <option value="Occupied">Occupied</option>
                <option value="Vacant">Vacant</option>
            </select>

            <div id="hostnameWrapper">
                <label style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Hostname</label>
                <input type="text" name="hostname" id="seatHost" style="width:100%; padding:0.8rem; margin:0.5rem 0 1.5rem; border-radius:12px; border:1px solid var(--border);">
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="submit" name="update_seat" style="flex:2; padding: 1rem; background: var(--primary); color:white; border:none; border-radius:14px; font-weight:800; cursor:pointer;">UPDATE</button>
                <button type="button" onclick="closeModal()" style="flex:1; padding: 1rem; background:#f1f5f9; color:#64748b; border:none; border-radius:14px; font-weight:700; cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditMode = false;

    window.onload = function() {
        const savedMode = localStorage.getItem('swapModeToronto');
        if (savedMode === 'true') {
            document.getElementById('editToggle').checked = true;
            toggleEditMode();
        }
    };

    function toggleEditMode() {
        isEditMode = document.getElementById('editToggle').checked;
        localStorage.setItem('swapModeToronto', isEditMode);
        
        const body = document.getElementById('body');
        const label = document.getElementById('statusLabel');
        const seats = document.querySelectorAll('.seat-box:not(.pillar)');

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
            const formData = new FormData();
            formData.append('swap_seats', true);
            formData.append('source_id', sourceId);
            formData.append('target_id', targetId);
            fetch('toronto.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => { if(data.success) location.reload(); });
        }
    }

    function handleSeatClick(element, id, cubicle, host, sw, status) {
        if (isEditMode) return;
        if (element.classList.contains('pillar')) return;

        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('seatId').value = id;
        document.getElementById('seatCubicle').value = cubicle;
        document.getElementById('seatHost').value = host;
        document.getElementById('seatSwitch').value = sw === 'Not Set' ? '' : sw;
        document.getElementById('seatStatus').value = status;
        toggleHostnameField();
    }

    function toggleHostnameField() {
        const status = document.getElementById('seatStatus').value;
        const hostInput = document.getElementById('seatHost');
        if(status === 'Vacant') {
            hostInput.value = '';
            hostInput.disabled = true;
            hostInput.style.background = '#f1f5f9';
        } else {
            hostInput.disabled = false;
            hostInput.style.background = '#fff';
        }
    }

    function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }

    function searchMap() {
        let val = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.seat-box:not(.pillar)').forEach(s => {
            const host = s.getAttribute('data-hostname') || '';
            s.classList.toggle('dimmed', val && !host.includes(val));
        });
    }
</script>
</body>
</html>