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
// Note: Changed variable name to match schema 'cubicle_no'
$cubicle_no = ($location === 'Onsite') ? ($data['cubicle_no'] ?? '') : '';
$response = ["success" => false];

try {
    if ($action === 'create') {
        // Updated to all_assets_master
        $stmt = $conn->prepare("INSERT INTO all_assets_master (asset_name, host_name, serial_num, device_type, status, remarks, location, department, cubicle_no, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssssssss", $asset_name, $host_name, $serial_num, $device_type, $status, $remarks, $location, $department, $cubicle_no);
        
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);

    } elseif ($action === 'update' && $id) {
        // Simplified: One table update handles both inventory and floor map data
        $stmt = $conn->prepare("UPDATE all_assets_master SET asset_name=?, host_name=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_no=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssssi", $asset_name, $host_name, $serial_num, $device_type, $status, $remarks, $location, $department, $cubicle_no, $id);
        
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);

    } elseif ($action === 'delete' && $id) {
        // Updated to all_assets_master
        $stmt = $conn->prepare("DELETE FROM all_assets_master WHERE id = ?");
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