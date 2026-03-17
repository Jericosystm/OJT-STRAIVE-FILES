<?php
header('Content-Type: application/json');

// --- DATABASE CONFIGURATION ---
$host     = "localhost";
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password
$dbname   = "ojt project"; // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

/**
 * NOTE: Ensure your table name in MySQL is 'machine_movement'.
 * Columns: id, item_name, moved_by, date_moved, status
 */
$sql = "SELECT id, item_name, moved_by, date_moved, status FROM machine_movement";
$result = $conn->query($sql);

$data = [];

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    // Output the resulting array as JSON
    echo json_encode($data);
} else {
    // If the table doesn't exist yet or the query fails
    echo json_encode(["error" => "Query failed: " . $conn->error]);
}

$conn->close();
?>