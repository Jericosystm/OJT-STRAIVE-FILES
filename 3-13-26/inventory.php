<?php

require_once 'db.php'; 

// --- Fetch Cubicles for the Searchable List ---
$cubicles = [];
$cubicles_result = $conn->query("SELECT DISTINCT cubicle_no FROM production_floor_map ORDER BY cubicle_no ASC");

if ($cubicles_result) {
    while($row = $cubicles_result->fetch_assoc()) {
        $cubicles[] = $row['cubicle_no']; 
    }
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$status_map = ['all' => 'All', 'inventory' => 'Active', 'storage' => 'Vacant', 'dispose' => 'Dispose'];
$target_status = $status_map[$current_tab] ?? 'Active';

$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $sql = "SELECT * FROM inventory_items WHERE (asset_name LIKE ? OR host_name LIKE ? OR serial_num LIKE ? OR cubicle_number LIKE ?) ORDER BY updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$like_param, $like_param, $like_param, $like_param];
        $types = "ssss";
    } else {
        $sql = "SELECT * FROM inventory_items ORDER BY updated_at DESC";
    }
} else {
    if (!empty($search_query)) {
        $sql = "SELECT * FROM inventory_items WHERE status = ? AND (asset_name LIKE ? OR host_name LIKE ? OR serial_num LIKE ? OR cubicle_number LIKE ?) ORDER BY updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$target_status, $like_param, $like_param, $like_param, $like_param];
        $types = "sssss";
    } else {
        $sql = "SELECT * FROM inventory_items WHERE status = ? ORDER BY updated_at DESC";
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
        /* UI Enhancement Styles */
        .location-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 140px;
        }
        .loc-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            width: fit-content;
        }
        .loc-badge.onsite { background-color: #e0f2fe; color: #0369a1; }
        .loc-badge.wfh { background-color: #f1f5f9; color: #475569; }
        
        .onsite-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding-left: 8px;
            border-left: 2px solid #e2e8f0;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: #64748b;
        }
        .detail-item i { font-size: 0.7rem; color: #94a3b8; width: 12px; }
        .detail-item span { font-weight: 500; color: #334155; }
        
        .data-table td { vertical-align: middle !important; }
        code { background: #f1f5f9; padding: 2px 5px; border-radius: 4px; font-family: monospace; color: #e11d48; }
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
                    <input type="text" name="search" placeholder="Search assets or cubicle..." value="<?php echo htmlspecialchars($search_query); ?>">
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
            <th>Machine Movement</th> <th>Logs</th>
            <th style="text-align: center;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($item = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['asset_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($item['host_name']); ?></td>
                <td><code><?php echo htmlspecialchars($item['serial_num']); ?></code></td>
                <td>
                    <?php if(($item['location'] ?? '') == 'Onsite'): ?>
                        <div class="location-container">
                            <span class="loc-badge onsite">
                                <i class="fa-solid fa-building"></i> Onsite
                            </span>
                            <div class="onsite-details">
                                <div class="detail-item">
                                    <i class="fa-solid fa-sitemap"></i>
                                    <span><?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fa-solid fa-desktop"></i>
                                    <span><?php echo htmlspecialchars($item['cubicle_no'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="loc-badge wfh">
                            <i class="fa-solid fa-house-user"></i> WFH
                        </span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($item['device_type']); ?></td>
                <td><span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo $item['status']; ?></span></td>
                
<td>
    <?php if ($item['status'] === 'Active' && !empty($item['machine_movement'])): ?>
        <span class="movement-badge <?php echo strtolower($item['machine_movement']); ?>">
            <?php echo htmlspecialchars($item['machine_movement']); ?>
        </span>
    <?php elseif ($item['status'] === 'Active'): ?>
        <span style="color: #cbd5e1; font-size: 0.8rem;">Pending</span>
    <?php else: ?>
        <span class="move-none" style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">
            N/A
        </span>
    <?php endif; ?>
</td>

                <td class="timestamp-info">
                    <div style="color:#ff6600; font-weight: 500;"><?php echo date('M d y, H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></div>
                </td>
                <td style="text-align: center;">
                    <button title="Edit" onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button title="Delete" onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn btn-delete">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center; padding:40px; color:#94a3b8;">No records found.</td></tr>
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
                        <input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search...">
                        <datalist id="cubicleList">
                            <?php foreach($cubicles as $num): ?>
                                <option value="<?php echo htmlspecialchars($num); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
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
                    <select id="status" class="form-control" onchange="handleStatusChange()">
                        <option value="Active">Active</option>
                        <option value="Vacant">Vacant</option>
                        <option value="Dispose">Dispose</option>
                    </select>
                </div>
            </div>

            <div id="movementArea" style="margin-top: 10px;">
                <label>Machine Movement</label>
                <select id="machineMovement" class="form-control">
                    <option value="">-- Select Movement --</option>
                    <option value="Release">Release</option>
                    <option value="Return">Return</option>
                </select>
            </div>

            <div id="remarksArea" style="display:none; margin-top: 10px;">
                <label>Remarks</label>
                <textarea id="remarks" class="form-control"></textarea>
            </div>

            <button type="submit" style="width:100%; background:#ff6600; color:white; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; margin-top:10px;">Save Asset</button>
            <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:#64748b; cursor:pointer; margin-top:5px;">Cancel</button>
        </form>
    </div>
</div>

    <script>
    // Handles BOTH Remarks visibility and Machine Movement accessibility
    function handleStatusChange() {
        const status = document.getElementById('status').value;
        const movementField = document.getElementById('machineMovement');
        const movementArea = document.getElementById('movementArea');
        const remarksArea = document.getElementById('remarksArea');

        if (status === 'Active') {
            // Show/Enable Movement, Hide Remarks
            movementArea.style.display = 'block';
            movementField.disabled = false;
            remarksArea.style.display = 'none';
        } else {
            // Disable/Hide Movement (Vacant/Dispose = NULL)
            movementField.value = ''; 
            movementField.disabled = true;
            movementArea.style.display = 'none';
            
            // Show remarks ONLY if it's Dispose
            remarksArea.style.display = (status === 'Dispose') ? 'block' : 'none';
        }
    }

    function toggleLocationFields() {
        const loc = document.getElementById('location').value;
        const onsiteBox = document.getElementById('onsiteInfo');
        onsiteBox.style.display = (loc === 'Onsite') ? 'flex' : 'none';
        document.getElementById('department').required = (loc === 'Onsite');
        document.getElementById('cubicleNumber').required = (loc === 'Onsite');
    }

    async function editAsset(item) {
        document.getElementById('assetId').value = item.id;
        document.getElementById('assetName').value = item.asset_name;
        document.getElementById('hostName').value = item.host_name;
        document.getElementById('serialNum').value = item.serial_num;
        document.getElementById('deviceType').value = item.device_type;
        document.getElementById('status').value = item.status;
        
        // ADDED: Populate the machine movement field
        document.getElementById('machineMovement').value = item.machine_movement || '';
        
        document.getElementById('remarks').value = item.remarks || '';
        document.getElementById('location').value = item.location || 'WFH';
        document.getElementById('department').value = item.department || '';
        document.getElementById('cubicleNumber').value = item.cubicle_no || '';
        
        // Replaced toggleRemarks with handleStatusChange
        handleStatusChange();
        toggleLocationFields();
        document.getElementById('modalTitle').innerText = "Update Asset";
        document.getElementById('assetModal').style.display = 'block';
    }

    document.getElementById('assetForm').onsubmit = async (e) => {
        e.preventDefault();
        const data = {
            action: document.getElementById('assetId').value ? 'update' : 'create',
            id: document.getElementById('assetId').value,
            asset_name: document.getElementById('assetName').value,
            host_name: document.getElementById('hostName').value,
            serial_num: document.getElementById('serialNum').value,
            device_type: document.getElementById('deviceType').value,
            status: document.getElementById('status').value,
            
            // ADDED: Send machine movement to inventory_CRUD.php
            machine_movement: document.getElementById('machineMovement').value,
            
            remarks: document.getElementById('remarks').value,
            location: document.getElementById('location').value,
            department: document.getElementById('department').value,
            cubicle_number: document.getElementById('cubicleNumber').value
        };

        try {
            const res = await fetch('inventory_CRUD.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data) 
            });
            const text = await res.text();
            const result = JSON.parse(text);
            if (result.success) location.reload();
            else alert("Error: " + result.message);
        } catch (err) {
            alert("Connection failed: " + err.message);
        }
    }

    async function deleteAsset(id) {
        if (!confirm("Are you sure?")) return;
        const res = await fetch('inventory_CRUD.php', { 
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
        document.getElementById('modalTitle').innerText = "Add Asset";
        
        // Initial state for new assets
        handleStatusChange(); 
        
        document.getElementById('onsiteInfo').style.display = 'none';
        document.getElementById('assetModal').style.display = 'block'; 
    }
    
    function closeModal() { document.getElementById('assetModal').style.display = 'none'; }
</script>
</body>
</html>