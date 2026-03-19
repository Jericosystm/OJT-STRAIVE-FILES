<?php
session_start();
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'login'; 
    $password = $_POST['password'] ?? ''; 

    if ($action === 'register') {
        // Security check - matches 'euc_admin' based on your DB
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'euc_admin') {
            header("Location: login.php?error=unauthorized");
            exit();
        }

        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $role = $_POST['role'] ?? 'euc_user'; 
        $full_name = !empty($_POST['full_name']) ? trim($_POST['full_name']) : $username; 

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $reg_sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
        $reg_stmt = $conn->prepare($reg_sql);

        if ($reg_stmt) {
            $reg_stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
            if ($reg_stmt->execute()) {
                header("Location: user_management.php?success=user_added");
            } else {
                header("Location: user_management.php?error=failed");
            }
        }
        exit();

    } else {
        // --- LOGIN LOGIC ---
        $user_input = trim($_POST['username']); 
        
        $sql  = "SELECT id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $user_input, $user_input);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // REVISED REDIRECT: Changed 'admin' to 'euc_admin' to match your DB screenshot
                if ($user['role'] === 'euc_admin') {
                    header("Location: index_admin.php"); 
                } else {
                    header("Location: index_user.php"); 
                }
                exit();
            } else {
                header("Location: login.php?error=invalid_credentials");
                exit();
            }
        }
    }
}
?>