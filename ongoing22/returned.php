<?php
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password is empty
$dbname = "ojt project"; // Your database name from the screenshot

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Fetch data from the table you created
$sql = "SELECT id, item_name, returned_by, date_returned, status FROM returned_items";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Output data as JSON
echo json_encode($data);

$conn->close();
?>