    <?php
    session_start();
    require_once 'db.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $department_name = "Atlanta"; 
    $total_seats = 99;

    /**
     * HELPER: Syncs hostname location to inventory
     */
function syncInventory($conn, $hostname, $cubicle, $dept, $status, $switch_port = '') {        $hostname = !empty($hostname) ? strtoupper(trim($hostname)) : 'N/A';
        $cubicle  = strtoupper(trim($cubicle)); 
        $dept     = trim($dept);
        $location = ($status === 'Occupied' && $hostname !== 'N/A') ? 'Onsite' : 'N/A';

        $stmt = $conn->prepare("UPDATE inventory_items SET hostname = ?, department = ?, location = ?, switch_port = ? WHERE UPPER(trim(cubicle_number)) = ?");
        $stmt->bind_param("sssss", $hostname, $dept, $location, $switch_port, $cubicle);    $stmt->execute();
    }

    // --- SWAP HANDLER ---
    if(isset($_POST['swap_seats'])) {
        $sourceId = $_POST['source_id'];
        $targetId = $_POST['target_id'];

        $conn->begin_transaction();

        try {
            // 1. Fetch current data for both seats
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

            // 2. SWAP ON FLOOR MAP
            $updateMap = $conn->prepare("UPDATE production_floor_map SET hostname=?, status=? WHERE id=?");
            // Move Target info to Source ID
            $updateMap->bind_param("ssi", $tgtHost, $targetMap['status'], $sourceId);
            $updateMap->execute();
            // Move Source info to Target ID
            $updateMap->bind_param("ssi", $srcHost, $sourceMap['status'], $targetId);
            $updateMap->execute();

            // 3. SWAP IN INVENTORY (The Fixed Sync)
            // We use a unique temporary hostname to break the duplication link
            $tempHostSuffix = "_SWAP_" . time();

            if (!empty($srcHost)) {
    // Get the physical port of the TARGET desk
    $stmtP1 = $conn->prepare("SELECT switch_port FROM production_floor_map WHERE cubicle_no = ?");
    $stmtP1->bind_param("s", $tgtCubicle);
    $stmtP1->execute();
    $tgtPort = $stmtP1->get_result()->fetch_assoc()['switch_port'] ?? '';

    // Move Source Host to Target Desk + Target Port
    $tmpName = $srcHost . $tempHostSuffix;
    $upd1 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, hostname = ?, switch_port = ? WHERE hostname = ?");
    $upd1->bind_param("ssss", $tgtCubicle, $tmpName, $tgtPort, $srcHost);
    $upd1->execute();
}

if (!empty($tgtHost)) {
    // Get the physical port of the SOURCE desk
    $stmtP2 = $conn->prepare("SELECT switch_port FROM production_floor_map WHERE cubicle_no = ?");
    $stmtP2->bind_param("s", $srcCubicle);
    $stmtP2->execute();
    $srcPort = $stmtP2->get_result()->fetch_assoc()['switch_port'] ?? '';

    // Move Target Host to Source Desk + Source Port
    $upd2 = $conn->prepare("UPDATE inventory_items SET cubicle_number = ?, switch_port = ? WHERE hostname = ?");
    $upd2->bind_param("sss", $srcCubicle, $srcPort, $tgtHost);
    $upd2->execute();
}

if (!empty($srcHost)) {
    // Restore original hostname
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
    // --- UPDATE HANDLER ---
    if(isset($_POST['update_seat'])) {
        $id = $_POST['id'];
        $hostname = trim($_POST['hostname']);
        
        // 1. DEFINE THE VARIABLE HERE (This fixes the red line)
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
                // 2. PASS THE VARIABLE HERE
                syncInventory($conn, 'N/A', $cubicle_name, $department_name, 'Vacant', '');
            }
            // 3. AND PASS IT HERE
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-dark); overflow: hidden; }
            
            .navbar { background: #90ee90; padding: 0 2.5rem; display: flex; align-items: center; height: 70px; box-sizing: border-box; gap: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; z-index: 10; }
            .nav-back-btn { color: #15803d; text-decoration: none; font-size: 1.5rem; display: flex; align-items: center; transition: transform 0.2s; }
            .nav-back-btn:hover { transform: scale(1.1); }

            .container { height: calc(100vh - 70px); padding: 1.5rem 2rem; display: flex; flex-direction: column; box-sizing: border-box; max-width: 1600px; margin: 0 auto; }
            .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
            .header-row h1 { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.03em; margin: 0; background: linear-gradient(to right, #0f172a, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

            #searchInput { width: 320px; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 14px; border: 1px solid var(--border); background: white; font-size: 0.9rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); outline: none; box-shadow: var(--shadow-soft); }
            #searchInput:focus { border-color: var(--primary); width: 350px; }

            .map-grid-container { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 30px; border: 1px solid rgba(255,255,255,0.8); flex-grow: 1; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-soft); overflow: auto; }
            
            /* Atlanta Grid: 11 columns plus a 15px divider at column 9 */
            .map-grid { 
                display: grid; 
                grid-template-columns: repeat(8, 1fr) 15px repeat(3, 1fr); 
                grid-template-rows: repeat(9, 1fr); 
                gap: 10px; 
                width: 100%; 
                height: 100%; 
                max-width: 1400px;
            }

            .vertical-divider {
                grid-column: 9;
                grid-row: 1 / span 9;
                background: #1e293b;
                width: 4px;
                justify-self: center;
                border-radius: 10px;
                margin: 10px 0;
            }

            .seat-box { border-radius: 12px; text-align: center; border: 1px solid transparent; background: var(--card-bg); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow-soft); padding: 5px; }
            .seat-box:hover { transform: translateY(-3px) scale(1.02); box-shadow: var(--shadow-hover); z-index: 10; border-color: #facc15; }

            .Occupied { background: var(--occupied-bg); color: var(--occupied-text); border: 1px solid var(--occupied-border); }
            .Vacant { background: rgba(255, 255, 255, 0.6); border: 1px solid var(--border); }

            .edit-sidebar { position: fixed; right: 20px; top: 90px; background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow-hover); border: 1px solid var(--border); z-index: 100; display: flex; flex-direction: column; gap: 10px; }
            .edit-mode-active .seat-box { cursor: grab; border: 2px dashed var(--primary) !important; }
            
            .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: var(--primary); }
            input:checked + .slider:before { transform: translateX(26px); }

            .status-legend { display: flex; justify-content: center; gap: 30px; margin-top: 1rem; background: white; padding: 0.6rem 2rem; border-radius: 50px; width: fit-content; margin-left: auto; margin-right: auto; box-shadow: var(--shadow-soft); border: 1px solid var(--border); }
            .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.85rem; }
            .dot { width: 10px; height: 10px; border-radius: 50%; }
            .dimmed { opacity: 0.15; filter: blur(2px); transform: scale(0.9); }

            /* Modal Styles matching San Antonio */
            .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; animation: fadeIn 0.3s; }
            .modal-content { 
                background: #fff; width: 420px; padding: 2.5rem; border-radius: 32px; 
                position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                box-shadow: 0 30px 60px -12px rgba(0,0,0,0.3);
            }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        </style>
    </head>
    <body id="body">

    <nav class="navbar">
        <a href="prod_map.php" class="nav-back-btn" title="Back to Overview">
            <i class="fa-solid fa-circle-arrow-left"></i>
        </a>
        <div style="color: #15803d; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.02em;">OJTBox | Atlanta Dashboard</div>
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
            <div><h1>Atlanta Floor Plan</h1></div>
            <div class="search-wrapper" style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 5;"></i>
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
                    $tooltip = "Cubicle: $cubicle_name\nStatus: $status\nHostname: " . ($hostname ?: 'None');

                    // Determine Grid Position to account for the divider column
                    $col = ($i % 11) + 1;
                    $grid_col = ($col > 8) ? $col + 1 : $col;
                ?>
                    <div class="seat-box <?php echo $status; ?>" 
                        id="seat-<?php echo $id ?: 'temp-'.$i; ?>"
                        data-id="<?php echo $id; ?>"
                        data-hostname="<?php echo strtolower($hostname); ?>"
                        title="<?php echo $tooltip; ?>"
                        style="grid-column: <?php echo $grid_col; ?>;"
                        onclick="handleSeatClick(event, '<?php echo $id; ?>', '<?php echo $cubicle_name; ?>', '<?php echo $hostname; ?>', '<?php echo $switch_port; ?>', '<?php echo $status; ?>')">
                        <strong style="font-size: 0.75rem; display:block;"><?php echo $cubicle_name; ?></strong>
                        <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 600;"><?php echo $switch_port; ?></div>
                        <div style="font-size: 0.7rem; font-weight: 700; margin-top:2px;"><?php echo $hostname ?: 'Available'; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="status-legend">
            <div class="legend-item">
                <div class="dot" style="background: #22c55e;"></div>
                Occupied: <span style="color: #166534;"><?php echo $occupied_count; ?></span>
            </div>
            <div class="legend-item">
                <div class="dot" style="background: #cbd5e1;"></div>
                Vacant: <span style="color: #64748b;"><?php echo $vacant_count; ?></span>
            </div>
            <div class="legend-item" style="border-left: 1px solid var(--border); padding-left: 20px;">
                Total: 99
            </div>
        </div>
    </div>

        <div id="modalOverlay" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalHeader" style="margin:0 0 1.5rem; font-weight:800;">Station Details</h2>
            
            <form>
                <input type="hidden" id="seatId">
                
                <label style="font-size:0.7rem; font-weight:700; display:block; margin-bottom:5px;">CUBICLE</label>
                <input type="text" id="seatCubicle" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; box-sizing: border-box;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">PORT</label>
                    <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                </div>
                <input type="text" id="seatSwitch" name="switch_port" style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; color: var(--text-muted); box-sizing: border-box;">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">STATUS</label>
                    <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                </div>
                <select id="seatStatus" readonly style="width:100%; padding:0.7rem; margin-bottom:1rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; pointer-events: none; color: var(--text-muted); box-sizing: border-box;">
                    <option value="Occupied">Occupied</option>
                    <option value="Vacant">Vacant</option>
                </select>

                <div id="hostnameWrapper">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label style="font-size:0.7rem; font-weight:700; color: var(--text-muted);">HOSTNAME</label>
                        <i class="fa-solid fa-lock" style="font-size: 0.6rem; color: var(--text-muted);"></i>
                    </div>
                    <input type="text" id="seatHost" readonly style="width:100%; padding:0.7rem; margin-bottom:1.5rem; border-radius:10px; border:1px solid var(--border); background:#f8fafc; color: var(--text-muted); box-sizing: border-box;">
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
                seats.forEach(seat => seat.setAttribute('draggable', false));
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

        // Modal Logic
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
            for (let seat of seats) {
                let host = seat.getAttribute('data-hostname');
                seat.classList.toggle('dimmed', !host.includes(input));
            }
        }
    </script>

    </body>
    </html>