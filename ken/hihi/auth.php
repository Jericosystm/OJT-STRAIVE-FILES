<?php
session_start();
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'login'; 
    $password = $_POST['password'] ?? ''; 

    if ($action === 'register') {
        // Security check
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'euc_admin') {
            header("Location: login.php?error=unauthorized");
            exit();
        }

        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $role = $_POST['role'] ?? 'euc_user'; 
        // FIXED: Fallback to username if full_name is empty para hindi mag-error sa DB
        $full_name = !empty($_POST['full_name']) ? trim($_POST['full_name']) : $username; 

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Sinigurado na match ang columns sa screenshot mo (username, email, password, full_name, role)
        $reg_sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
        $reg_stmt = $conn->prepare($reg_sql);

        if ($reg_stmt) {
            // FIXED: Sinigurado ang 5 "s" para sa 5 parameters
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
        
        // SELECT id, username, password, role from the table
        $sql  = "SELECT id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $user_input, $user_input);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // password_verify will compare your plain text input to the HASH in the DB
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // STRICT REDIRECT LOGIC
                if ($user['role'] === 'admin') {
                    // Pupunta ito sa index.admin.php (ensure the file exists)
                    header("Location: index_admin.php"); 
                } else {
                    // Para sa normal users (user role)
                    header("Location: index_user.php"); 
                }
                exit();
            } else {
                // Kung mali ang password or hindi mahanap ang user
                header("Location: login.php?error=invalid_credentials");
                exit();
            }
        }
    }
}
?>