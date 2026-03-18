<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'euc_admin'; 
$back_link = ($user_role === 'euc_admin') ? 'index_admin.php' : 'index_user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php'; 

// 1. Fetch ALL Cubicles
$cubicles = [];
$cubicles_result = $conn->query("SELECT id, cubicle_no, status, hostname FROM production_floor_map ORDER BY cubicle_no ASC");
if ($cubicles_result) {
    while($row = $cubicles_result->fetch_assoc()) {
        $cubicles[] = $row; 
    }
}

// 2. Search and Tab Logic
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$status_map = ['all' => 'All', 'inventory' => 'Active', 'storage' => 'Vacant', 'dispose' => 'Dispose'];
$target_status = $status_map[$current_tab] ?? 'Active';

$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $sql = "SELECT i.*, p.department AS switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no WHERE (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ?) ORDER BY i.updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$like_param, $like_param, $like_param, $like_param];
        $types = "ssss";
    } else {
        $sql = "SELECT i.*, p.switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no ORDER BY i.updated_at DESC";
    }
} else {
    if (!empty($search_query)) {
        $sql = "SELECT i.*, p.department AS switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no WHERE i.status = ? AND (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ?) ORDER BY i.updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$target_status, $like_param, $like_param, $like_param, $like_param];
        $types = "sssss";
    } else {
        $sql = "SELECT i.*, p.department AS switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no WHERE i.status = ? ORDER BY i.updated_at DESC";
        $params = [$target_status];
        $types = "s";
    }
}

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | Asset Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --neon-green: #00ff99;
            --text-gray: #a0a0a0;
            --border-color: #222222;
            --transition-speed: 0.4s;
        }

        /* Initial Page Fade-in Effect */
        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Force Dark Mode Background even if header is light */
        body {
            background-color: var(--bg-dark) !important;
            color: #ffffff !important;
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            animation: fadeInPage 0.8s ease-out;
        }

        .inventory-container {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-section { margin-bottom: 40px; }
        .header-section h4 {
            color: var(--primary-orange) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
            font-size: 0.8rem;
        }
        .header-section h1 { font-size: 2.5rem; margin: 0; font-weight: 700; color: #ffffff !important; }

        .tab-wrapper { display: flex; gap: 15px; margin-top: 25px; }

        /* Smooth Tab Buttons */
        .tab-btn {
            background: #1a1a1a !important;
            color: white !important;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid var(--border-color) !important;
            transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-btn:hover {
            border-color: var(--primary-orange) !important;
            background: rgba(255, 102, 0, 0.05) !important;
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: var(--primary-orange) !important;
            border-color: var(--primary-orange) !important;
            box-shadow: 0 5px 20px rgba(255, 102, 0, 0.4);
            color: white !important;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Interactive Search Box */
        .search-box form {
            display: flex;
            background: #151515 !important;
            border-radius: 10px;
            padding: 5px;
            border: 1px solid var(--border-color) !important;
            transition: border-color 0.3s ease;
        }
        .search-box form:focus-within {
            border-color: var(--primary-orange) !important;
            box-shadow: 0 0 10px rgba(255, 102, 0, 0.1);
        }
        .search-box input {
            background: transparent !important;
            border: none;
            color: white !important;
            padding: 10px 15px;
            outline: none;
            width: 300px;
        }

        .btn-add {
            background: var(--primary-orange) !important;
            color: white !important;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(255, 102, 0, 0.2);
        }
        .btn-add:hover {
            filter: brightness(1.2);
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(255, 102, 0, 0.4);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            background: transparent !important;
        }

        .data-table th {
            text-align: left;
            color: var(--text-gray) !important;
            padding: 0 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        /* Table Row Transitions */
        .data-table tr {
            background: var(--card-bg) !important;
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            transform: translateX(10px) scale(1.01);
            background: #1a1a1a !important;
            box-shadow: -5px 0 0 var(--primary-orange), 0 10px 30px rgba(0,0,0,0.5);
        }

        .data-table td {
            padding: 20px;
            border-top: 1px solid var(--border-color) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: white !important;
        }

        .data-table td:first-child { border-left: 1px solid var(--border-color) !important; border-radius: 12px 0 0 12px; }
        .data-table td:last-child { border-right: 1px solid var(--border-color) !important; border-radius: 0 12px 12px 0; }

        /* Animated Status Dot */
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 rgba(0, 255, 153, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(0, 255, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 255, 153, 0); }
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            background: var(--neon-green);
            animation: pulseGlow 2s infinite;
        }

        .node-icon {
            background: #1a1a1a !important;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            border: 1px solid #333 !important;
            transition: 0.3s ease;
            color: white !important;
        }
        tr:hover .node-icon {
            border-color: var(--primary-orange) !important;
            color: var(--primary-orange) !important;
        }

        .asset-main { font-weight: 700; font-size: 1.1rem; color: #ffffff !important; }
        .sub-text { color: var(--text-gray) !important; font-size: 0.8rem; display: block; }

        .action-btn {
            background: #222 !important;
            border: 1px solid #333 !important;
            color: white !important;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 5px;
            transition: all 0.3s ease;
        }
        .btn-edit:hover { background: var(--primary-orange) !important; border-color: var(--primary-orange) !important; transform: scale(1.1); }
        .btn-delete:hover { background: #ff4444 !important; border-color: #ff4444 !important; transform: scale(1.1); }

        /* Modal Overlay Transition */
        .modal {
            display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85) !important; backdrop-filter: blur(10px);
            opacity: 0; transition: opacity 0.3s ease;
        }
        .modal.show { opacity: 1; display: flex; align-items: center; justify-content: center; }

        .modal-content {
            background: #111 !important; border: 1px solid #333 !important; padding: 35px;
            width: 90%; max-width: 500px; border-radius: 20px;
            transform: scale(0.8); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            color: white !important;
        }
        .modal.show .modal-content { transform: scale(1); }

        .form-control {
            background: #1a1a1a !important; border: 1px solid #333 !important; color: white !important;
            width: 100%; padding: 12px; border-radius: 8px; margin: 10px 0;
            box-sizing: border-box; transition: all 0.3s ease;
        }
        .form-control:focus { border-color: var(--primary-orange) !important; outline: none; background: #222 !important; }

        .loc-badge {
            background: rgba(0, 255, 153, 0.1) !important;
            color: var(--neon-green) !important;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: bold;
            border: 1px solid rgba(0, 255, 153, 0.2) !important;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <section class="header-section">
            <h4>Network Architecture</h4>
            <h1>Inventory Nodes</h1>
            
            <div class="tab-wrapper">
                <a href="?tab=all" class="tab-btn <?php echo $current_tab == 'all' ? 'active' : ''; ?>">All Assets</a>
                <a href="?tab=inventory" class="tab-btn <?php echo $current_tab == 'inventory' ? 'active' : ''; ?>">Active</a>
                <a href="?tab=storage" class="tab-btn <?php echo $current_tab == 'storage' ? 'active' : ''; ?>">Storage</a>
                <a href="?tab=dispose" class="tab-btn <?php echo $current_tab == 'dispose' ? 'active' : ''; ?>">Dispose</a>
            </div>
        </section>

        <div class="header-flex">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
                    <input type="text" name="search" placeholder="Search workstations..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="background:transparent; border:none; color:var(--primary-orange); cursor:pointer; padding: 0 15px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>
            <button onclick="openModal()" class="btn-add">+ Initialize New Asset</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Workstation Details</th>
                    <th>Network / Serial</th>
                    <th>Location Info</th>
                    <th>Status / Log</th>
                    <th style="text-align: center;">Command</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($item = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="node-icon"><i class="fa-solid fa-server"></i></div>
                            <span class="asset-main"><?php echo htmlspecialchars($item['asset_name']); ?></span>
                            <span class="sub-text">Type: <?php echo htmlspecialchars($item['device_type']); ?></span>
                        </td>
                        <td>
                            <span class="asset-main" style="color:var(--text-gray)"><?php echo htmlspecialchars($item['hostname']); ?></span>
                            <span class="sub-text" style="color:var(--primary-orange)">SN: <?php echo htmlspecialchars($item['serial_num']); ?></span>
                        </td>
                        <td>
                            <?php if(($item['location'] ?? '') == 'Onsite'): ?>
                                <span class="loc-badge">ONSITE</span>
                                <span class="sub-text" style="margin-top:5px;"><?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?></span>
                                <span class="sub-text">Cubicle: <?php echo htmlspecialchars($item['cubicle_number'] ?? 'N/A'); ?></span>
                            <?php else: ?>
                                <span class="loc-badge" style="color:#aaa; background:#222; border-color:#444;">REMOTE / WFH</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <span class="status-dot"></span>
                                <span style="font-weight:bold; font-size:0.8rem;"><?php echo strtoupper($item['status']); ?></span>
                            </div>
                            <span class="sub-text" style="margin-top:5px;">Last Update:</span>
                            <span class="sub-text" style="color:var(--primary-orange)"><?php echo date('M d, Y H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></span>
                        </td>
                        <td style="text-align: center;">
                            <button title="Edit" onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn btn-edit"><i class="fa-solid fa-terminal"></i></button>
                            <button title="Delete" onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--text-gray); padding:50px;">NO ACTIVE NODES DETECTED.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="assetModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="color:var(--primary-orange); margin-top:0;">Node Initialization</h2>
            <form id="assetForm">
                <input type="hidden" id="assetId">
                
                <label class="sub-text">ASSET IDENTIFIER</label>
                <input type="text" id="assetName" class="form-control" required>
                
                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label class="sub-text">HOSTNAME</label>
                        <input type="text" id="hostName" class="form-control" required>
                    </div>
                    <div style="flex:1">
                        <label class="sub-text">SERIAL NUM</label>
                        <input type="text" id="serialNum" class="form-control" required>
                    </div>
                </div>

                <label class="sub-text">DEPLOYMENT ZONE</label>
                <select id="location" class="form-control" onchange="toggleLocationFields()" required>
                    <option value="WFH">Remote / WFH</option>
                    <option value="Onsite">Onsite Production</option>
                </select>

                <div id="onsiteInfo" style="display:none; background: #000; padding: 15px; border-radius: 10px; border: 1px solid #333; margin-bottom:15px; animation: fadeInPage 0.3s ease;">
                   <label class="sub-text">DEPARTMENT</label>
                   <select id="department" class="form-control">
                        <option value="NATGEN">NATGEN</option>
                        <option value="LN ECRASH">LN ECRASH</option>
                        <option value="LN ELSEVIER">LN ELSEVIER</option>
                   </select>
                   <label class="sub-text">CUBICLE ASSIGNMENT</label>
                   <input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search DAL-XXXX..." oninput="syncCubicleId()">
                   <input type="hidden" id="selectedCubicleId">
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label class="sub-text">NODE TYPE</label>
                        <select id="deviceType" class="form-control">
                            <option>Desktop</option><option>Laptop</option><option>Server</option>
                        </select>
                    </div>
                    <div style="flex:1">
                        <label class="sub-text">OPERATIONAL STATUS</label>
                        <select id="status" class="form-control">
                            <option value="Active">Operational</option>
                            <option value="Vacant">Standby</option>
                            <option value="Dispose">Decommission</option>
                        </select>
                    </div>
                </div>

                <button type="submit" id="saveBtn" class="btn-add" style="width:100%; margin-top:20px;">EXECUTE COMMAND</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:var(--text-gray); cursor:pointer; margin-top:10px;">Abort</button>
            </form>
        </div>
    </div>

    <datalist id="cubicleList">
        <?php foreach($cubicles as $c): ?>
            <option value="<?php echo htmlspecialchars($c['cubicle_no']); ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <script>
        const cubicleData = <?php echo json_encode($cubicles); ?>;
        const modal = document.getElementById('assetModal');
        
        function toggleLocationFields() {
            const loc = document.getElementById('location').value;
            const info = document.getElementById('onsiteInfo');
            info.style.display = (loc === 'Onsite') ? 'block' : 'none';
        }

        function syncCubicleId() {
            const input = document.getElementById('cubicleNumber').value.toUpperCase();
            const match = cubicleData.find(c => c.cubicle_no.toUpperCase() === input);
            document.getElementById('selectedCubicleId').value = match ? match.id : "";
        }

        function openModal() {
            document.getElementById('assetId').value = '';
            document.getElementById('assetForm').reset();
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeModal() { 
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Close modal when clicking outside content
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        async function editAsset(item) {
            document.getElementById('assetId').value = item.id;
            document.getElementById('assetName').value = item.asset_name;
            document.getElementById('hostName').value = item.hostname;
            document.getElementById('serialNum').value = item.serial_num;
            document.getElementById('location').value = item.location || 'WFH';
            document.getElementById('status').value = item.status;
            document.getElementById('cubicleNumber').value = item.cubicle_number || '';
            toggleLocationFields();
            openModal();
        }

        document.getElementById('assetForm').onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                action: document.getElementById('assetId').value ? 'update' : 'create',
                id: document.getElementById('assetId').value,
                asset_name: document.getElementById('assetName').value,
                hostname: document.getElementById('hostName').value,
                serial_num: document.getElementById('serialNum').value,
                location: document.getElementById('location').value,
                status: document.getElementById('status').value,
                cubicle_number: document.getElementById('cubicleNumber').value,
                cubicle_id: document.getElementById('selectedCubicleId').value,
                device_type: document.getElementById('deviceType').value
            };

            const res = await fetch('inventory_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if(result.success) {
                closeModal();
                location.reload(); 
            } else {
                alert(result.message);
            }
        };

        function deleteAsset(id) {
            if(confirm('Are you sure you want to decommission this node?')) {
                // Logic for delete can be added here
            }
        }
    </script>
</body>
</html>