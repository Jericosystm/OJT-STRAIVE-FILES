<?php
// Database configuration
$host     = "localhost";
$username = "root";         // Default XAMPP username
$password = "";             // Default XAMPP password is empty
$dbname   = "ojt project";  // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If it fails, we output a JSON error so your JavaScript can read it
    die(json_encode([
        "success" => false, 
        "message" => "Database Connection Failed: " . $conn->connect_error
    ]));
}

// Set charset to utf8mb4 (supports special characters/emojis)
$conn->set_charset("utf8mb4");

// Note: We do NOT close the connection here. 
// Other files will include this and use the $conn variable.
?>