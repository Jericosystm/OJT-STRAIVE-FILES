<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$type = $_GET['type'] ?? 'all';

if ($type === 'return') {
    $query = "SELECT * FROM machine_movement WHERE location LIKE 'Return%'";
} elseif ($type === 'release') {
    $query = "SELECT * FROM machine_movement WHERE location LIKE 'Release%'";
} else {
    $query = "SELECT * FROM machine_movement WHERE location IN ('Return', 'Release')";
}

$query .= " ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Machine Movement Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="inventory.css"> 
    <style>
        /* Container & Layout */
        .log-container { padding: 20px; max-width: 1200px; margin: auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-bar { display: flex; gap: 10px; }
        
        /* Badges */
        .badge-return { background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .badge-release { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .timestamp { font-size: 0.85rem; color: #64748b; }
        
        /* Buttons */
        .btn-add { background: #f97316; color: white; padding: 10px 15px; border-radius: 6px; border:none; cursor:pointer; font-weight: bold; }
        .btn-edit { color: #3b82f6; cursor: pointer; border: none; background: none; font-size: 0.9rem; font-weight: 600; }
        .btn-save { background: #166534; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        
        /* Modal Styles */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.6); 
            backdrop-filter: blur(2px);
        }
        .modal-content { 
            background: white; 
            width: 90%; 
            max-width: 450px; 
            margin: 80px auto; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; box-sizing: border-box;
        }
        textarea { height: 80px; resize: none; }
        .btn-cancel { background: none; border: none; color: #64748b; width: 100%; margin-top: 15px; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="log-container">
        <div class="header-flex">
            <h2><i class="fa-solid fa-truck-moving"></i> Machine Movement History</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="filter-bar">
                    <a href="machine_movement.php?type=all" class="tab-btn <?php echo $type == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="machine_movement.php?type=return" class="tab-btn <?php echo $type == 'return' ? 'active' : ''; ?>">Returns</a>
                    <a href="machine_movement.php?type=release" class="tab-btn <?php echo $type == 'release' ? 'active' : ''; ?>">Releases</a>
                </div>
                <button onclick="openModal()" class="btn-add"><i class="fa-solid fa-plus"></i> Manual Log Entry</button>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Name</th>
                    <th>Hostname</th>
                    <th>Serial Number</th>
                    <th>Type</th>
                    <th>Movement</th>
                    <th>Date Processed</th>
                    <th>Moved By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['asset_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['hostname']); ?></td>
                        <td><code><?php echo htmlspecialchars($row['serial_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($row['device_type']); ?></td>
                        <td>
                            <?php if($row['location'] == 'Return'): ?>
                                <span class="badge-return"><i class="fa-solid fa-rotate-left"></i> RETURNED</span>
                            <?php else: ?>
                                <span class="badge-release"><i class="fa-solid fa-truck-ramp-box"></i> RELEASED</span>
                            <?php endif; ?>
                        </td>
                        <td class="timestamp">
                            <?php 
                                $date = $row['return_date'] ?? $row['release_date'] ?? $row['created_at'];
                                echo date('M d, Y | h:i A', strtotime($date)); 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['moved_by']); ?></td>
                        <td>
                            <button type="button" class="btn-edit" data-id="<?php echo $row['id']; ?>" onclick="openEditModal(this)">
    <i class="fa-solid fa-pen-to-square"></i> Edit
</button>
                    
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:50px;">No movement records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="manualModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0;">Manual Movement & Sync</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Updating here will automatically update or create the live Inventory record.</p>
            <form id="manualLogForm">
                <div class="form-group">
                    <label>Hostname</label>
                    <input type="text" name="hostname" placeholder="Enter Hostname" required>
                </div>
                <div class="form-group">
                    <label>Movement Type</label>
                    <select name="location" required>
                        <option value="Release">Release (Outward)</option>
                        <option value="Return">Return (Inward)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Internal Remarks</label>
                    <textarea name="remarks" placeholder="Optional notes for sync..."></textarea>
                </div>
                <button type="submit" class="btn-save">Sync & Save Log</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0;">Edit Movement & Sync Inventory</h3>
            <form id="editLogForm">
                <input type="hidden" name="movement_id" id="edit_movement_id">
                <input type="hidden" name="hostname" id="edit_hostname">

                <div class="form-group">
                    <label>Asset Name</label>
                    <input type="text" name="asset_name" id="edit_asset_name" required>
                </div>
                <div class="form-group">
                    <label>Serial Number</label>
                    <input type="text" name="serial_number" id="edit_serial_number">
                </div>
                <div class="form-group">
                    <label>Device Type</label>
                    <select name="device_type" id="edit_device_type">
                        <option value="Laptop">Laptop</option>
                        <option value="Desktop">Desktop</option>
                        <option value="Monitor">Monitor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Movement Status</label>
                    <select name="location" id="edit_location">
                        <option value="Release">Release</option>
                        <option value="Return">Return</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-save">Update Both Records</button>
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

<script>
// --- MODAL CONTROL FUNCTIONS ---
function openModal() { document.getElementById('manualModal').style.display = 'block'; }
function closeModal() { 
    document.getElementById('manualModal').style.display = 'none'; 
    document.getElementById('manualLogForm').reset();
}

function openEditModal(btn) {
    const row = btn.closest('tr');
    document.getElementById('edit_movement_id').value = btn.getAttribute('data-id');
    document.getElementById('edit_asset_name').value = row.cells[0].innerText.trim();
    document.getElementById('edit_hostname').value = row.cells[1].innerText.trim();
    document.getElementById('edit_serial_number').value = row.cells[2].innerText.trim();
    document.getElementById('edit_device_type').value = row.cells[3].innerText.trim();
    
    const moveText = row.cells[4].innerText.includes('RETURN') ? 'Return' : 'Release';
    document.getElementById('edit_location').value = moveText;

    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        closeModal();
        closeEditModal();
    }
}

// --- FORM SUBMISSIONS ---
document.addEventListener('DOMContentLoaded', function() {
    // Manual Add Submit
    document.getElementById('manualLogForm').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.action = 'manual_sync_update';

        try {
            const resp = await fetch('./movement_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await resp.json();
            if(result.success) {
                alert("Inventory Synced Successfully!");
                location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (error) { alert("Critical Error: Check if movement_crud.php exists."); }
    };

    // Edit Submit
    document.getElementById('editLogForm').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.action = 'update_movement_sync';

        try {
            const resp = await fetch('./movement_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await resp.json();
            if(result.success) {
                alert("Log and Inventory updated successfully!");
                location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (error) { alert("Critical Error: Update failed."); }
    };
});
</script>
</body>
</html>