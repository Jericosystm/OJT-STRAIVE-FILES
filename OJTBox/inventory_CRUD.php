<?php
header('Content-Type: application/json');
require_once 'db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$action = $data['action'] ?? '';
// Default to 'Active' if status is missing
$target_status = $data['status'] ?? 'Active';
$remarks = $data['remarks'] ?? '';
$table = "inventory_items"; 

$response = ["success" => false];

switch ($action) {
    case 'create':
        // 1. Check for Host Name duplicate
        $host_check = $conn->prepare("SELECT id FROM $table WHERE host_name = ?");
        $host_check->bind_param("s", $data['host_name']);
        $host_check->execute();
        $host_check->store_result();

        // 2. Check for Asset Name duplicate
        $asset_check = $conn->prepare("SELECT id FROM $table WHERE asset_name = ?");
        $asset_check->bind_param("s", $data['asset_name']);
        $asset_check->execute();
        $asset_check->store_result();

        if ($host_check->num_rows > 0 && $asset_check->num_rows > 0) {
            $response = ["success" => false, "message" => "Duplicate Error: Both Asset Name and Host Name already exist."];
        } 
        elseif ($host_check->num_rows > 0) {
            $response = ["success" => false, "message" => "Duplicate Error: This Host Name is already in use."];
        } 
        elseif ($asset_check->num_rows > 0) {
            $response = ["success" => false, "message" => "Duplicate Error: This Asset Name already exists."];
        } 
        else {
            // No duplicates found, proceed with insertion
            // Added 'remarks' to the query
            $stmt = $conn->prepare("INSERT INTO $table (asset_name, host_name, serial_num, device_type, status, remarks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", 
                $data['asset_name'], 
                $data['host_name'], 
                $data['serial_num'], 
                $data['device_type'], 
                $target_status,
                $remarks
            );
            
            if ($stmt->execute()) {
                $response = ["success" => true];
            } else {
                $response = ["success" => false, "message" => "Database error: " . $conn->error];
            }
        }
        break;

    case 'update':
        $id = $data['id'];
        // Update query now includes 'remarks'
        // 'updated_at' will update automatically in MySQL if configured as ON UPDATE CURRENT_TIMESTAMP
        $stmt = $conn->prepare("UPDATE $table SET asset_name=?, host_name=?, serial_num=?, device_type=?, status=?, remarks=? WHERE id=?");
        $stmt->bind_param("ssssssi", 
            $data['asset_name'], 
            $data['host_name'], 
            $data['serial_num'], 
            $data['device_type'], 
            $target_status, 
            $remarks,
            $id
        );
        
        if ($stmt->execute()) {
            $response = ["success" => true];
        } else {
            $response = ["success" => false, "message" => "Update failed: " . $conn->error];
        }
        break;

    case 'delete':
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
        if ($stmt->execute()) {
            $response = ["success" => true];
        }
        break;
        
    default:
        $response = ["success" => false, "message" => "Invalid action."];
        break;
}

echo json_encode($response);
$conn->close();
?>