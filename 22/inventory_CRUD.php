<?php
ob_start(); 
error_reporting(E_ALL); 
ini_set('display_errors', 0); // Keep 0 for production, set to 1 to see raw PHP errors

header('Content-Type: application/json');
$response = ["success" => false, "message" => "Unknown error"];

try {
    require_once 'db.php';

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) throw new Exception("No data received.");

    // --- Data Extraction ---
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
            if ($location === 'Onsite' && !empty($cubicle_id)) {
                // Clear hostname from any other seats first
                $clear = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
                $clear->bind_param("s", $hostname);
                $clear->execute();

                // Assign to the specific seat in San Antonio
                $sync = $conn->prepare("UPDATE production_floor_map SET hostname = ?, status = 'Occupied', switch_port = ? WHERE id = ?");
                $sync->bind_param("ssi", $hostname, $switch_port, $cubicle_id);
                $sync->execute();
            }
            $response = ["success" => true];
        } else {
            throw new Exception("Inventory Insert Failed: " . $stmt->error);
        }

    // --- 2. UPDATE ACTION ---
    
// --- 2. UPDATE ACTION (Force Sync Version) ---
    } elseif ($action === 'update' && $id) {
        
        // 1. Update the Inventory Record first
        $stmt = $conn->prepare("UPDATE inventory_items SET asset_name=?, hostname=?, serial_num=?, device_type=?, status=?, remarks=?, location=?, department=?, cubicle_number=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssssi", $asset_name, $hostname, $serial_num, $device_type, $status, $remarks, $location, $inventory_dept, $cubicle_input, $id);
        
        if ($stmt->execute()) {
            // 2. Clear this hostname from ANY other seat it might be in
            $clearAny = $conn->prepare("UPDATE production_floor_map SET hostname = NULL, status = 'Vacant' WHERE hostname = ?");
            $clearAny->bind_param("s", $hostname);
            $clearAny->execute();

            // 3. Force the update to the specific seat
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