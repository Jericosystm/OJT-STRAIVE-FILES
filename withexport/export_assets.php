<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$current_tab = $_GET['tab'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$status_map = ['all' => 'All', 'inventory' => 'Active', 'storage' => 'storage', 'dispose' => 'Dispose'];
$target_status = $status_map[$current_tab] ?? 'Active';

// Reusing your existing query logic
if ($target_status === 'All') {
    if (!empty($search_query)) {
        $sql = "SELECT i.* FROM inventory_items i WHERE (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ? OR i.agent_name LIKE ?)";
        $params = ["%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"];
        $types = "sssss";
    } else {
        $sql = "SELECT i.* FROM inventory_items i";
        $params = [];
    }
} else {
    if (!empty($search_query)) {
        $sql = "SELECT i.* FROM inventory_items i WHERE i.status = ? AND (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ?)";
        $params = [$target_status, "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"];
        $types = "sssss";
    } else {
        $sql = "SELECT i.* FROM inventory_items i WHERE i.status = ?";
        $params = [$target_status];
        $types = "s";
    }
}

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=OJTBox_Assets_'.date('Y-m-d').'.csv');

$output = fopen('php://output', 'w');
// CSV Headers
fputcsv($output, ['Asset Name', 'Hostname', 'Serial Num', 'Device Type', 'Location', 'Status', 'Cubicle', 'Agent Name', 'Last Updated']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['asset_name'],
        $row['hostname'],
        $row['serial_num'],
        $row['device_type'],
        $row['location'],
        $row['status'],
        $row['cubicle_number'],
        $row['agent_name'],
        $row['updated_at']
    ]);
}
fclose($output);
exit();