<?php
ob_start(); 
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
    $id = $data['id'] ?? null;
    $hostname = !empty(trim($data['hostname'] ?? '')) ? trim($data['hostname']) : null;
    $location = $data['location'] ?? 'WFH';
    $cubicle_input = trim($data['cubicle_number'] ?? ''); // From Inventory Form
    
    $asset_name = $data['asset_name'] ?? '';
    $serial_num = $data['serial_num'] ?? '';
    $device_type = $data['device_type'] ?? '';
    $status = $data['status'] ?? 'Active';
    $remarks = $data['remarks'] ?? '';
    $department = ($location === 'Onsite') ? ($data['department'] ?? '') : '';

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO inventory_items (asset_name, hostname, department, cubicle_number, location, serial_num, device_type, status, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssss", $asset_name, $hostname, $department, $cubicle_input, $location, $serial_num, $device_type, $status, $remarks);
        
        if ($stmt->execute()) {
            if ($location === 'Onsite' && !empty($cubicle_input)) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied' WHERE cubicle_no = ?");
                $sync->bind_param("ss", $hostname, $cubicle_input);
                $sync->execute();
            }
            $response = ["success" => true];
        }

    } elseif ($action === 'update' && $id) {
        $oldStmt = $conn->prepare("SELECT hostname FROM inventory_items WHERE id = ?");
        $oldStmt->bind_param("i", $id);
        $oldStmt->execute();
        $oldData = $oldStmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $department, $cubicle_input, $id);
        
        if ($stmt->execute()) {
            // 1. Clear old seat
            if (!empty($oldData['hostname'])) {
                $clear = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
                $clear->bind_param("s", $oldData['hostname']);
                $clear->execute();
            }
            // 2. Set new seat
            if ($location === 'Onsite' && !empty($cubicle_input)) {
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied' WHERE cubicle_no = ?");
                $sync->bind_param("ss", $hostname, $cubicle_input);
                $sync->execute();
            }
            $response = ["success" => true];
        }
    } elseif ($action === 'delete' && $id) {
        // Clear map before deleting asset
        $get = $conn->prepare("SELECT hostname FROM inventory_items WHERE id = ?");
        $get->bind_param("i", $id);
        $get->execute();
        $res = $get->get_result()->fetch_assoc();
        if ($res && $res['hostname']) {
            $cl = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $cl->bind_param("s", $res['hostname']);
            $cl->execute();
        }
        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $response = ["success" => true];
    }

} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
}

ob_get_clean(); 
echo json_encode($response);
exit();