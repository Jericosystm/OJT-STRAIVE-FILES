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
    <title>OJTBox | Access Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --neon-purple: #bc13fe;
            --neon-red: #ff3131;
            --neon-blue: #00d4ff;
            --text-gray: #a0a0a0;
            --border-color: #222222;
        }

        body {
            background-color: var(--bg-dark);
            color: #ffffff;
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0;
        }

        .container { padding: 40px; max-width: 1200px; margin: auto; }
        
        .page-header { margin-bottom: 30px; border-left: 4px solid var(--primary-orange); padding-left: 20px; }
        .page-header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }
        .page-header p { color: var(--text-gray); margin: 5px 0 0 0; font-size: 0.9rem; }

        .form-card { 
            background: var(--card-bg); 
            padding: 25px; 
            border-radius: 12px; 
            margin-bottom: 40px; 
            border: 1px solid var(--border-color);
            position: relative;
        }
        .form-card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 3px;
            background: <?php echo $edit_user ? 'var(--neon-purple)' : 'var(--primary-orange)'; ?>;
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        
        .form-group label { display: block; color: var(--text-gray); font-size: 0.7rem; text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        input, select { 
            background: #1a1a1a; border: 1px solid #333; color: white; padding: 12px; border-radius: 6px; width: 100%; box-sizing: border-box;
        }

        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; border: none; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit { background: var(--primary-orange); color: white; width: 100%; justify-content: center; }
        .btn-update { background: var(--neon-purple); color: white; width: 100%; justify-content: center; }
        .btn-edit { color: var(--neon-blue); background: rgba(0, 212, 255, 0.1); border: 1px solid rgba(0, 212, 255, 0.2); }
        .btn-del { color: var(--neon-red); background: rgba(255, 49, 49, 0.1); border: 1px solid rgba(255, 49, 49, 0.2); }

        .user-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .user-table th { text-align: left; padding: 10px 20px; color: var(--text-gray); font-size: 0.75rem; text-transform: uppercase; }
        .user-table tr { background: #151515; }
        .user-table td { padding: 15px 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .user-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 8px 0 0 8px; }
        .user-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 8px 8px 0; }

        /* --- FIXED BADGE STYLES --- */
        .badge { 
            padding: 5px 12px; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            font-weight: 900; 
            border: 1px solid;
            display: inline-block;
            min-width: 70px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .badge-admin { 
            color: var(--neon-blue); 
            border-color: var(--neon-blue); 
            background: rgba(0, 212, 255, 0.1); 
            box-shadow: 0 0 5px rgba(0, 212, 255, 0.2);
        }

        .badge-user { 
            color: #ffffff; /* Brighter white so it's visible */
            border-color: #444444; 
            background: rgba(255, 255, 255, 0.05); 
        }
        
        .cancel-link { color: #666; font-size: 0.8rem; text-decoration: none; display: block; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-shield-halved"></i> Access Control List</h1>
            <p>Manage administrative and user-level permissions</p>
        </div>
        
        <div class="form-card">
            <h3 style="margin-top: 0; font-size: 1rem; color: <?php echo $edit_user ? 'var(--neon-purple)' : 'var(--primary-orange)'; ?>;">
                <i class="fa-solid <?php echo $edit_user ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
                <?php echo $edit_user ? 'RECONFIGURING SYSTEM IDENTITY' : 'REGISTER NEW EUC'; ?>
            </h3>
            
            <form method="POST" class="form-grid">
                <?php if($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Access Key (Password)</label>
                    <input type="password" name="password" placeholder="<?php echo $edit_user ? 'Leave blank to keep current' : 'Enter Password'; ?>" <?php echo $edit_user ? '' : 'required'; ?>>
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
                        <?php echo $edit_user ? 'SAVE CHANGES' : 'GRANT ACCESS'; ?>
                    </button>
                    <?php if($edit_user): ?>
                        <a href="user_management.php" class="cancel-link">Abort Operation</a>
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
                    <td style="color: #444; font-family: monospace;">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td style="font-size: 0.9rem; color: var(--text-gray);"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['role'] === 'euc_admin') ? 'badge-admin' : 'badge-user'; ?>">
                            <?php 
                                // Logic: Strip 'euc_' prefix. If the result is 'user', output 'USER'.
                                $displayRole = strtoupper(str_replace('euc_', '', $row['role']));
                                echo !empty($displayRole) ? $displayRole : 'USER';
                            ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                            <a href="user_management.php?edit=<?php echo $row['id']; ?>" class="btn btn-edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <a href="user_management.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-del" 
                               onclick="return confirm('DE-AUTHORIZE USER: Are you sure?')">
                                <i class="fa-solid fa-user-xmark"></i>
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.7rem; color: var(--neon-blue); text-transform: uppercase;">
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