<?php
ob_start(); 
session_start(); 
error_reporting(E_ALL); 
ini_set('display_errors', 0); 

header('Content-Type: application/json');
$response = ["success" => false, "message" => "Unknown error"];

try {
    require_once 'db.php';

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) throw new Exception("No data received.");

    $action = $data['action'] ?? '';
    $current_user = $_SESSION['username'] ?? 'Admin';

    // --- 1. EDIT MOVEMENT & SYNC INVENTORY ---
    if ($action === 'update_movement_sync') {
        $move_id    = $data['movement_id'] ?? null;
        $hostname   = trim($data['hostname'] ?? '');
        $asset_name = trim($data['asset_name'] ?? '');
        $serial     = trim($data['serial_number'] ?? '');
        $type       = $data['device_type'] ?? 'Laptop';
        $location   = $data['location'] ?? 'Release';
        $status     = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';

        if (!$move_id) throw new Exception("Missing Movement ID.");

        $conn->begin_transaction();

        // Update the Movement Log record
        $upd_log = $conn->prepare("UPDATE machine_movement SET asset_name=?, serial_number=?, device_type=?, location=? WHERE id=?");
        $upd_log->bind_param("ssssi", $asset_name, $serial, $type, $location, $move_id);
        $upd_log->execute();

        // Sync changes back to the Master Inventory using Hostname
        $upd_inv = $conn->prepare("UPDATE inventory_items SET asset_name=?, serial_num=?, device_type=?, location=?, status=? WHERE hostname=?");
        $upd_inv->bind_param("ssssss", $asset_name, $serial, $type, $location, $status, $hostname);
        $upd_inv->execute();

        $conn->commit();
        $response = ["success" => true];

    // --- 2. MANUAL ADD & AUTO-CREATE ---
    } elseif ($action === 'manual_sync_update') {
        $hostname = trim($data['hostname'] ?? '');
        $location = $data['location'] ?? 'Release';
        $remarks  = $data['remarks'] ?? '';
        $status   = ($location === 'Return') ? 'Returning Unit' : 'Released/Deployed';

        if (empty($hostname)) throw new Exception("Hostname is required.");

        $conn->begin_transaction();

        $check = $conn->prepare("SELECT id, asset_name, serial_num, device_type FROM inventory_items WHERE hostname = ?");
        $check->bind_param("s", $hostname);
        $check->execute();
        $asset = $check->get_result()->fetch_assoc();

        if ($asset) {
            $asset_id = $asset['id'];
            $upd = $conn->prepare("UPDATE inventory_items SET location = ?, status = ?, remarks = ? WHERE id = ?");
            $upd->bind_param("sssi", $location, $status, $remarks, $asset_id);
            $upd->execute();
        } else {
            // Auto-create if not exists
            $ins = $conn->prepare("INSERT INTO inventory_items (hostname, asset_name, device_type, serial_num, location, status, remarks) VALUES (?, ?, 'Laptop', 'N/A', ?, ?, ?)");
            $ins->bind_param("sssss", $hostname, $hostname, $location, $status, $remarks);
            $ins->execute();
            $asset_id = $conn->insert_id;
            $asset = ['asset_name' => $hostname, 'serial_num' => 'N/A', 'device_type' => 'Laptop'];
        }

        $now = date('Y-m-d H:i:s');
        $rel_date = ($location === 'Release') ? $now : null;
        $ret_date = ($location === 'Return') ? $now : null;

        $log = $conn->prepare("INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $log->bind_param("issssssss", $asset_id, $asset['asset_name'], $hostname, $asset['serial_num'], $asset['device_type'], $location, $rel_date, $ret_date, $current_user);
        $log->execute();

        $conn->commit();
        $response = ["success" => true];
    }

} catch (Exception $e) {
    if (isset($conn)) { $conn->rollback(); }
    $response = ["success" => false, "message" => $e->getMessage()];
}

ob_get_clean(); 
echo json_encode($response);
exit();