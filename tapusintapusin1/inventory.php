<?php
session_start();

$username = $_SESSION['username'] ?? 'User'; 
$user_email = $_SESSION['email'] ?? ''; 
$user_role = $_SESSION['role'] ?? 'euc_user'; 
$back_link = ($user_role === 'euc_admin') ? 'index_admin.php' : 'index_user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php'; 

// 1. Fetch ALL Cubicles for Datalist
$cubicles = [];
$cubicles_result = $conn->query("SELECT id, cubicle_no, status, hostname FROM production_floor_map ORDER BY cubicle_no ASC");
if ($cubicles_result) {
    while($row = $cubicles_result->fetch_assoc()) {
        $cubicles[] = $row; 
    }
}

// 2. Search and Tab Logic
$current_tab = $_GET['tab'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$status_map = ['all' => 'All', 'inventory' => 'Active', 'storage' => 'storage', 'dispose' => 'Dispose'];
$target_status = $status_map[$current_tab] ?? 'Active';

$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $like_param = "%$search_query%";
        $sql = "SELECT i.*, p.department AS floor_dept 
        FROM inventory_items i 
        LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no 
        WHERE (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ? OR i.agent_name LIKE ?) 
                ORDER BY i.updated_at DESC";
        $params = [$like_param, $like_param, $like_param, $like_param, $like_param];
        $types = "sssss";
    } else {
        $sql = "SELECT i.*, p.department AS switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no ORDER BY i.updated_at DESC";
    }
} else {
    if (!empty($search_query)) {
        $like_param = "%$search_query%";
        $sql = "SELECT i.*, p.department AS switch_port FROM inventory_items i LEFT JOIN production_floor_map p ON i.cubicle_number = p.cubicle_no WHERE i.status = ? AND (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ?) ORDER BY i.updated_at DESC";
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
            --text-main: #ffffff;
            --border-color: #222222;
            --input-bg: #1a1a1a;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0;
            animation: fadeInPage 0.8s ease-out;
        }

        @keyframes fadeInPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .inventory-container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .header-section h4 { color: var(--primary-orange); text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; margin-bottom: 5px; }
        .header-section h1 { font-size: 2.5rem; margin: 0; font-weight: 700; }

        .tab-wrapper { display: flex; gap: 15px; margin: 25px 0 40px 0; }
        .tab-btn {
            background: var(--input-bg); color: var(--text-main); text-decoration: none; padding: 10px 25px;
            border-radius: 8px; font-weight: 600; border: 1px solid var(--border-color);
            transition: 0.3s;
        }
        .tab-btn.active { background: var(--primary-orange); color: white; border-color: var(--primary-orange); box-shadow: 0 5px 20px rgba(255, 102, 0, 0.4); }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .search-box form { display: flex; background: var(--card-bg); border-radius: 10px; padding: 5px; border: 1px solid var(--border-color); align-items: center; }
        .search-box input { background: transparent; border: none; color: var(--text-main); padding: 10px 15px; outline: none; width: 300px; }
        
        .btn-add { background: var(--primary-orange); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; }

        .btn-export {
    background: transparent;
    color: var(--text-gray);
    border: 1px solid var(--border-color);
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s;
}

.btn-export:hover {
    background: var(--input-bg);
    color: var(--text-main);
    border-color: var(--text-gray);
}

        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .data-table th { text-align: left; color: var(--text-gray); padding: 0 20px; font-size: 0.75rem; text-transform: uppercase; }
        .data-table tr { background: var(--card-bg); transition: 0.3s; }
        .data-table td { padding: 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .data-table td:first-child { border-radius: 12px 0 0 12px; }
        .data-table td:last-child { border-radius: 0 12px 12px 0; text-align: center; }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; background: var(--neon-green); box-shadow: 0 0 10px var(--neon-green); }
        .loc-badge { background: rgba(128,128,128,0.1); color: var(--text-gray); padding: 4px 10px; border-radius: 5px; font-size: 0.7rem; font-weight: bold; border: 1px solid var(--border-color); }
        .loc-badge.active-loc { border-color: var(--neon-green); color: var(--neon-green); background: rgba(0, 255, 153, 0.1); }
        .loc-badge.inactive-loc { border-color: #ff4444; color: #ff4444; background: rgba(255, 68, 68, 0.1); }
        
        .node-icon { background: var(--input-bg); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px; border: 1px solid var(--border-color); float: left; color: var(--primary-orange); }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); align-items: center; justify-content: center; }
        .modal-content { background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; width: 90%; max-width: 500px; border-radius: 20px; max-height: 90vh; overflow-y: auto; }
        .form-control { background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); width: 100%; padding: 12px; border-radius: 8px; margin: 10px 0 20px 0; box-sizing: border-box; }
        .sub-text { color: var(--text-gray); font-size: 0.75rem; display: block; text-transform: uppercase; }
        .action-btn { background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); padding: 8px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <section class="header-section">
            <h4>OJTbox Straive Laguna</h4>
            <h1>Inventory Assets</h1>
            
            <div class="tab-wrapper">
                <?php foreach(['all' => 'All Assets', 'inventory' => 'Active', 'storage' => 'Storage', 'dispose' => 'Dispose'] as $key => $label): ?>
                    <a href="?tab=<?php echo $key; ?>" class="tab-btn <?php echo $current_tab == $key ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="header-flex">
    <div class="search-box">
        <form action="" method="GET">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
            <input type="text" name="search" placeholder="Query hostname or serial..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" style="background:none; border:none; color:var(--primary-orange); cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
    
    <div style="display:flex; gap:10px;">
        <a href="export_inventory.php?tab=<?php echo $current_tab; ?>&search=<?php echo urlencode($search_query); ?>" class="btn-export">
            <i class="fa-solid fa-file-export"></i> Export CSV
        </a>
        
        <button onclick="openModal()" class="btn-add">+ Initialize New Node</button>
    </div>
</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Workstation Details</th>
                    <th>Network / Serial</th>
                    <th>Location Info</th>
                    <th>Status / Log</th>
                    <th>Command</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($item = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="node-icon"><i class="fa-solid fa-microchip"></i></div>
                            <div>
                                <span style="font-weight:700; display:block;"><?php echo htmlspecialchars($item['asset_name']); ?></span>
                                <span class="sub-text"><?php echo htmlspecialchars($item['device_type']); ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="display:block; font-family:monospace;"><?php echo htmlspecialchars($item['hostname'] ?? 'NO-HOST'); ?></span>
                            <span class="sub-text" style="color:var(--primary-orange)">SN: <?php echo htmlspecialchars($item['serial_num']); ?></span>
                        </td>
                        <td>
                            <?php if(($item['location'] ?? '') == 'Onsite'): ?>
                                <span class="loc-badge active-loc">ONSITE</span>
                                <span class="sub-text" style="margin-top:5px;"><?php echo htmlspecialchars($item['cubicle_number'] ?? 'N/A'); ?></span>
                            <?php elseif(in_array($item['status'], ['storage', 'Dispose'])): ?>
                                <span class="loc-badge inactive-loc">OFF-GRID</span>
                                <span class="sub-text" style="margin-top:5px;">NO DEPLOYMENT</span>
                            <?php else: ?>
                                <span class="loc-badge"><?php echo strtoupper($item['location'] ?? 'REMOTE'); ?></span>
                                <span class="sub-text" style="margin-top:5px;"><?php echo htmlspecialchars($item['agent_name'] ?? 'N/A'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><span class="status-dot"></span><span style="font-size:0.8rem; font-weight:bold;"><?php echo strtoupper($item['status']); ?></span></div>
                            <span class="sub-text" style="margin-top:5px;"><?php echo date('M d, H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></span>
                        </td>
                        <td>
                            <button title="Edit" onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn"><i class="fa-solid fa-terminal"></i></button>
                            <button title="Delete" onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:var(--text-gray);">NO ACTIVE NODES DETECTED.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="assetModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="color:var(--primary-orange); margin: 0 0 20px 0;">Node Initialization</h2>
            <form id="assetForm">
                <input type="hidden" id="assetId">
                
                <label class="sub-text">Deployment Zone</label>
                <select id="location" class="form-control" onchange="toggleLocationFields()">
                    <option value="WFH">Remote / WFH</option>
                    <option value="Onsite">Onsite Production</option>
                    <option value="Release">Release (Asset Out)</option>
                    <option value="Return">Return (Asset In)</option>
                    <option value="Storage">Storage (Inventory)</option>
                    <option value="Dispose">Dispose (Decommission)</option>
                </select>

                <label class="sub-text">Current Node Status</label>
                <select id="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="storage">In Storage</option> 
                    <option value="Dispose">Disposed</option>
                    <option value="Vacant">Vacant</option>
                    <option value="RELEASED/DEPLOYED">RELEASED/DEPLOYED</option>
                </select>

                <label class="sub-text">Asset Identifier</label>
                <input type="text" id="assetName" class="form-control" required placeholder="e.g., PC-001">
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:1">
                        <label class="sub-text">Hostname</label>
                        <input type="text" id="hostName" class="form-control" required>
                    </div>
                    <div style="flex:1">
                        <label class="sub-text">Serial Num</label>
                        <input type="text" id="serialNum" class="form-control" required>
                    </div>
                </div>

                <label class="sub-text">Device Type</label>
                <select id="deviceType" class="form-control">
                    <option value="Desktop">Desktop</option>
                    <option value="Laptop">Laptop</option>
                </select>

                <div id="onsiteInfo" style="display:none; background:rgba(128,128,128,0.05); padding:15px; border-radius:10px; border:1px solid var(--border-color); margin-bottom:15px;">
                    <label class="sub-text">Department</label>
                    <select id="department" class="form-control">
                        <option value="Atlanta">Atlanta</option>
                        <option value="Boston">Boston</option>
                        <option value="Chicago">Chicago</option>
                        <option value="Dallas">Dallas</option>
                        <option value="Denver">Denver</option>
                        <option value="Golden State">Golden State</option>
                        <option value="Gray Room">Gray Room</option>
                        <option value="Indiana">Indiana</option>
                        <option value="Los Angeles">Los Angeles</option>
                        <option value="Miami">Miami</option>
                        <option value="Orlando">Orlando</option>
                        <option value="Phoenix">Phoenix</option>
                        <option value="Sacramento">Sacramento</option>
                        <option value="San Antonio">San Antonio</option>
                        <option value="Toronto">Toronto</option>
                        <option value="Training Room">Training Room</option>
                    </select>
                    <div style="display:flex; gap:15px;">
                        <div style="flex:1">
                            <label class="sub-text">Cubicle</label>
                            <input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search Cubicle...">
                        </div>
                        <div style="flex:1">
                            <label class="sub-text">Switch Port</label>
                            <input type="text" id="switch_port" class="form-control">
                        </div>
                    </div>
                </div>

                <div id="agentInfo" style="display:none; background:rgba(128,128,128,0.05); padding:15px; border-radius:10px; border:1px solid var(--border-color); margin-bottom:15px;">
                    <label class="sub-text">User / Agent Details</label>
                    <input type="text" id="agentName" class="form-control" placeholder="Full Name">
                    <input type="email" id="agentEmail" class="form-control" placeholder="Agent Email Address">
                    <input type="email" id="supEmail" class="form-control" placeholder="Supervisor Email">
                    
                    <label class="sub-text">Equipment Accessories</label>
                    <input type="text" id="accessories" class="form-control" placeholder="e.g. Mouse, Keyboard, Headset">

                    <div id="remarksWrapper" style="display:none; margin-bottom:15px;">
                        <label class="sub-text">Log Remarks</label>
                        <textarea id="remarks" class="form-control" rows="3" placeholder="Enter reason for release/return..."></textarea>
                    </div>
                </div>

                <button type="submit" id="saveBtn" class="btn-add" style="width:100%;">EXECUTE COMMAND</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:var(--text-gray); margin-top:15px; cursor:pointer;">Abort</button>
            </form>
        </div>
    </div>

    <datalist id="cubicleList">
        <?php foreach($cubicles as $c): ?>
            <option value="<?php echo htmlspecialchars($c['cubicle_no']); ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <script>
    const modal = document.getElementById('assetModal');

    function toggleLocationFields() {
        const loc = document.getElementById('location').value;
        const statusDropdown = document.getElementById('status');
        const onsiteDiv = document.getElementById('onsiteInfo');
        const agentDiv = document.getElementById('agentInfo');
        const remarksDiv = document.getElementById('remarksWrapper');

        onsiteDiv.style.display = (loc === 'Onsite') ? 'block' : 'none';

        if (loc === 'Release' || loc === 'Return') {
            agentDiv.style.display = 'block';
            remarksDiv.style.display = 'block';
        } else {
            agentDiv.style.display = 'none';
            remarksDiv.style.display = 'none';
            document.getElementById('remarks').value = ''; 
        }

        if (loc === 'Storage') statusDropdown.value = 'storage';
        else if (loc === 'Dispose') statusDropdown.value = 'Dispose';
        else if (loc === 'Release') statusDropdown.value = 'RELEASED/DEPLOYED';
        else if (loc === 'Return') statusDropdown.value = 'Active';
    }

    function openModal() { 
        document.getElementById('assetId').value = '';
        document.getElementById('assetForm').reset();
        document.getElementById('modalTitle').innerText = "Node Initialization";
        toggleLocationFields();
        modal.style.display = 'flex'; 
    }

    function closeModal() { modal.style.display = 'none'; }

    async function editAsset(item) {
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('assetId', item.id);
        setVal('assetName', item.asset_name);
        setVal('hostName', item.hostname);
        setVal('serialNum', item.serial_num);
        setVal('location', item.location);
        setVal('status', item.status);
        setVal('cubicleNumber', item.cubicle_number);
        setVal('deviceType', item.device_type);
        setVal('department', item.department);
        setVal('switch_port', item.switch_port);
        setVal('agentName', item.agent_name);
        setVal('agentEmail', item.agent_email);
        setVal('supEmail', item.immediate_supmail);
        setVal('accessories', item.accessories);
        setVal('remarks', item.remarks);

        document.getElementById('modalTitle').innerText = "Update Node Configuration";
        toggleLocationFields(); 
        modal.style.display = 'flex';
    }

    document.getElementById('assetForm').onsubmit = async (e) => {
        e.preventDefault();
        const getVal = (id) => {
            const el = document.getElementById(id);
            return el ? el.value.trim() : "";
        };

        const assetId = getVal('assetId');
        const data = {
            action: assetId ? 'update' : 'create',
            id: assetId || null,
            asset_name: getVal('assetName'),
            hostname: getVal('hostName'),
            serial_num: getVal('serialNum'),
            location: getVal('location'),
            status: getVal('status'),
            cubicle_number: getVal('cubicleNumber'),
            department: getVal('department'),
            switch_port: getVal('switch_port'),
            agent_name: getVal('agentName'),
            agent_email: getVal('agentEmail'),
            // Hidden data: still using the session variable from PHP
            user_email: '<?php echo $user_email; ?>',
            immediate_supmail: getVal('supEmail'),
            accessories: getVal('accessories'),
            device_type: getVal('deviceType'),
            remarks: getVal('remarks')
        };

        try {
            const res = await fetch('inventory_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if(result.success) location.reload(); else alert("Error: " + result.message);
        } catch (err) {
            console.error(err);
            alert("Connection Error.");
        }
    };

    function deleteAsset(id) {
        if(confirm("Confirm permanent deletion?")) {
            fetch('inventory_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            }).then(() => location.reload());
        }
    }
</script>
</body>
</html>