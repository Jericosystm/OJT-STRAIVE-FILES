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

    // Extract Basic Data
    $action         = $data['action'] ?? '';
    $id             = $data['id'] ?? null;
    $hostname       = !empty(trim($data['hostname'] ?? '')) ? trim($data['hostname']) : null;
    $location       = $data['location'] ?? 'WFH';
    $cubicle_input  = trim($data['cubicle_number'] ?? ''); 
    $switch_port    = trim($data['switch_port'] ?? 'Not Set'); 
    $asset_name     = $data['asset_name'] ?? '';
    $serial_num     = $data['serial_num'] ?? '';
    $device_type    = $data['device_type'] ?? '';
    $status         = $data['status'] ?? 'Active';
    $remarks        = $data['remarks'] ?? '';
    $inventory_dept = trim($data['department'] ?? '');

    // --- LOGIC: REMOVE AGENT INFO FOR WFH AND ONSITE ---
    // Agent details are only relevant for Release (Out) and Return (In)
    if (in_array($location, ['Release', 'Return'])) {
        $agent_name  = trim($data['agent_name'] ?? '');
        $agent_email = trim($data['agent_email'] ?? '');
        $sup_email   = trim($data['immediate_supmail'] ?? '');
    } else {
        $agent_name  = '';
        $agent_email = '';
        $sup_email   = '';
    }

    // --- CUBICLE ID LOOKUP ---
    $cubicle_id = null;
    if ($location === 'Onsite' && !empty($cubicle_input)) {
        $lookUp = $conn->prepare("SELECT id FROM production_floor_map WHERE cubicle_no = ? LIMIT 1");
        $lookUp->bind_param("s", $cubicle_input);
        $lookUp->execute();
        $res = $lookUp->get_result()->fetch_assoc();
        $cubicle_id = $res['id'] ?? null;
    }

    // --- 1. CREATE ACTION ---
    if ($action === 'create') {
        $sql = "INSERT INTO inventory_items (asset_name, hostname, department, cubicle_number, location, serial_num, device_type, status, remarks, agent_name, agent_email, immediate_supmail, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssss", $asset_name, $hostname, $inventory_dept, $cubicle_input, $location, $serial_num, $device_type, $status, $remarks, $agent_name, $agent_email, $sup_email);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $current_user = $_SESSION['username'] ?? 'Admin';
            $now = date('Y-m-d H:i:s');

            // Log Initial Movement
            $release_date = (in_array(strtolower($location), ['release', 'onsite', 'wfh'])) ? $now : null;
            $return_date  = (strtolower($location) === 'return') ? $now : null;

            $log_sql = "INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("isssssssssss", $new_id, $asset_name, $hostname, $serial_num, $device_type, $location, $release_date, $return_date, $current_user, $agent_name, $agent_email, $sup_email);
            $log_stmt->execute();

            if ($location === 'Onsite' && $cubicle_id) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ?, department = ? WHERE id = ?");
                $sync->bind_param("sssi", $hostname, $switch_port, $inventory_dept, $cubicle_id);
                $sync->execute();
            }
            $response = ["success" => true];
        }

    // --- 2. UPDATE ACTION ---
    } elseif ($action === 'update' && $id) {
        $check = $conn->prepare("SELECT location, hostname FROM inventory_items WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $old_data = $check->get_result()->fetch_assoc();
        $old_location = $old_data['location'] ?? '';
        $old_hostname = $old_data['hostname'] ?? '';

        $sql = "UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, agent_name=?, agent_email=?, immediate_supmail=?, updated_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $inventory_dept, $cubicle_input, $agent_name, $agent_email, $sup_email, $id);
        
        if ($stmt->execute()) {
            $now = date('Y-m-d H:i:s');
            if (strtolower($old_location) !== strtolower($location) || in_array(strtolower($location), ['release', 'return'])) {
                $release_date = (in_array(strtolower($location), ['release', 'onsite', 'wfh'])) ? $now : null;
                $return_date  = (strtolower($location) === 'return') ? $now : null;
                $current_user = $_SESSION['username'] ?? 'Admin';

                $log_sql = "INSERT INTO machine_movement (asset_id, asset_name, hostname, serial_number, device_type, location, release_date, return_date, moved_by, agent_name, agent_email, immediate_supmail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("isssssssssss", $id, $asset_name, $hostname, $serial_num, $device_type, $location, $release_date, $return_date, $current_user, $agent_name, $agent_email, $sup_email);
                $log_stmt->execute();
            }

            // Sync with floor map
            $clearAny = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $clearAny->bind_param("s", $old_hostname);
            $clearAny->execute();

            if ($location === 'Onsite' && $cubicle_id) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ?, department = ? WHERE id = ?");
                $sync->bind_param("sssi", $hostname, $switch_port, $inventory_dept, $cubicle_id);
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