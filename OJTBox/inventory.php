<?php
session_start();

// Security Check: If 'user_id' isn't set, they aren't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now we can safely get the username (make sure you set this in auth.php!)
$username = $_SESSION['username'] ?? 'User'; 
?>
<?php
require_once 'db.php'; 

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$status_map = [
    'all'       => 'All',
    'inventory' => 'Active',
    'storage'   => 'Vacant',
    'dispose'   => 'Dispose'
];

$target_status = $status_map[$current_tab] ?? 'Active';

$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $sql = "SELECT * FROM inventory_items 
                WHERE (asset_name LIKE ? OR host_name LIKE ? OR serial_num LIKE ?) 
                ORDER BY updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$like_param, $like_param, $like_param];
        $types = "sss";
    } else {
        $sql = "SELECT * FROM inventory_items ORDER BY updated_at DESC";
    }
} else {
    if (!empty($search_query)) {
        $sql = "SELECT * FROM inventory_items 
                WHERE status = ? 
                AND (asset_name LIKE ? OR host_name LIKE ? OR serial_num LIKE ?) 
                ORDER BY updated_at DESC";
        $like_param = "%$search_query%";
        $params = [$target_status, $like_param, $like_param, $like_param];
        $types = "ssss";
    } else {
        $sql = "SELECT * FROM inventory_items WHERE status = ? ORDER BY updated_at DESC";
        $params = [$target_status];
        $types = "s";
    }
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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

</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <div class="logo"><a href="index.php" style="color: white; text-decoration: none; margin-right: 15px;"><i class="fa-solid fa-arrow-left"></i></a>OJTBox <span>| Inventory Manager</span></div>
        </div>
    </nav>

    <main class="inventory-container">
        <div class="tab-wrapper">
            <a href="?tab=all" class="tab-btn <?php echo $current_tab == 'all' ? 'active' : ''; ?>">All Assets</a>
            <a href="?tab=inventory" class="tab-btn <?php echo $current_tab == 'inventory' ? 'active' : ''; ?>">Active</a>
            <a href="?tab=storage" class="tab-btn <?php echo $current_tab == 'storage' ? 'active' : ''; ?>">Storage</a>
            <a href="?tab=dispose" class="tab-btn <?php echo $current_tab == 'dispose' ? 'active' : ''; ?>">Dispose</a>
        </div>

        <div class="header-flex">
            <h2>Inventory Records</h2>
            <button onclick="openModal()" style="background:#ff6600; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold;">+ Add New Asset</button>
        </div>

        <form action="" method="GET" class="search-container">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
            <input type="text" name="search" class="search-input" placeholder="Search by name, host, or serial..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" style="background:#333; color:white; border:none; padding:0 20px; border-radius:8px; cursor:pointer;">Search</button>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Name</th>
                    <th>Host Name</th>
                    <th>Serial Number</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Logs (Added/Updated)</th>
                    <?php if($current_tab == 'dispose'): ?><th>Remarks</th><?php endif; ?>
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
                        <td><?php echo htmlspecialchars($item['device_type']); ?></td>
                        <td><span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo $item['status']; ?></span></td>
                        <td class="timestamp-info">
                        
                            <div style="color:#ff6600; font-weight: 500;"><?php echo date('M d y, H:i', strtotime($item['updated_at'] ?? $item['created_at'])); ?></div>
                        </td>
                        <?php if($current_tab == 'dispose'): ?>
                            <td style="max-width:200px;"><small><?php echo htmlspecialchars($item['remarks'] ?? 'None'); ?></small></td>
                        <?php endif; ?>
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
                <label>Host Name</label>
                <input type="text" id="hostName" class="form-control" required>
                <label>Serial Number</label>
                <input type="text" id="serialNum" class="form-control" required>
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
                    <textarea id="remarks" class="form-control"></textarea>
                </div>
                <button type="submit" style="width:100%; background:#ff6600; color:white; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; margin-top:10px;">Save Asset</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:#64748b; cursor:pointer; margin-top:5px;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function toggleRemarks() {
            document.getElementById('remarksArea').style.display = (document.getElementById('status').value === 'Dispose') ? 'block' : 'none';
        }

        async function editAsset(item) {
            document.getElementById('assetId').value = item.id;
            document.getElementById('assetName').value = item.asset_name;
            document.getElementById('hostName').value = item.host_name;
            document.getElementById('serialNum').value = item.serial_num;
            document.getElementById('deviceType').value = item.device_type;
            document.getElementById('status').value = item.status;
            document.getElementById('remarks').value = item.remarks || '';
            toggleRemarks();
            document.getElementById('modalTitle').innerText = "Update Asset";
            document.getElementById('assetModal').style.display = 'block';
        }

        async function deleteAsset(id) {
            if (!confirm("Are you sure you want to permanently delete this asset?")) return;
            const res = await fetch('inventory_CRUD.php', { 
                method: 'POST', 
                body: JSON.stringify({ action: 'delete', id: id }) 
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.message);
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
                remarks: document.getElementById('remarks').value
            };
            const res = await fetch('inventory_CRUD.php', { method:'POST', body: JSON.stringify(data) });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.message);
        }

        function openModal() { 
            document.getElementById('assetId').value = '';
            document.getElementById('assetForm').reset();
            document.getElementById('modalTitle').innerText = "Add Asset";
            document.getElementById('remarksArea').style.display = 'none';
            document.getElementById('assetModal').style.display = 'block'; 
        }
        function closeModal() { document.getElementById('assetModal').style.display = 'none'; }
    </script>
</body>
</html>