<?php
session_start();
require_once 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
    <title>OJTBox | Movement Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --neon-green: #00ff99;
            --neon-blue: #00d4ff;
            --text-gray: #a0a0a0;
            --text-main: #ffffff;
            --border-color: #222222;
            --input-bg: #151515;
        }

        /* Light Mode Overrides */
        [data-theme="light"] {
            --bg-dark: #f4f4f4;
            --card-bg: #ffffff;
            --text-gray: #444444; /* Darkened from #666666 for better readability */
            --text-main: #000000; /* Pure black for maximum contrast */
            --border-color: #cccccc; /* Slightly darker border for definition */
            --input-bg: #f9f9f9;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0;
            transition: background 0.3s ease, color 0.3s ease;
            animation: fadeInPage 0.8s ease-out;
        }

        @keyframes fadeInPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .log-container { padding: 40px; max-width: 1400px; margin: auto; }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .tab-wrapper { display: flex; gap: 10px; background: var(--input-bg); padding: 5px; border-radius: 10px; border: 1px solid var(--border-color); }
        .tab-link { 
            padding: 8px 20px; color: var(--text-gray); text-decoration: none; border-radius: 6px; 
            font-size: 0.85rem; font-weight: 600; transition: 0.3s;
        }
        .tab-link.active { background: var(--primary-orange); color: white; }

        /* Search Styles */
        .search-container { margin-bottom: 25px; position: relative; max-width: 400px; }
        .search-input {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border-color);
            padding: 12px 40px 12px 15px; border-radius: 10px; color: var(--text-main);
            font-size: 0.9rem; transition: 0.3s;
        }
        .search-input:focus { outline: none; border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(0, 212, 255, 0.2); }
        .search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); }

        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .data-table th { text-align: left; color: var(--text-gray); padding: 0 20px; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; }
        .data-table tr { background: var(--card-bg); transition: 0.3s; }
        .data-table tbody tr:hover { transform: translateX(8px); background: var(--input-bg); box-shadow: -4px 0 0 var(--neon-blue); }
        .data-table td { padding: 18px 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        .data-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 10px 0 0 10px; }
        .data-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 10px 10px 0; }

        .badge { font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 4px; border: 1px solid; display: inline-flex; align-items: center; gap: 5px; }
        .badge-return { color: #856404; border-color: rgba(133, 100, 4, 0.3); background: rgba(250, 204, 21, 0.2); }
        [data-theme="dark"] .badge-return { color: #facc15; border-color: rgba(250, 204, 21, 0.3); background: rgba(250, 204, 21, 0.1); }
        .badge-release { color: #00814d; border-color: rgba(0, 129, 77, 0.3); background: rgba(0, 255, 153, 0.2); }
        [data-theme="dark"] .badge-release { color: var(--neon-green); border-color: rgba(0, 255, 153, 0.3); background: rgba(0, 255, 153, 0.1); }

        .btn-add { background: var(--primary-orange); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-add:hover { box-shadow: 0 0 15px rgba(255,102,0,0.4); }
        .btn-edit { background: none; border: 1px solid var(--border-color); color: var(--neon-blue); padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.8rem; transition: 0.3s; }
        [data-theme="light"] .btn-edit { color: #007791; border-color: #007791; }
        .btn-edit:hover { background: var(--neon-blue); color: black; }

        .modal { 
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); align-items: center; justify-content: center;
        }
        .modal-content { 
            background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; width: 95%; max-width: 500px; border-radius: 15px; 
            box-shadow: 0 0 30px rgba(0,0,0,0.5); max-height: 90vh; overflow-y: auto; color: var(--text-main);
        }
        .form-group label { display: block; color: var(--text-gray); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; font-weight: 700; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); padding: 12px; border-radius: 8px; box-sizing: border-box; margin-bottom: 15px;
        }
        .btn-save { background: var(--neon-green); color: black; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .agent-info-box { border-left: 2px solid var(--neon-blue); padding-left: 15px; margin-bottom: 20px; background: rgba(0, 212, 255, 0.03); padding-top: 10px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="log-container">
        <div class="header-flex">
            <div>
                <h4 style="color:var(--neon-blue); text-transform:uppercase; letter-spacing:2px; font-size:0.75rem; margin:0;">Transaction Logs</h4>
                <h1 style="margin:5px 0 0 0; font-size:2rem; color: var(--text-main);">Machine Movement</h1>
            </div>
            
            <div style="display: flex; gap: 20px; align-items: center;">
                <div class="tab-wrapper">
                    <a href="?type=all" class="tab-link <?php echo $type == 'all' ? 'active' : ''; ?>">All History</a>
                    <a href="?type=return" class="tab-link <?php echo $type == 'return' ? 'active' : ''; ?>">Returns</a>
                    <a href="?type=release" class="tab-link <?php echo $type == 'release' ? 'active' : ''; ?>">Releases</a>
                </div>
                <button onclick="openModal()" class="btn-add"><i class="fa-solid fa-plus"></i> Manual Log Entry</button>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="logSearch" class="search-input" placeholder="Search hostname, asset, or agent...">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
        </div>

        <table class="data-table" id="movementTable">
            <thead>
                <tr>
                    <th>Asset Details</th>
                    <th>Identifier</th>
                    <th>Agent / Superior</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Operator</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="log-row">
                        <td>
                            <span style="font-weight:700; display:block; color:var(--text-main);"><?php echo htmlspecialchars($row['asset_name']); ?></span>
                            <span style="font-size:0.75rem; color:var(--text-gray); font-weight:600;"><?php echo htmlspecialchars($row['device_type']); ?></span>
                        </td>
                        <td>
                            <code style="color:var(--primary-orange); font-weight:700;"><?php echo htmlspecialchars($row['hostname']); ?></code>
                            <div style="font-size:0.7rem; color:var(--text-gray); font-weight:600;">SN: <?php echo htmlspecialchars($row['serial_number']); ?></div>
                        </td>
                        <td>
                            <?php if(!empty($row['agent_name'])): ?>
                                <div style="font-weight:700; color:var(--neon-blue); font-size:0.85rem;"><?php echo htmlspecialchars($row['agent_name']); ?></div>
                                <div style="font-size:0.7rem; color:var(--text-gray); font-weight:600;"><?php echo htmlspecialchars($row['agent_email']); ?></div>
                                <div style="font-size:0.65rem; color:var(--text-gray); font-weight:600;">Sup: <?php echo htmlspecialchars($row['immediate_supmail']); ?></div>
                            <?php else: ?>
                                <span style="color:var(--text-gray); font-style:italic; font-size:0.75rem;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(strtolower($row['location']) == 'return'): ?>
                                <span class="badge badge-return"><i class="fa-solid fa-rotate-left"></i> RETURNED</span>
                            <?php else: ?>
                                <span class="badge badge-release"><i class="fa-solid fa-truck-ramp-box"></i> RELEASED</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.85rem; color:var(--text-gray); font-weight:600;">
                            <?php 
                                $date = $row['return_date'] ?? $row['release_date'] ?? $row['created_at'];
                                echo date('M d, y', strtotime($date)); 
                            ?>
                        </td>
                        <td style="font-size:0.85rem; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($row['moved_by']); ?></td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-edit" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-agent="<?php echo htmlspecialchars($row['agent_name'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($row['agent_email'] ?? ''); ?>"
                                    data-sup="<?php echo htmlspecialchars($row['immediate_supmail'] ?? ''); ?>"
                                    data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>"
                                    onclick="openEditModal(this)">
                                <i class="fa-solid fa-terminal"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding:60px; color:var(--text-gray);">NO TRANSACTION DATA DETECTED.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="manualModal" class="modal">
        <div class="modal-content">
            <h3 style="color:var(--primary-orange); margin-top:0;">Manual Log Entry</h3>
            <form id="manualLogForm">
                <div class="form-group">
                    <label>Hostname</label>
                    <input type="text" name="hostname" placeholder="e.g., DAL-LAP-123" required>
                </div>
                <div class="form-group">
    <label>Asset Number</label>
    <input type="text" name="asset_name" placeholder="e.g., ASSET-2026-001" required>
</div>

                <div class="form-group">
                <label>Serial Number</label>
                <input type="text" name="serial_number" placeholder="e.g., S3R14L-XYZ" required>
            </div>
                <div class="form-group">
                    <label>Log Type</label>
                    <select name="location">
                        <option value="Release">Release (Outward)</option>
                        <option value="Return">Return (Inward)</option>
                    </select>
                </div>
                
                <div class="agent-info-box">
                    <div class="form-group"><label>Agent Name</label><input type="text" name="agent_name"></div>
                    <div class="form-group"><label>Agent Email</label><input type="email" name="agent_email"></div>
                    <div class="form-group"><label>Immediate Superior Email</label><input type="email" name="immediate_supmail"></div>
                </div>

                <div class="form-group">
    <label>Device Type</label>
    <select name="device_type">
        <option value="Laptop">Laptop</option>
        <option value="Desktop">Desktop</option>
    </select>
</div>

                <div class="form-group">
                    <label>Internal Remarks</label>
                    <textarea name="remarks" placeholder="Optional sync notes..."></textarea>
                </div>
                <button type="submit" class="btn-save">INITIALIZE SYNC</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:var(--text-gray); margin-top:15px; cursor:pointer;">Abort</button>
            </form>
        </div>
    </div>

   <div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="color:var(--neon-blue); margin-top:0;">Update Logistics Log</h3>
        <form id="editLogForm">
            <input type="hidden" name="movement_id" id="edit_movement_id">
            <input type="hidden" name="hostname" id="edit_hostname">
            <input type="hidden" name="device_type" id="edit_device_type">

            <div class="form-group">
                <label>Asset Name</label>
                <input type="text" name="asset_name" id="edit_asset_name" required>
            </div>

            <div class="form-group">
                <label>Serial Number</label>
                <input type="text" name="serial_number" id="edit_serial_number">
            </div>

            <div class="form-group">
                <label>Logistics State</label>
                <select name="location" id="edit_location">
                    <option value="Release">Release</option>
                    <option value="Return">Return</option>
                </select>
            </div>

            <div class="agent-info-box">
                <div class="form-group"><label>Agent Name</label><input type="text" name="agent_name" id="edit_agent_name"></div>
                <div class="form-group"><label>Agent Email</label><input type="email" name="agent_email" id="edit_agent_email"></div>
                <div class="form-group"><label>Immediate Superior Email</label><input type="email" name="immediate_supmail" id="edit_sup_email"></div>
            </div>
            
            <div class="form-group">
                <label>Internal Remarks</label>
                <textarea name="remarks" id="edit_remarks" placeholder="Update sync notes..."></textarea>
            </div>
            
            <button type="submit" class="btn-save" style="background:var(--neon-blue);">REWRITE LOG DATA</button>
            <button type="button" onclick="closeEditModal()" style="width:100%; background:none; border:none; color:var(--text-gray); margin-top:15px; cursor:pointer;">Cancel</button>
        </form>
    </div>
</div>

    <script>
    // Search Logic
    document.getElementById('logSearch').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('.log-row');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    document.getElementById('logSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('.log-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

function openModal() { document.getElementById('manualModal').style.display = 'flex'; }
function closeModal() { document.getElementById('manualModal').style.display = 'none'; }
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function openEditModal(btn) {
    const row = btn.closest('tr');
    document.getElementById('edit_movement_id').value = btn.getAttribute('data-id');
    document.getElementById('edit_asset_name').value = row.cells[0].querySelector('span').innerText.trim();
    
    // Get the device type from the table cell
    const deviceType = row.cells[0].querySelector('span:last-child').innerText.trim();
    document.getElementById('edit_device_type').value = deviceType; 
    
    document.getElementById('edit_hostname').value = row.cells[1].querySelector('code').innerText.trim();
    document.getElementById('edit_serial_number').value = row.cells[1].querySelector('div').innerText.replace('SN: ', '').trim();
    
    document.getElementById('edit_agent_name').value = btn.getAttribute('data-agent');
    document.getElementById('edit_agent_email').value = btn.getAttribute('data-email');
    document.getElementById('edit_sup_email').value = btn.getAttribute('data-sup');
    document.getElementById('edit_remarks').value = btn.getAttribute('data-remarks');

    const moveText = row.cells[3].innerText.includes('RETURN') ? 'Return' : 'Release';
    document.getElementById('edit_location').value = moveText;
    document.getElementById('editModal').style.display = 'flex';
}

// Updated form logic - No longer need window.currentDeviceType
document.addEventListener('DOMContentLoaded', function() {
    const formIds = ['manualLogForm', 'editLogForm'];
    
    formIds.forEach(id => {
        const form = document.getElementById(id);
        if(!form) return;

        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            data.action = (id === 'manualLogForm') ? 'manual_sync_update' : 'update_movement_sync';

            try {
                const resp = await fetch('movement_crud.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await resp.json();
                if(result.success) {
                    location.reload(); 
                } else {
                    alert("Database Error: " + result.message);
                }
            } catch (error) { 
                alert("Critical Error: The server returned an invalid response."); 
            }
        };
    });
});

// Unified Form Submission Logic
document.addEventListener('DOMContentLoaded', function() {
    const formIds = ['manualLogForm', 'editLogForm'];
    
    formIds.forEach(id => {
        const form = document.getElementById(id);
        if(!form) return;

        form.onsubmit = async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Assign the correct action for PHP
            data.action = (id === 'manualLogForm') ? 'manual_sync_update' : 'update_movement_sync';
            
            // Attach device type if editing
            if(id === 'editLogForm') {
                data.device_type = window.currentDeviceType || 'Laptop';
            }

            try {
                const resp = await fetch('movement_crud.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const text = await resp.text();
                console.log("Server Response:", text); // Check console (F12) if it fails

                const result = JSON.parse(text);
                if(result.success) {
                    location.reload(); 
                } else {
                    alert("Database Error: " + result.message);
                }
            } catch (error) { 
                console.error("Fetch Error:", error);
                alert("Critical Error: The server returned an invalid response. Check the Network tab in F12."); 
            }
        };
    });
});
    </script>
</body>
</html>