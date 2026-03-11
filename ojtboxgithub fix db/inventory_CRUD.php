<?php
error_reporting(E_ALL); // Changed to see errors while debugging
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

$action    = $data['action'] ?? '';
$id        = $data['id'] ?? null;
$host_name = trim($data['host_name'] ?? '');
$status    = $data['status'] ?? 'Active';
$location  = $data['location'] ?? 'Onsite';
$department = $data['department'] ?? '';
// Use the variable name consistent with your JSON data
$cubicle   = ($location === 'Onsite') ? ($data['cubicle_number'] ?? '') : '';

$response = ["success" => false];

try {
    $conn->begin_transaction();

    if ($action === 'create' || $action === 'update') {
        
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO inventory_items (asset_name, host_name, serial_num, device_type, status, remarks, location, department, cubicle_number, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssssssss", $data['asset_name'], $host_name, $data['serial_num'], $data['device_type'], $status, $data['remarks'], $location, $department, $cubicle);
        } else {
            $stmt = $conn->prepare("UPDATE inventory_items SET asset_name=?, host_name=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("sssssssssi", $data['asset_name'], $host_name, $data['serial_num'], $data['device_type'], $status, $data['remarks'], $location, $department, $cubicle, $id);
        }
        
        if (!$stmt->execute()) throw new Exception("Inventory update failed.");

        // --- MIRROR SYNC LOGIC (The "Single Source of Truth" Part) ---
        
        // 1. Always clear this host_name from the map first to prevent duplicates
        $clearOld = $conn->prepare("UPDATE production_floor_map SET hostname = '', status = 'Vacant' WHERE hostname = ?");
        $clearOld->bind_param("s", $host_name);
        $clearOld->execute();

        // 2. If it's Active, Onsite, and has a Cubicle, assign it to the map
        if ($status === 'Active' && $location === 'Onsite' && !empty($cubicle)) {
            $syncMap = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied' WHERE cubicle_no = ?");
            $syncMap->bind_param("ss", $host_name, $cubicle);
            if (!$syncMap->execute()) throw new Exception("Map sync failed.");
        }

    } elseif ($action === 'delete' && $id) {
        // Get hostname before deleting to clean the map
        $getHost = $conn->prepare("SELECT host_name FROM inventory_items WHERE id = ?");
        $getHost->bind_param("i", $id);
        $getHost->execute();
        $oldHost = $getHost->get_result()->fetch_assoc()['host_name'] ?? '';

        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if (!empty($oldHost)) {
            $clearMap = $conn->prepare("UPDATE production_floor_map SET hostname = '', status = 'Vacant' WHERE hostname = ?");
            $clearMap->bind_param("s", $oldHost);
            $clearMap->execute();
        }
    }

    $conn->commit(); 
    $response = ["success" => true];

} catch (Exception $e) {
    $conn->rollback();
    $response = ["success" => false, "message" => $e->getMessage()];
}

echo json_encode($response);
$conn->close();
?>