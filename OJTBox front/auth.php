<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This input could be the username or the email address
    $login_input = trim($_POST['username']); 
    $password_input = $_POST['password'];

    // Updated Query: Check both username and email columns
    $sql = "SELECT id, username, email, password, full_name FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify the password against the stored hash
        if (password_verify($password_input, $row['password'])) {
            // Login Success!
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            
            header("Location: index.php");
            exit();
        } else {
            // Found user, but wrong password
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // No user found with that username or email
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
}
?>