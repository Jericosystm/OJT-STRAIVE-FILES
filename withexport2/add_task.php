<?php
session_start();
require_once 'db.php';

// Security
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if we are EDITING an existing task
$task = [
    'id' => '',
    'task_description' => '',
    'assigned_to' => '',
    'status' => 'Pending',
    'date_given' => date('Y-m-d'),
    'date_completed' => '',
    'comment' => ''
];

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $task = $row;
    }
}

// Capture return state from URL (if coming from archive with filters)
$referer = $_SERVER['HTTP_REFERER'] ?? 'taskbox.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | <?php echo $task['id'] ? 'Edit' : 'New'; ?> Task</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --text-main: #ffffff;
            --border-color: #222222;
            --input-bg: #151515;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .form-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        h2 { margin-top: 0; color: var(--primary-orange); }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #a0a0a0; }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: white;
            box-sizing: border-box;
            outline: none;
        }

        input:focus { border-color: var(--primary-orange); }

        .btn-row { display: flex; gap: 10px; margin-top: 30px; }
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }

        .btn-save { background: var(--primary-orange); color: white; }
        .btn-cancel { background: #222; color: #a0a0a0; text-decoration: none; text-align: center; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>

<div class="form-card">
    <h2><i class="fa-solid fa-list-check"></i> <?php echo $task['id'] ? 'Edit Task' : 'New Task'; ?></h2>
    
    <form action="task_CRUD.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(basename($referer)); ?>">

        <div class="form-group">
            <label>Task Description</label>
            <input type="text" name="task_description" value="<?php echo htmlspecialchars($task['task_description']); ?>" required placeholder="What needs to be done?">
        </div>

        <div class="form-group">
            <label>Assigned To</label>
            <input type="text" name="assigned_to" value="<?php echo htmlspecialchars($task['assigned_to']); ?>" required placeholder="Name of Tech Support">
        </div>

        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Status</label>
                <select name="status">
                    <option value="Pending" <?php echo $task['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Done" <?php echo $task['status'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Date Given</label>
                <input type="date" name="date_given" value="<?php echo $task['date_given']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Comment / Note (Optional)</label>
            <textarea name="comment" rows="3"><?php echo htmlspecialchars($task['comment']); ?></textarea>
        </div>

        <div class="btn-row">
            <a href="<?php echo $referer; ?>" class="btn btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-save">Save Task</button>
        </div>
    </form>
</div>

</body>
</html>