<?php
// Prevent any PHP errors from echoing as HTML (which breaks JSON)
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'db.php';

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received or invalid JSON."]);
    exit;
}

$action = $data['action'] ?? '';
$id = $data['id'] ?? null;
$asset_name = $data['asset_name'] ?? '';
$host_name = $data['host_name'] ?? '';
$serial_num = $data['serial_num'] ?? '';
$device_type = $data['device_type'] ?? '';
$status = $data['status'] ?? 'Active';
$remarks = $data['remarks'] ?? '';
$location = $data['location'] ?? 'WFH';

// Logic: If WFH, force department and cubicle to be empty
$department = ($location === 'Onsite') ? ($data['department'] ?? '') : '';
$cubicle_number = ($location === 'Onsite') ? ($data['cubicle_number'] ?? '') : '';

$response = ["success" => false];

try {
    if ($action === 'create') {
        // 1. Duplicate Check for NEW assets
        $check = $conn->prepare("SELECT id FROM inventory_items WHERE asset_name = ? OR host_name = ?");
        $check->bind_param("ss", $asset_name, $host_name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Asset Name or Host Name already exists.");
        }

        $stmt = $conn->prepare("INSERT INTO inventory_items (asset_name, host_name, serial_num, device_type, status, remarks, location, department, cubicle_number, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssssssss", $asset_name, $host_name, $serial_num, $device_type, $status, $remarks, $location, $department, $cubicle_number);
        
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);

    } elseif ($action === 'update' && $id) {
        // 2. Duplicate Check for UPDATES (Exclude current ID)
        $check = $conn->prepare("SELECT id FROM inventory_items WHERE (asset_name = ? OR host_name = ?) AND id != ?");
        $check->bind_param("ssi", $asset_name, $host_name, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Asset Name or Host Name is already used by another record.");
        }

        $stmt = $conn->prepare("UPDATE inventory_items SET asset_name=?, host_name=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssssi", $asset_name, $host_name, $serial_num, $device_type, $status, $remarks, $location, $department, $cubicle_number, $id);
        
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);

    } elseif ($action === 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);
    } else {
        throw new Exception("Invalid action or missing ID.");
    }
} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
}

echo json_encode($response);
$conn->close();
?>