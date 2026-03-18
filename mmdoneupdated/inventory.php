<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'euc_user'; 
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
$current_tab = $_GET['tab'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$status_map = ['all' => 'All', 'inventory' => 'Active', 'storage' => 'Vacant', 'dispose' => 'Dispose'];
$target_status = $status_map[$current_tab] ?? 'Active';

$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $like_param = "%$search_query%"; // FIXED: Added missing definition
        $sql = "SELECT i.*, p.department AS switch_port 
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
            --border-color: #222222;
        }

        body {
            background-color: var(--bg-dark);
            color: #ffffff;
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
            background: #1a1a1a; color: white; text-decoration: none; padding: 10px 25px;
            border-radius: 8px; font-weight: 600; border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .tab-btn.active { background: var(--primary-orange); box-shadow: 0 5px 20px rgba(255, 102, 0, 0.4); }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .search-box form { display: flex; background: #151515; border-radius: 10px; padding: 5px; border: 1px solid var(--border-color); align-items: center; }
        .search-box input { background: transparent; border: none; color: white; padding: 10px 15px; outline: none; width: 300px; }
        
        .btn-add {
            background: var(--primary-orange); color: white; border: none; padding: 12px 25px;
            border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s;
        }

        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .data-table th { text-align: left; color: var(--text-gray); padding: 0 20px; font-size: 0.75rem; text-transform: uppercase; }
        .data-table tr { background: var(--card-bg); transition: 0.3s; }
        .data-table tbody tr:hover { transform: translateX(10px); background: #1a1a1a; box-shadow: -5px 0 0 var(--primary-orange); }
        .data-table td { padding: 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .data-table td:first-child { border-radius: 12px 0 0 12px; }
        .data-table td:last-child { border-radius: 0 12px 12px 0; text-align: center; }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; background: var(--neon-green); box-shadow: 0 0 10px var(--neon-green); }
        .loc-badge { background: rgba(255,255,255,0.05); color: var(--text-gray); padding: 4px 10px; border-radius: 5px; font-size: 0.7rem; font-weight: bold; border: 1px solid var(--border-color); }
        .loc-badge.active-loc { border-color: var(--neon-green); color: var(--neon-green); background: rgba(0, 255, 153, 0.1); }
        
        .node-icon { background: #1a1a1a; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px; border: 1px solid #333; float: left; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); align-items: center; justify-content: center; }
        .modal-content { background: #111; border: 1px solid #333; padding: 30px; width: 90%; max-width: 500px; border-radius: 20px; }
        .form-control { background: #1a1a1a; border: 1px solid #333; color: white; width: 100%; padding: 12px; border-radius: 8px; margin: 10px 0 20px 0; box-sizing: border-box; }
        .sub-text { color: var(--text-gray); font-size: 0.75rem; display: block; text-transform: uppercase; }
        .action-btn { background: #222; border: 1px solid #333; color: white; padding: 8px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <section class="header-section">
            <h4>System Architecture</h4>
            <h1>Inventory Nodes</h1>
            
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
                    
                    <?php if(!empty($search_query)): ?>
                        <a href="?tab=<?php echo $current_tab; ?>" style="color:var(--text-gray); margin-right:10px;"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>

                    <button type="submit" style="background:none; border:none; color:var(--primary-orange); cursor:pointer; padding: 0 10px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>
            <button onclick="openModal()" class="btn-add">+ Initialize New Node</button>
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
                            <div style="float:left">
                                <span style="font-weight:700; display:block;"><?php echo htmlspecialchars($item['asset_name']); ?></span>
                                <span class="sub-text"><?php echo htmlspecialchars($item['device_type']); ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="display:block; font-family:monospace;"><?php echo htmlspecialchars($item['hostname']); ?></span>
                            <span class="sub-text" style="color:var(--primary-orange)">SN: <?php echo htmlspecialchars($item['serial_num']); ?></span>
                        </td>
                        <td>
                            <?php if(($item['location'] ?? '') == 'Onsite'): ?>
                                <span class="loc-badge active-loc">ONSITE</span>
                                <span class="sub-text" style="margin-top:5px;"><?php echo htmlspecialchars($item['cubicle_number'] ?? 'N/A'); ?></span>
                            <?php else: ?>
                                <span class="loc-badge"><?php echo strtoupper($item['location'] ?? 'REMOTE'); ?></span>
                                <span class="sub-text" style="margin-top:5px;"><?php echo htmlspecialchars($item['agent_name'] ?? ''); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><span class="status-dot"></span><span style="font-size:0.8rem; font-weight:bold;"><?php echo strtoupper($item['status']); ?></span></div>
                            <span class="sub-text" style="margin-top:5px;"><?php echo date('M d, H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></span>
                        </td>
                        <td>
                            <button title="Edit" onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn btn-edit"><i class="fa-solid fa-terminal"></i></button>
                            <button title="Delete" onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
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
                <input type="hidden" id="status" value="Active"> 
                
                <label class="sub-text">Asset Identifier</label>
                <input type="text" id="assetName" class="form-control" required>
                
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
                    <option value="Mini PC">Mini PC</option>
                    <option value="Server">Server</option>
                </select>

                <label class="sub-text">Deployment Zone</label>
                <select id="location" class="form-control" onchange="toggleLocationFields()">
                    <option value="WFH">Remote / WFH</option>
                    <option value="Onsite">Onsite Production</option>
                    <option value="Release">Release (Asset Out)</option>
                    <option value="Return">Return (Asset In)</option>
                </select>

                <div id="onsiteInfo" style="display:none; background:#000; padding:15px; border-radius:10px; border:1px solid #333; margin-bottom:15px;">
                    <div style="margin-bottom:10px;">
                        <label class="sub-text">Department</label>
                        <select id="department" class="form-control">
                            <option value="NATGEN">NATGEN</option>
                            <option value="LN ECRASH">LN ECRASH</option>
                            <option value="ACS">ACS</option>
                            <option value="WILEY">WILEY</option>
                            <option value="SPRINGER">SPRINGER</option>
                            <option value="MHE">MHE</option>
                            <option value="POSNL">POSNL</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:15px;">
                        <div style="flex:1">
                            <label class="sub-text">Cubicle Assignment</label>
                            <input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search Cubicle...">
                        </div>
                        <div style="flex:1">
                            <label class="sub-text">Switch Port</label>
                            <input type="text" id="switch_port" class="form-control" placeholder="e.g. Fa0/1">
                        </div>
                    </div>
                </div>

                <div id="agentInfo" style="display:none; background:#000; padding:15px; border-radius:10px; border:1px solid #333; margin-bottom:15px;">
                    <label class="sub-text">Agent Name</label>
                    <input type="text" id="agentName" class="form-control" placeholder="Full Name">
                    
                    <div style="display:flex; gap:15px;">
                        <div style="flex:1">
                            <label class="sub-text">Agent Email</label>
                            <input type="email" id="agentEmail" class="form-control" placeholder="agent@company.com">
                        </div>
                        <div style="flex:1">
                            <label class="sub-text">Superior Email</label>
                            <input type="email" id="supEmail" class="form-control" placeholder="sup@company.com">
                        </div>
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
    const onsiteDiv = document.getElementById('onsiteInfo');
    const agentDiv = document.getElementById('agentInfo');

    // Show Onsite fields (Department, Cubicle, Port) ONLY for Onsite
    onsiteDiv.style.display = (loc === 'Onsite') ? 'block' : 'none';

    // Show Agent fields (Name, Email, Superior) ONLY for Release or Return
    // WFH and Onsite will now hide this section
    if (loc === 'Release' || loc === 'Return') {
        agentDiv.style.display = 'block';
        document.getElementById('agentName').required = true;
        document.getElementById('agentEmail').required = true;
        document.getElementById('supEmail').required = true;
    } else {
        agentDiv.style.display = 'none';
        document.getElementById('agentName').required = false;
        document.getElementById('agentEmail').required = false;
        document.getElementById('supEmail').required = false;
        
        // Optional: Clear the values if switching away from Release/Return
        if (loc === 'WFH' || loc === 'Onsite') {
            document.getElementById('agentName').value = '';
            document.getElementById('agentEmail').value = '';
            document.getElementById('supEmail').value = '';
        }
    }
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
        setVal('location', item.location || 'WFH');
        setVal('status', item.status || 'Active');
        setVal('cubicleNumber', item.cubicle_number);
        setVal('deviceType', item.device_type);
        setVal('department', item.department);
        setVal('switch_port', item.switch_port);
        setVal('agentName', item.agent_name);
        setVal('agentEmail', item.agent_email);
        setVal('supEmail', item.immediate_supmail);

        document.getElementById('modalTitle').innerText = "Update Node Configuration";
        toggleLocationFields();
        modal.style.display = 'flex';
    }

    document.getElementById('assetForm').onsubmit = async (e) => {
        e.preventDefault();
        const getVal = (id) => document.getElementById(id) ? document.getElementById(id).value : "";

        const data = {
            action: getVal('assetId') ? 'update' : 'create',
            id: getVal('assetId'),
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
            immediate_supmail: getVal('supEmail'),
            device_type: getVal('deviceType')
        };

        try {
            const res = await fetch('inventory_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if(result.success) location.reload(); else alert(result.message);
        } catch (err) {
            alert("Execution failed.");
        }
    };

    function deleteAsset(id) {
        if(confirm("Confirm permanent deletion of this node?")) {
            fetch('inventory_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            }).then(res => res.json()).then(res => {
                if(res.success) location.reload();
            });
        }
    }
    </script>
</body>
</html>