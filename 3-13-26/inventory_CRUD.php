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

// --- Machine Movement & Null Logic ---
// 1. Logic: If status is Vacant or Dispose, movement MUST be null
$machine_movement = ($status === 'Active') ? ($data['machine_movement'] ?? null) : null;

// 2. Ensure empty strings from the frontend are converted to actual NULLs for SQL
if ($machine_movement === '') { $machine_movement = null; }

$response = ["success" => false];

try {
    if ($action === 'create') {
        // Table name updated to inventory_items per your SQL
        $sql = "INSERT INTO inventory_items (asset_name, host_name, serial_num, device_type, status, machine_movement, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $asset_name, $host_name, $serial_num, $device_type, $status, $machine_movement, $remarks);
        
        if ($stmt->execute()) $response = ["success" => true];
        else throw new Exception($stmt->error);

    } elseif ($action === 'update' && $id) {
        $sql = "UPDATE inventory_items SET asset_name=?, host_name=?, serial_num=?, device_type=?, status=?, machine_movement=?, remarks=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $asset_name, $host_name, $serial_num, $device_type, $status, $machine_movement, $remarks, $id);
        
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