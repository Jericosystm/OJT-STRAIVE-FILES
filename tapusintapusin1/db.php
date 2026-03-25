<?php
// Database configuration
$host     = "localhost";
$username = "root";         
$password = "";             
$dbname = "ojt project"; // Note the backticks inside the quotes
// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Determine if we should send a JSON response or a plain text one
    // This prevents breaking regular HTML pages while still helping CRUD scripts
    if (strpos($_SERVER['PHP_SELF'], '_crud.php') !== false || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        
        header('Content-Type: application/json');
        die(json_encode([
            "success" => false, 
            "message" => "Database Connection Failed: " . $conn->connect_error
        ]));
    } else {
        die("Database Connection Failed: " . $conn->connect_error);
    }
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>