<?php
date_default_timezone_set('Asia/Manila'); // Set PHP to Philippine Time
session_start();

if (!isset($_SESSION['user_id'])) {

header("Location: login.php");

exit();

}

$username = $_SESSION['username'] ?? 'User';

require_once 'db.php';



// --- Fetch Cubicles for the Searchable List ---

$cubicles = [];

$cubicles_result = $conn->query("SELECT DISTINCT cubicle_no FROM all_assets_master ORDER BY cubicle_no ASC");



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



$cols = "`id`, `asset_name`, `host_name`, `serial_num`, `location`, `department`, `cubicle_no`, `device_type`, `status`, `updated_at`, `created_at`, `remarks`";



if ($target_status === 'All') {

if (!empty($search_query)) {

$sql = "SELECT $cols FROM all_assets_master WHERE (`asset_name` LIKE ? OR `host_name` LIKE ? OR `serial_num` LIKE ? OR `cubicle_no` LIKE ?) ORDER BY updated_at DESC";
$like_param = "%$search_query%";

$params = [$like_param, $like_param, $like_param, $like_param];

$types = "ssss";

} else {

$sql = "SELECT $cols FROM all_assets_master ORDER BY updated_at DESC";

}

} else {

if (!empty($search_query)) {

$sql = "SELECT $cols FROM all_assets_master WHERE `status` = ? AND (`asset_name` LIKE ? OR `host_name` LIKE ? OR `serial_num` LIKE ? OR `cubicle_no` LIKE ?) ORDER BY updated_at DESC";

$like_param = "%$search_query%";

$params = [$target_status, $like_param, $like_param, $like_param, $like_param];

$types = "sssss";

} else {

$sql = "SELECT $cols FROM all_assets_master WHERE `status` = ? ORDER BY updated_at DESC";

$params = [$target_status];

$types = "s";

}

}



$stmt = $conn->prepare($sql);



if (!$stmt) {

die("<div style='padding:20px; background:#fee2e2; color:#b91c1c; font-family:sans-serif; border:2px solid #ef4444; margin:20px; border-radius:10px;'>

<h3 style='margin-top:0;'>Database Query Error</h3>

<p><strong>Error Message:</strong> " . $conn->error . "</p>

<hr>

<p>Ensure the table <code>inventory_items</code> has the <code>created_at</code> and <code>updated_at</code> columns.</p>

</div>");

}



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

/* Enhanced Header Layout */
.header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 20px;
}

.header-flex h2 {
    margin: 0;
    flex-grow: 1;
    color: #1e293b;
    font-size: 1.5rem;
}

/* Styled Search Box */
.search-box form {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 5px 15px;
    transition: all 0.3s ease;
    width: 300px; /* Fixed width for consistency */
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.search-box form:focus-within {
    border-color: #ff6600;
    box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
}

.search-box input {
    border: none;
    outline: none;
    padding: 8px 10px;
    width: 100%;
    font-size: 0.9rem;
    color: #334155;
}

.search-box button {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 1rem;
    padding: 5px;
    transition: color 0.2s;
}

.search-box button:hover {
    color: #ff6600;
}

/* Ensure the Add button stays prominent */
.btn-add-new {
    background: #ff6600;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: transform 0.2s;
}

.btn-add-new:hover {
    transform: translateY(-1px);
    background: #e65c00;
}

/* --- STICKY MODAL FIX --- */

.modal {

display: none;

position: fixed;

z-index: 9999;

left: 0;

top: 0;

width: 100%;

height: 100%;

background-color: rgba(0,0,0,0.6);

backdrop-filter: blur(3px);

}



.modal-content {

background-color: #fff;

position: relative;

margin: 5vh auto; /* Center with some top margin */

width: 90%;

max-width: 500px;

height: auto;

max-height: 85vh; /* Maximum height of modal */

border-radius: 12px;

display: flex;

flex-direction: column; /* Stack header, body, footer */

overflow: hidden; /* Important: hide overflow here so body handles it */

box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);

}



.modal-header {

padding: 1.25rem 1.5rem;

border-bottom: 1px solid #e2e8f0;

flex-shrink: 0; /* Header won't shrink */

}



/* The Form needs to be flex to allow the body to grow/scroll */

#assetForm {

display: flex;

flex-direction: column;

overflow: hidden;

height: 100%;

}



.modal-body {

padding: 1.5rem;

overflow-y: auto; /* THIS ENABLES SCROLLING */

flex-grow: 1; /* Takes up remaining space */

background: #ffffff;

}



.modal-footer {

padding: 1.25rem 1.5rem;

border-top: 1px solid #e2e8f0;

background: #f8fafc;

flex-shrink: 0; /* Footer won't shrink or move */

}



.form-group { margin-bottom: 1rem; }

.form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; color: #334155; font-size: 0.85rem; }


/* Style adjustments for small screens */

@media (max-height: 700px) {

.modal-content { margin: 2vh auto; max-height: 96vh; }

}



.tab-wrapper {

display: flex;

gap: 10px;

margin-bottom: 20px;

border-bottom: 2px solid #e2e8f0;

padding-bottom: 10px;

}



.tab-btn {

text-decoration: none;

padding: 10px 20px;

border-radius: 8px;

color: #64748b;

font-weight: 600;

transition: all 0.3s ease;

background: #f1f5f9;

}



.tab-btn:hover {

background: #e2e8f0;

color: #1e293b;

}



.tab-btn.active {

background: #ff6600;

color: white;

}

</style>

</head>

<body>

<?php include 'header.php'; ?>



<main class="inventory-container">

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
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            <input type="text" name="search" placeholder="Search by name, serial, or cubicle..." value="<?php echo htmlspecialchars($search_query); ?>">
        </form>
    </div>

    <button onclick="openModal()" class="btn-add-new">
        <i class="fa-solid fa-plus"></i> Add New Asset
    </button>
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

<td><?php echo htmlspecialchars($item['host_name']); ?></td>

<td><code><?php echo htmlspecialchars($item['serial_num']); ?></code></td>

<td>

<?php if(($item['location'] ?? '') == 'Onsite'): ?>

<div class="location-container">

<span class="loc-badge onsite"><i class="fa-solid fa-building"></i> Onsite</span>

<div class="onsite-details">

<div class="detail-item"><i class="fa-solid fa-sitemap"></i><span><?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?></span></div>

<div class="detail-item"><i class="fa-solid fa-desktop"></i><span><?php echo htmlspecialchars($item['cubicle_no'] ?? 'N/A'); ?></span></div>

</div>

</div>

<?php else: ?>

<span class="loc-badge wfh"><i class="fa-solid fa-house-user"></i> WFH</span>

<?php endif; ?>

</td>

<td><?php echo htmlspecialchars($item['device_type']); ?></td>

<td><span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo $item['status']; ?></span></td>

<td>
    <div style="color:#ff6600; font-size:0.85rem;">
        <?php 
            $date_string = $item['updated_at'] ?? $item['created_at'] ?? null;
            if ($date_string && $date_string != '0000-00-00 00:00:00') {
                $dt = new DateTime($date_string);
                // If your DB is in UTC, uncomment the line below:
                // $dt->setTimezone(new DateTimeZone('Asia/Manila')); 
                echo $dt->format('M d y, h:i A'); // 'A' adds AM/PM for easier reading
            } else {
                echo 'N/A';
            }
        ?>
    </div>
</td>

<td style="text-align: center;">

<button onclick='editAsset(<?php echo json_encode($item); ?>)' class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i></button>

<button onclick="deleteAsset(<?php echo $item['id']; ?>)" class="action-btn btn-delete"><i class="fa-solid fa-trash-can"></i></button>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr><td colspan="8" style="text-align:center; padding:40px;">No records found.</td></tr>

<?php endif; ?>

</tbody>

</table>

</main>



<div id="assetModal" class="modal">

<div class="modal-content">

<div class="modal-header">

<h3 id="modalTitle" style="margin:0; color:#1e293b;">Add Asset</h3>

</div>


<form id="assetForm">

<div class="modal-body">

<input type="hidden" id="assetId">


<div class="form-group">

<label>Asset Name</label>

<input type="text" id="assetName" class="form-control" required>

</div>


<div style="display:flex; gap:10px;" class="form-group">

<div style="flex:1">

<label>Host Name</label>

<input type="text" id="hostName" class="form-control" required>

</div>

<div style="flex:1">

<label>Serial Number</label>

<input type="text" id="serialNum" class="form-control" required>

</div>

</div>



<div class="form-group">

<label>Work Location</label>

<select id="location" class="form-control" onchange="toggleLocationFields()" required>

<option value="WFH">WFH (Work From Home)</option>

<option value="Onsite">Onsite</option>

</select>

</div>



<div id="onsiteInfo" style="display:none; flex-direction:column; gap:10px; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #e2e8f0;">

<div class="form-group">

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

<div class="form-group">

<label>Cubicle No.</label>

<input list="cubicleList" id="cubicleNumber" class="form-control" placeholder="Search...">

<datalist id="cubicleList">

<?php foreach($cubicles as $num): ?>

<option value="<?php echo htmlspecialchars($num); ?>">

<?php endforeach; ?>

</datalist>

</div>

</div>



<div style="display:flex; gap:10px;" class="form-group">

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



<div id="remarksArea" style="display:none;" class="form-group">

<label>Remarks</label>

<textarea id="remarks" class="form-control" rows="3"></textarea>

</div>

</div>



<div class="modal-footer">

<button type="submit" style="width:100%; background:#ff6600; color:white; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:1rem;">Save Asset</button>

<button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:#64748b; cursor:pointer; margin-top:8px; font-weight:500;">Cancel</button>

</div>

</form>

</div>

</div>



<script>

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



function openModal() {

document.getElementById('assetId').value = '';

document.getElementById('assetForm').reset();

document.getElementById('modalTitle').innerText = "Add Asset";

document.getElementById('remarksArea').style.display = 'none';

document.getElementById('onsiteInfo').style.display = 'none';

document.getElementById('assetModal').style.display = 'block';

}



function closeModal() { document.getElementById('assetModal').style.display = 'none'; }



async function editAsset(item) {

document.getElementById('assetId').value = item.id;

document.getElementById('assetName').value = item.asset_name;

document.getElementById('hostName').value = item.host_name;

document.getElementById('serialNum').value = item.serial_num;

document.getElementById('deviceType').value = item.device_type;

document.getElementById('status').value = item.status;

document.getElementById('remarks').value = item.remarks || '';

document.getElementById('location').value = item.location || 'WFH';

document.getElementById('department').value = item.department || '';

document.getElementById('cubicleNumber').value = item.cubicle_no || '';


toggleRemarks();

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

remarks: document.getElementById('remarks').value,

location: document.getElementById('location').value,

department: document.getElementById('department').value,

cubicle_no: document.getElementById('cubicleNumber').value

};



try {

const res = await fetch('inventory_CRUD.php', {

method: 'POST',

headers: { 'Content-Type': 'application/json' },

body: JSON.stringify(data)

});

const result = await res.json();

if (result.success) location.reload();

else alert("Error: " + result.message);

} catch (err) {

alert("Connection failed: " + err.message);

}

}



async function deleteAsset(id) {

if (!confirm("Are you sure?")) return;

try {

const res = await fetch('inventory_CRUD.php', {

method: 'POST',

headers: { 'Content-Type': 'application/json' },

body: JSON.stringify({ action: 'delete', id: id })

});

const result = await res.json();

if (result.success) location.reload();

else alert(result.message);

} catch (err) {

alert("Delete failed.");

}

}

</script>

</body>

</html>