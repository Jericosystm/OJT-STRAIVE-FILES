<?php
session_start();
include 'db.php';

// Security: Only 'euc_admin' should access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'euc_admin') {
    header("Location: login.php");
    exit();
}

// --- LOGIC: DELETE USER ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']); // Prevent self-deletion
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
        // Update with new password
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $user, $email, $pass, $role, $id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $user, $email, $role, $id);
    }
    $stmt->execute();
    header("Location: user_management.php?msg=updated");
    exit();
}

// Logic for pre-filling the edit form
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM users WHERE id = $edit_id");
    $edit_user = $res->fetch_assoc();
}

// Fetch all users
$result = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | User Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { padding: 30px; max-width: 1200px; margin: auto; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #ff6600; color: white; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #fff9f5; }

        .btn { padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; cursor: pointer; border: none; transition: 0.3s; display: inline-block; }
        .btn-add { background: #ff6600; color: white; }
        .btn-edit { background: #e3f2fd; color: #1976d2; margin-right: 5px; }
        .btn-del { background: #ffeded; color: #d9534f; }
        .btn-del:hover { background: #d9534f; color: white; }
        
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
        .role-admin { background: #e3f2fd; color: #1976d2; }
        .role-user { background: #f1f8e9; color: #689f38; }

        .form-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #ff6600; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; }
        .cancel-link { color: #666; font-size: 0.9rem; text-decoration: none; margin-left: 10px; }
    </style>
</head>
<body>

     <?php include 'header.php'; ?>

    <div class="container">
        
        <div class="form-card">
            <h3>
                <i class="fa-solid <?php echo $edit_user ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i> 
                <?php echo $edit_user ? 'Edit User Credentials' : 'Create New Account'; ?>
            </h3>
            <form method="POST" class="form-grid" style="margin-top: 15px;">
                <?php if($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <input type="text" name="username" placeholder="Username" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>">
                <input type="email" name="email" placeholder="Email Address" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>">
                <input type="password" name="password" placeholder="<?php echo $edit_user ? 'Leave blank to keep current' : 'Password'; ?>" <?php echo $edit_user ? '' : 'required'; ?>>
                
                <select name="role">
                    <option value="euc_user" <?php echo ($edit_user && $edit_user['role'] == 'euc_user') ? 'selected' : ''; ?>>EUC User</option>
                    <option value="euc_admin" <?php echo ($edit_user && $edit_user['role'] == 'euc_admin') ? 'selected' : ''; ?>>EUC Admin</option>
                </select>

                <div>
                    <button type="submit" name="<?php echo $edit_user ? 'update_user' : 'create_user'; ?>" class="btn btn-add">
                        <?php echo $edit_user ? 'Update User' : 'Create User'; ?>
                    </button>
                    <?php if($edit_user): ?>
                        <a href="user_management.php" class="cancel-link">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="role-badge <?php echo ($row['role'] === 'euc_admin') ? 'role-admin' : 'role-user'; ?>">
                            <?php echo strtoupper($row['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                            <a href="user_management.php?edit=<?php echo $row['id']; ?>" class="btn btn-edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <a href="user_management.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-del" 
                               onclick="return confirm('Are you sure you want to delete this user?')">
                               <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #999;">(Logged In)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>