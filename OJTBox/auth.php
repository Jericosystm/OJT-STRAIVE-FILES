<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['username']);
    $password_input = trim($_POST['password']); 

    // Search for the user - now including the 'role' column
    $sql = "SELECT id, username, email, password, full_name, role FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_input, $row['password'])) {
            
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role']; // Store role for access control
            
            // Redirect based on role
            if ($row['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>