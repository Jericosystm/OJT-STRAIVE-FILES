<?php
session_start();
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Make sure these match the 'name' attribute in your HTML <input>
    $user_input = trim($_POST['username']); 
    $password   = $_POST['password']; 

    $sql  = "SELECT id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user_input, $user_input);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // 2. The Verification
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        // 3. Exact redirects for your files
        if ($user['role'] === 'euc_admin') {
            header("Location: index_admin.php");
        } else {
            header("Location: index_user.php");
        }
        exit();
    } else {
        // If it fails, we go back to login
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
}
?>