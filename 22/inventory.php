<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'EUC User'; // Default to User if not set

// Determine the back link based on role
$back_link = ($user_role === 'EUC Admin') ? 'index_admin.php' : 'index_user.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'] ?? 'User'; 
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
    <title>OJTBox | Asset Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="inventory.css">
    <style>
        /* FIX: Ensure the Save Button is visible */
        .modal {
            display: none; 
            position: fixed;
            z-index: 9999; /* Higher z-index to stay on top */
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(3px);
            overflow-y: auto; /* Allow whole page scroll if needed */
        }

        .modal-content {
            background-color: #fff;
            margin: 2% auto; /* Smaller top margin for better visibility */
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh; /* Don't let it leave the screen */
            overflow-y: auto;  /* Add scrollbar INSIDE modal if content is long */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .location-container { display: flex; flex-direction: column; gap: 6px; min-width: 140px; }
        .loc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; width: fit-content; }
        .loc-badge.onsite { background-color: #e0f2fe; color: #0369a1; }
        .loc-badge.wfh { background-color: #f1f5f9; color: #475569; }
        .onsite-details { display: flex; flex-direction: column; gap: 2px; padding-left: 8px; border-left: 2px solid #e2e8f0; }
        .detail-item { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #64748b; }
        .detail-item i { font-size: 0.7rem; color: #94a3b8; width: 12px; }
        .detail-item span { font-weight: 500; color: #334155; }
        .data-table td { vertical-align: middle !important; }
        code { background: #f1f5f9; padding: 2px 5px; border-radius: 4px; font-family: monospace; color: #e11d48; }
        
        /* Modal Form styling */
        .form-control { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        label { font-weight: 600; color: #334155; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <div class="tab-wrapper">
            <a href="?tab=all" class="tab-btn <?php echo $current_tab == 'all' ? 'active' : ''; ?>">All Assets</a>
            <a href="?tab=inventory" class="tab-btn <?php echo $current_tab == 'inventory' ? 'active' : ''; ?>">Active</a>
            <a href="?tab=storage" class="tab-btn <?php echo $current_tab == 'storage' ? 'active' : ''; ?>">Storage</a>
            <a href="?tab=dispose" class="tab-btn <?php echo $current_tab == 'dispose' ? 'active' : ''; ?>">Dispose</a>
        </div>

        <div class="header-flex">
            <h2>Inventory Records</h2>
            <div class="search-box">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
                    <input type="text" name="search" placeholder="Search assets..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <button onclick="openModal()" style="background:#ff6600; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold;">+ Add New Asset</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Name</th>
                    <th>Host Name</th>
                    <th>Serial Number</th>
                    <th>Location Info</th> 
                    <th>Type</th>
                    <th>Status</th>
                    <th>Logs</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($item = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['asset_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['hostname']); ?></td>
                        <td><code><?php echo htmlspecialchars($item['serial_num']); ?></code></td>
                        <td>
                            <td>
    <?php if(($item['location'] ?? '') == 'Onsite'): ?>
        <div class="location-container">
            <span class="loc-badge onsite"><i class="fa-solid fa-building"></i> Onsite</span>
            <div class="onsite-details">
                <div class="detail-item">
                    <i class="fa-solid fa-sitemap"></i> 
                    <span><?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <i class="fa-solid fa-desktop"></i> 
                    <span><?php echo htmlspecialchars($item['cubicle_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <i class="fa-solid fa-plug"></i> 
                    <span style="color: #ff6600; font-size: 0.75rem;">
                        <?php echo htmlspecialchars($item['switch_port'] ?? 'Not Set'); ?>
                    </span>
                </div>
            </div>
        </div>
    <?php else: ?>
        <span class="loc-badge wfh"><i class="fa-solid fa-house-user"></i> WFH</span>
    <?php endif; ?>
</td>
                        </td>
                        <td><?php echo htmlspecialchars($item['device_type']); ?></td>
                        <td><span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo $item['status']; ?></span></td>
                        <td class="timestamp-info">
                            <div style="color:#ff6600; font-weight: 500;"><?php echo date('M d y, H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></div>
                        </td>
                        <td style="text-align: center;">
                            <button title="Edit" onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button title="Delete" onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn btn-delete"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="assetModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add Asset</h3>
            <form id="assetForm">
                <input type="hidden" id="assetId">
                
                <label>Asset Name</label>
                <input type="text" id="assetName" class="form-control" required>
                
                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label>Host Name</label>
                        <input type="text" id="hostName" class="form-control" required>
                    </div>
                    <div style="flex:1">
                        <label>Serial Number</label>
                        <input type="text" id="serialNum" class="form-control" required>
                    </div>
                </div>

                <label>Work Location</label>
                <select id="location" class="form-control" onchange="toggleLocationFields()" required>
                    <option value="WFH">WFH (Work From Home)</option>
                    <option value="Onsite">Onsite</option>
                </select>

                <div id="onsiteInfo" style="display:none; flex-direction:column; gap:10px; background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0;">
                    <div style="display:flex; gap:10px;">
                        <div style="flex:1">
                            <label>Department</label>
                            <select id="department" class="form-control">
                                <option value="">-- Select --</option>
                                <option value="NATGEN">NATGEN</option>
                                <option value="LN ECRASH">LN ECRASH</option>
                                <option value="LN ELSEVIER">LN ELSEVIER</option>
                                <option value="DPD">DPD</option>
                                <option value="WILEY">WILEY</option>
                                <option value="SPRINGER">SPRINGER</option>
                                <option value="MHE">MHE</option>
                            </select>
                        </div>
                        <div style="flex:1">
                            <label>Cubicle No.</label>
                            <input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search DAL-XXXX..." oninput="syncCubicleId()">
                            <input type="hidden" id="selectedCubicleId">
                            <datalist id="cubicleList">
    <?php foreach($cubicles as $c): ?>
        <option value="<?php echo htmlspecialchars($c['cubicle_no']); ?>">
            <?php echo ($c['status'] === 'Occupied') ? "⚠️ " . htmlspecialchars($c['hostname']) : "✅ Vacant"; ?>
        </option>
    <?php endforeach; ?>
</datalist>
                        </div>
                    </div>
                    <div>
                        <label>Switch & Port</label>
                        <input type="text" id="switchPort" class="form-control" placeholder="e.g., SW-01 / P-23">
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label>Type</label>
                        <select id="deviceType" class="form-control">
                            <option>Laptop</option><option>Desktop</option><option>Server</option>
                        </select>
                    </div>
                    <div style="flex:1">
                        <label>Status</label>
                        <select id="status" class="form-control" onchange="toggleRemarks()">
                            <option value="Active">Active</option>
                            <option value="Vacant">Vacant</option>
                            <option value="Dispose">Dispose</option>
                        </select>
                    </div>
                </div>

                <div id="remarksArea" style="display:none;">
                    <label>Remarks</label>
                    <textarea id="remarks" class="form-control" rows="2"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" id="saveBtn" style="width:100%; background:#ff6600; color:white; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:1rem;">
                        <i class="fa-solid fa-save"></i> Save Asset Data
                    </button>
                    <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:#64748b; cursor:pointer; margin-top:10px; font-weight: 500;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // This converts your PHP array into a real JS object we can search
const cubicleData = <?php echo json_encode($cubicles); ?>;
    function toggleRemarks() {
        document.getElementById('remarksArea').style.display = (document.getElementById('status').value === 'Dispose') ? 'block' : 'none';
    }

    function toggleLocationFields() {
        const loc = document.getElementById('location').value;
        const onsiteBox = document.getElementById('onsiteInfo');
        onsiteBox.style.display = (loc === 'Onsite') ? 'flex' : 'none';
        document.getElementById('department').required = (loc === 'Onsite');
        document.getElementById('cubicleNumber').required = (loc === 'Onsite');
    }

   function syncCubicleId() {
    const input = document.getElementById('cubicleNumber');
    const hiddenIdInput = document.getElementById('selectedCubicleId');
    
    // 1. Clean the user's input
    const userTyped = input.value.trim().toUpperCase();
    
    // 2. Search our JS object for a match
    const match = cubicleData.find(c => c.cubicle_no.trim().toUpperCase() === userTyped);
    
    if (match) {
        hiddenIdInput.value = match.id;
        input.style.borderColor = "#10b981"; // Valid Green
        console.log("Found Match! ID is: " + match.id);
    } else {
        hiddenIdInput.value = "";
        input.style.borderColor = (userTyped === "") ? "#ddd" : "#ef4444"; // Red if not found
    }
}

    async function editAsset(item) {
        document.getElementById('assetId').value = item.id;
        document.getElementById('assetName').value = item.asset_name;
        document.getElementById('hostName').value = item.hostname;
        document.getElementById('serialNum').value = item.serial_num;
        document.getElementById('deviceType').value = item.device_type;
        document.getElementById('status').value = item.status;
        document.getElementById('remarks').value = item.remarks || '';
        document.getElementById('location').value = item.location || 'WFH';
        document.getElementById('department').value = item.department || '';
        document.getElementById('cubicleNumber').value = item.cubicle_number || '';
        document.getElementById('switchPort').value = item.switch_port || '';
        
        syncCubicleId();
        toggleRemarks();
        toggleLocationFields();
        document.getElementById('modalTitle').innerText = "Update Asset Detail";
        document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-save"></i> Update Asset';
        document.getElementById('assetModal').style.display = 'block';
    }

   document.getElementById('assetForm').onsubmit = async (e) => {
    e.preventDefault();

    const locationValue = document.getElementById('location').value;
    const cubicleId = document.getElementById('selectedCubicleId').value;
    const cubicleNum = document.getElementById('cubicleNumber').value;

    // --- VALIDATION: Check if Cubicle exists in the map ---
    if (locationValue === 'Onsite') {
        if (!cubicleNum) {
            alert("Please enter a Cubicle Number for Onsite assets.");
            return;
        }
        if (!cubicleId) {
            alert("Error: The Cubicle Number '" + cubicleNum + "' does not exist in the floor map. Please select a valid cubicle from the list.");
            document.getElementById('cubicleNumber').focus();
            return;
        }
    }

    const data = {
        action: document.getElementById('assetId').value ? 'update' : 'create',
        id: document.getElementById('assetId').value,
        asset_name: document.getElementById('assetName').value,
        hostname: document.getElementById('hostName').value,
        serial_num: document.getElementById('serialNum').value,
        device_type: document.getElementById('deviceType').value,
        status: document.getElementById('status').value,
        remarks: document.getElementById('remarks').value,
        location: locationValue,
        department: document.getElementById('department').value,
        cubicle_number: cubicleNum,
        cubicle_id: cubicleId,
        switch_port: document.getElementById('switchPort').value
    };

    // ... rest of your try/catch fetch logic ...
    try {
        const res = await fetch('inventory_crud.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data) 
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert("Database Error: " + result.message);
    } catch (err) {
        alert("System Error: Check network or backend.");
    }
}

    async function deleteAsset(id) {
        if (!confirm("Are you sure you want to delete this asset?")) return;
        const res = await fetch('inventory_crud.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id }) 
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert(result.message);
    }

    function openModal() { 
        document.getElementById('assetId').value = '';
        document.getElementById('assetForm').reset();
        document.getElementById('modalTitle').innerText = "Add New Asset";
        document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-save"></i> Save Asset';
        document.getElementById('assetModal').style.display = 'block'; 
    }

    function closeModal() { document.getElementById('assetModal').style.display = 'none'; }
    window.onclick = function(event) {
        if (event.target == document.getElementById('assetModal')) closeModal();
    }
    </script>
</body>
</html>