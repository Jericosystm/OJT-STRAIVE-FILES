<?php
session_start();
include 'db.php';

// 1. Get the role from the session FIRST
$user_role = $_SESSION['role'] ?? 'EUC User'; 

// 2. NOW use that variable to decide the link
if ($user_role === 'euc_admin') {
    $back_link = "index_admin.php";
} else {
    $back_link = "index_user.php";
}

// 3. Security Check (already in your code)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}



if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    if (empty($username) || empty($email) || empty($current_password)) {
        $error_msg = "Current password is required to save changes.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $result['password'])) {
            if (!empty($new_password)) {
                $hashed_new_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $update_stmt->bind_param("sssi", $username, $email, $hashed_new_pass, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $username, $email, $user_id);
            }

            if ($update_stmt->execute()) {
                $_SESSION['username'] = $username;
                $success_msg = "Account updated successfully!";
            } else {
                $error_msg = "Update failed. Email might already be taken.";
            }
        } else {
            $error_msg = "Incorrect current password.";
        }
    }
}

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | OJTBox</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff6600;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --border: #e2e8f0;
        }

        body { 
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .settings-wrapper {
            display: flex;
            justify-content: center;
            padding: 50px 20px;
        }

        .settings-card { 
            max-width: 380px; 
            width: 100%;
            background: #fff; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
            position: relative;
        }

        /* Updated Back Button Styling */
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--text-sub);
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .close-btn:hover { 
            background: #f1f5f9;
            color: #ef4444; 
        }

        .settings-header { text-align: center; margin-bottom: 25px; }
        .profile-icon {
            width: 50px;
            height: 50px;
            background: #fff5eb;
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.4rem;
        }

        .settings-header h2 { margin: 0; color: var(--text-main); font-size: 1.25rem; }
        .settings-header p { color: var(--text-sub); font-size: 0.85rem; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 600; 
            color: var(--text-main); 
            font-size: 0.8rem; 
        }
        
        .input-box { position: relative; }
        .input-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-group input { 
            width: 100%; 
            padding: 11px 12px 11px 40px; 
            border: 1.5px solid var(--border); 
            border-radius: 10px; 
            box-sizing: border-box;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 102, 0, 0.1);
        }

        .btn-save { 
            width: 100%; 
            padding: 13px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.2s;
            margin-top: 10px;
        }

        .btn-save:hover { background: #e65c00; transform: translateY(-1px); }

        .alert { 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 0.85rem; 
            text-align: center;
        }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 20px 0;
        }
    </style>
</head>
<body> 

    <?php include 'header.php'; ?>

    <div class="settings-wrapper">
        <div class="settings-card">
            <a href="javascript:history.back()" class="close-btn" title="Go Back">
                <i class="fa-solid fa-xmark"></i>
            </a>

            <div class="settings-header">
                <div class="profile-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h2>Security & Account</h2>
                <p>Update your credentials safely</p>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if($error_msg): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-box">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-box">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password (Optional)</label>
                    <div class="input-box">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="new_password" placeholder="••••••••">
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label>Verify Current Password</label>
                    <div class="input-box">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="current_password" required placeholder="Type current password to save">
                    </div>
                </div>

                <button type="submit" name="update_account" class="btn-save">
                    Confirm Changes
                </button>
            </form>
        </div>
    </div>

</body>
</html>