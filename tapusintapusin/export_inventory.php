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

// Re-use your query logic to get the same results as the table
$params = [];
$types = "";

if ($target_status === 'All') {
    if (!empty($search_query)) {
        $like_param = "%$search_query%";
        $sql = "SELECT i.* FROM inventory_items i WHERE (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ? OR i.agent_name LIKE ?) ORDER BY i.updated_at DESC";
        $params = [$like_param, $like_param, $like_param, $like_param, $like_param];
        $types = "sssss";
    } else {
        $sql = "SELECT i.* FROM inventory_items i ORDER BY i.updated_at DESC";
    }
} else {
    if (!empty($search_query)) {
        $like_param = "%$search_query%";
        $sql = "SELECT i.* FROM inventory_items i WHERE i.status = ? AND (i.asset_name LIKE ? OR i.hostname LIKE ? OR i.serial_num LIKE ? OR i.cubicle_number LIKE ?) ORDER BY i.updated_at DESC";
        $params = [$target_status, $like_param, $like_param, $like_param, $like_param];
        $types = "sssss";
    } else {
        $sql = "SELECT i.* FROM inventory_items i WHERE i.status = ? ORDER BY i.updated_at DESC";
        $params = [$target_status];
        $types = "s";
    }
}

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Set Headers for Download
$filename = "OJTBox_Inventory_" . $current_tab . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Set CSV Columns
fputcsv($output, ['Asset Name', 'Hostname', 'Serial Number', 'Device Type', 'Status', 'Location', 'Cubicle', 'Department', 'Agent Name', 'Last Updated']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['asset_name'],
        $row['hostname'],
        $row['serial_num'],
        $row['device_type'],
        $row['status'],
        $row['location'],
        $row['cubicle_number'],
        $row['department'],
        $row['agent_name'],
        $row['updated_at']
    ]);
}

fclose($output);
exit();
?>