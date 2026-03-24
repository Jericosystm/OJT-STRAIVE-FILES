<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$type = $_GET['type'] ?? 'all';

// Match the logic in your movement logs page
if ($type === 'return') {
    $query = "SELECT * FROM machine_movement WHERE location LIKE 'Return%'";
} elseif ($type === 'release') {
    $query = "SELECT * FROM machine_movement WHERE location LIKE 'Release%'";
} else {
    $query = "SELECT * FROM machine_movement WHERE location IN ('Return', 'Release')";
}

$query .= " ORDER BY created_at DESC";
$result = $conn->query($query);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=OJTBox_Movement_Logs_'.date('Y-m-d').'.csv');

$output = fopen('php://output', 'w');

// CSV Column Headers
fputcsv($output, ['Asset Name', 'Device Type', 'Hostname', 'Serial Number', 'Agent Name', 'Agent Email', 'Log Type', 'Date', 'Operator']);

while ($row = $result->fetch_assoc()) {
    $date = $row['return_date'] ?? $row['release_date'] ?? $row['created_at'];
    fputcsv($output, [
        $row['asset_name'],
        $row['device_type'],
        $row['hostname'],
        $row['serial_number'],
        $row['agent_name'],
        $row['agent_email'],
        strtoupper($row['location']),
        date('M d, Y', strtotime($date)),
        $row['moved_by']
    ]);
}

fclose($output);
exit();