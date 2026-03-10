<?php
session_start();
require_once 'db.php';

// Access Control: Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission to create a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_user = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']);
    $new_name = trim($_POST['new_full_name']);
    $new_role = $_POST['new_role'];
    // Hash the password so it works with password_verify in auth.php
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $insert_sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssss", $new_user, $new_email, $new_pass, $new_name, $new_role);

    if ($stmt->execute()) {
        $message = "<div class='alert success'>User <strong>$new_user</strong> created successfully!</div>";
    } else {
        $message = "<div class='alert danger'>Error: " . $conn->error . "</div>";
    }
}

// Fetch all users to display in the table
$users_result = $conn->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .admin-container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .header h2 { color: #333; margin: 0; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        form.create-user-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; background: #fafafa; padding: 20px; border-radius: 10px; border: 1px solid #eee; }
        form input, form select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-create { background: #ff6600; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th { background: #333; color: white; text-align: left; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: #ff6600; color: white; }
        .role-user { background: #e0e0e0; color: #555; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="header">
        <h2><i class="fa-solid fa-user-shield"></i> Admin Dashboard</h2>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i> Logout</a>
    </div>

    <?php echo $message; ?>

    <h3>Create New User</h3>
    <form class="create-user-form" method="POST">
        <input type="text" name="new_username" placeholder="Username" required>
        <input type="email" name="new_email" placeholder="Email Address" required>
        <input type="text" name="new_full_name" placeholder="Full Name" required>
        <input type="password" name="new_password" placeholder="Password" required>
        <select name="new_role">
            <option value="user">User (Standard)</option>
            <option value="admin">Admin (Full Access)</option>
        </select>
        <button type="submit" name="create_user" class="btn-create">Add User</button>
    </form>

    <h3>User Registry</h3>
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <span class="role-badge <?php echo ($user['role'] == 'admin') ? 'role-admin' : 'role-user'; ?>">
                        <?php echo $user['role']; ?>
                    </span>
                </td>
                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>