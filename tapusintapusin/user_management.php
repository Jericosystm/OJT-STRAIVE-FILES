<?php
session_start();
include 'db.php';

// Security: Only 'euc_admin' should access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'euc_admin') {
    header("Location: index_user.php");
    exit();
}

// --- LOGIC: DELETE USER ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']); 
    $stmt->execute();
    header("Location: user_management.php?msg=deleted");
    exit();
}

// --- LOGIC: CREATE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user, $email, $pass, $role);
    $stmt->execute();
    header("Location: user_management.php?msg=created");
    exit();
}

// --- LOGIC: UPDATE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $user, $email, $pass, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $user, $email, $role, $id);
    }
    $stmt->execute();
    header("Location: user_management.php?msg=updated");
    exit();
}

$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM users WHERE id = $edit_id");
    $edit_user = $res->fetch_assoc();
}

$result = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Access Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Default Dark Mode */
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.4);
            --bg: #030303;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #FFFFFF;
            --text-muted: rgba(255, 255, 255, 0.5);
            --neon-blue: #00d4ff;
            --neon-red: #ff3131;
            --neon-purple: #bc13fe;
        }

        /* Light Mode Overrides */
        [data-theme="light"] {
            --bg: #F5F5F7;
            --card-bg: #FFFFFF;
            --card-hover: #E8E8ED;
            --border: rgba(0, 0, 0, 0.1);
            --text-main: #1D1D1F;
            --text-muted: #6E6E73;
        }

        @keyframes pageReveal {
            from { opacity: 0; transform: translateY(20px) scale(0.98); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        @keyframes staggerIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.05), transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(255, 102, 0, 0.03), transparent 40%);
            background-attachment: fixed;
            min-height: 100vh;
            animation: pageReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 40px; }
        
        .page-header { margin-bottom: 40px; animation: staggerIn 0.6s ease forwards; }
        .page-header h1 { font-weight: 800; font-size: 2.5rem; margin: 0; letter-spacing: -1.5px; }
        .page-header p { color: var(--text-muted); margin-top: 5px; font-weight: 600; }

        .form-card { 
            background: var(--card-bg); 
            padding: 30px; 
            border-radius: 28px; 
            margin-bottom: 40px; 
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
            animation: staggerIn 0.7s ease forwards;
            transition: transform 0.4s ease, background 0.4s ease;
        }
        
        .form-card:hover { transform: translateY(-5px); background: var(--card-hover); }

        .form-card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: <?php echo $edit_user ? 'var(--neon-purple)' : 'var(--primary)'; ?>;
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
        
        .form-group label { display: block; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 8px; font-weight: 800; letter-spacing: 1px; }
        
        input, select { 
            background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: var(--text-main); padding: 12px 16px; border-radius: 12px; width: 100%; box-sizing: border-box;
            transition: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        [data-theme="light"] input, [data-theme="light"] select { background: #fff; }

        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 15px var(--primary-glow); }

        .btn { padding: 12px 20px; border-radius: 12px; font-weight: 800; cursor: pointer; border: none; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit { background: var(--primary); color: white; width: 100%; justify-content: center; }
        .btn-update { background: var(--neon-purple); color: white; width: 100%; justify-content: center; }
        
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .user-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; animation: staggerIn 0.8s ease forwards; }
        .user-table th { text-align: left; padding: 0 20px; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .user-table tr { transition: transform 0.3s ease; }
        .user-table tbody tr:hover { transform: scale(1.01); }
        
        .user-table td { background: var(--card-bg); padding: 20px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); backdrop-filter: blur(10px); }
        .user-table td:first-child { border-left: 1px solid var(--border); border-radius: 20px 0 0 20px; }
        .user-table td:last-child { border-right: 1px solid var(--border); border-radius: 0 20px 20px 0; }

        .badge { 
            padding: 6px 14px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; border: 1px solid; display: inline-block; text-align: center; letter-spacing: 0.5px;
        }
        .badge-admin { color: var(--neon-blue); border-color: var(--neon-blue); background: rgba(0, 212, 255, 0.1); }
        .badge-user { color: var(--text-main); border-color: var(--border); background: var(--card-hover); }
        
        .btn-edit { color: var(--neon-blue); background: rgba(0, 212, 255, 0.1); border: 1px solid var(--neon-blue); padding: 8px 12px; }
        .btn-del { color: var(--neon-red); background: rgba(255, 49, 49, 0.1); border: 1px solid var(--neon-red); padding: 8px 12px; }
        
        .cancel-link { color: var(--text-muted); font-size: 0.8rem; text-decoration: none; display: block; text-align: center; margin-top: 15px; font-weight: 700; transition: 0.3s; }
        .cancel-link:hover { color: var(--neon-red); }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <p style="color: var(--primary); font-weight: 800; font-size: 0.75rem; letter-spacing: 5px; text-transform: uppercase; margin-bottom: 10px;">Security Framework</p>
            <h1>Access Control List</h1>
        </div>
        
        <div class="form-card">
            <h3 style="margin-top: 0; font-size: 0.9rem; font-weight: 800; letter-spacing: 1px; color: <?php echo $edit_user ? 'var(--neon-purple)' : 'var(--primary)'; ?>; text-transform: uppercase; margin-bottom: 25px;">
                <i class="fa-solid <?php echo $edit_user ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
                <?php echo $edit_user ? 'Reconfiguring System Identity' : 'Register New User'; ?>
            </h3>
            
            <form method="POST" class="form-grid">
                <?php if($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" placeholder="user@company.com">
                </div>

                <div class="form-group">
                    <label>Access Key (Password)</label>
                    <input type="password" name="password" placeholder="<?php echo $edit_user ? 'Leave blank to keep current' : '••••••••'; ?>" <?php echo $edit_user ? '' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label>Privilege Level</label>
                    <select name="role">
                        <option value="euc_user" <?php echo ($edit_user && $edit_user['role'] == 'euc_user') ? 'selected' : ''; ?>>EUC User</option>
                        <option value="euc_admin" <?php echo ($edit_user && $edit_user['role'] == 'euc_admin') ? 'selected' : ''; ?>>EUC Admin</option>
                    </select>
                </div>

                <div>
                    <button type="submit" name="<?php echo $edit_user ? 'update_user' : 'create_user'; ?>" class="btn <?php echo $edit_user ? 'btn-update' : 'btn-submit'; ?>">
                        <?php echo $edit_user ? 'APPLY CHANGES' : 'GRANT ACCESS'; ?>
                    </button>
                    <?php if($edit_user): ?>
                        <a href="user_management.php" class="cancel-link">ABORT OPERATION</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <table class="user-table">
            <thead>
                <tr>
                    <th>UID</th>
                    <th>Identity</th>
                    <th>Communication</th>
                    <th>Roles</th>
                    <th style="text-align: right;">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-family: 'JetBrains Mono'; color: var(--primary); font-weight: 800;">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td><strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['role'] === 'euc_admin') ? 'badge-admin' : 'badge-user'; ?>">
                            <?php 
                                $displayRole = strtoupper(str_replace('euc_', '', $row['role']));
                                echo !empty($displayRole) ? $displayRole : 'USER';
                            ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                            <a href="user_management.php?edit=<?php echo $row['id']; ?>" class="btn btn-edit" title="Edit User">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <a href="user_management.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-del" 
                               title="Delete User"
                               onclick="return confirm('DE-AUTHORIZE USER: Are you sure?')">
                                <i class="fa-solid fa-user-xmark"></i>
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; color: var(--neon-blue); font-weight: 800; text-transform: uppercase;">
                                <i class="fa-solid fa-circle-check"></i> Active Session
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>