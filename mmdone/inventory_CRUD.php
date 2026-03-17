<?php
ob_start(); 
session_start(); // Important for capturing Admin/Username
error_reporting(E_ALL); 
ini_set('display_errors', 0); 

header('Content-Type: application/json');
$response = ["success" => false, "message" => "Unknown error"];

try {
    require_once 'db.php';

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) throw new Exception("No data received.");

    $action         = $data['action'] ?? '';
    $id             = $data['id'] ?? null;
    $hostname       = !empty(trim($data['hostname'] ?? '')) ? trim($data['hostname']) : null;
    $location       = $data['location'] ?? 'WFH';
    $cubicle_input  = trim($data['cubicle_number'] ?? ''); 
    $cubicle_id     = $data['cubicle_id'] ?? null; 
    $switch_port    = trim($data['switch_port'] ?? 'Not Set'); 
    $asset_name     = $data['asset_name'] ?? '';
    $serial_num     = $data['serial_num'] ?? '';
    $device_type    = $data['device_type'] ?? '';
    $status         = $data['status'] ?? 'Active';
    $remarks        = $data['remarks'] ?? '';
    
    $inventory_dept = ($location === 'Onsite') ? ($data['department'] ?? '') : '';

    // --- 1. CREATE ACTION ---
    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO inventory_items (asset_name, hostname, department, cubicle_number, location, serial_num, device_type, status, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssss", $asset_name, $hostname, $inventory_dept, $cubicle_input, $location, $serial_num, $device_type, $status, $remarks);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;

            // LOG INITIAL MOVEMENT
            $release_date = (strtolower($location) === 'release') ? date('Y-m-d H:i:s') : null;
            $return_date  = (strtolower($location) === 'return') ? date('Y-m-d H:i:s') : null;
            $current_user = $_SESSION['username'] ?? 'Admin';

            $log_sql = "INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("issssssss", $new_id, $asset_name, $hostname, $serial_num, $device_type, $location, $release_date, $return_date, $current_user);
            $log_stmt->execute();

            if ($location === 'Onsite' && !empty($cubicle_id)) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ? WHERE id = ?");
                $sync->bind_param("ssi", $hostname, $switch_port, $cubicle_id);
                $sync->execute();
            }
            $response = ["success" => true];
        }

    // --- 2. UPDATE ACTION (Unified & Fixed) ---
    } elseif ($action === 'update' && $id) {
        // Fetch old location to see if it changed
        $check = $conn->prepare("SELECT location FROM inventory_items WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $old_location = $check->get_result()->fetch_assoc()['location'] ?? '';

        $stmt = $conn->prepare("UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $inventory_dept, $cubicle_input, $id);
        
        if ($stmt->execute()) {
            // Log movement ONLY if location changed
            if (strtolower($old_location) !== strtolower($location)) {
                $release_date = (strtolower($location) === 'release') ? date('Y-m-d H:i:s') : null;
                $return_date  = (strtolower($location) === 'return') ? date('Y-m-d H:i:s') : null;
                $current_user = $_SESSION['username'] ?? 'Admin';

                $log_sql = "INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("issssssss", $id, $asset_name, $hostname, $serial_num, $device_type, $location, $release_date, $return_date, $current_user);
                $log_stmt->execute();
            }

            // Always clear map first to avoid double-occupancy
            $clearAny = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $clearAny->bind_param("s", $hostname);
            $clearAny->execute();

            if ($location === 'Onsite' && !empty($cubicle_id)) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ? WHERE id = ?");
                $sync->bind_param("ssi", $hostname, $switch_port, $cubicle_id);
                $sync->execute();
            }
            $response = ["success" => true];
        }

    // --- 3. DELETE ACTION ---
    } elseif ($action === 'delete' && $id) {
        $cl = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = (SELECT hostname FROM inventory_items WHERE id = ?)");
        $cl->bind_param("i", $id);
        $cl->execute();

        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ["success" => true];
        }
    }

} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
}

ob_get_clean(); 
echo json_encode($response);
exit();