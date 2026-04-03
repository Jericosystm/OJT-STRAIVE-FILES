<?php
session_start();
require_once 'db.php';

// Security: Ensure user is logged in for tracking
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$referer = $_SERVER['HTTP_REFERER'] ?? 'taskbox.php';

// --- 0. TRACKER HELPER ---
// --- UPDATE THIS IN task_CRUD.php ---
function logActivity($conn, $user_id, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// --- 1. HANDLE CHECKBOX TOGGLE (GET) ---
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $status = $_GET['status'];
    $tab = $_GET['tab'] ?? 'all';
    $dateComp = ($status === 'Done') ? date('Y-m-d') : null;

    // Fetch task name for the log before updating
    $name_check = $conn->query("SELECT task_description FROM tasks WHERE id = $id")->fetch_assoc();
    $task_name = $name_check['task_description'] ?? "Task #$id";

    $stmt = $conn->prepare("UPDATE tasks SET status = ?, date_completed = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ssi", $status, $dateComp, $id);
    
    if ($stmt->execute()) {
        logActivity($conn, $user_id, "STATUS_CHANGE", "Changed '$task_name' to $status");
        $stmt->close();
        header("Location: " . $referer . (strpos($referer, '?') !== false ? "&tab=$tab" : "?tab=$tab"));
        exit();
    }
}

// --- 2. HANDLE DELETE (GET) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    // Fetch task name for the log before deleting
    $name_check = $conn->query("SELECT task_description FROM tasks WHERE id = $id")->fetch_assoc();
    $task_name = $name_check['task_description'] ?? "Task #$id";

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity($conn, $user_id, "DELETE_TASK", "Permanently deleted task: $task_name");
        $stmt->close();
        header("Location: " . $referer);
        exit();
    }
}

// --- 3. HANDLE MODAL SAVE / ADD TASK (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_description'])) {
    $id = $_POST['id'] ?? '';
    $desc = $_POST['task_description'];
    $assign = $_POST['assigned_to'];
    $status = $_POST['status'] ?? 'Pending';
    $given = !empty($_POST['date_given']) ? $_POST['date_given'] : date('Y-m-d');
    $comp = !empty($_POST['date_completed']) ? $_POST['date_completed'] : NULL;
    $comment = $_POST['comment'] ?? '';

    if (!empty($id)) {
        // UPDATE EXISTING
        $sql = "UPDATE tasks SET task_description=?, assigned_to=?, status=?, date_given=?, date_completed=?, comment=?, last_updated=CURRENT_TIMESTAMP WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $desc, $assign, $status, $given, $comp, $comment, $id);
        $action = "UPDATE_TASK";
        $log_msg = "Modified details for: $desc";
    } else {
        // INSERT NEW
        $sql = "INSERT INTO tasks (task_description, assigned_to, status, date_given, date_completed, comment, last_updated) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $desc, $assign, $status, $given, $comp, $comment);
        $action = "CREATE_TASK";
        $log_msg = "Created new task assigned to $assign: $desc";
    }

    if ($stmt->execute()) {
        logActivity($conn, $user_id, $action, $log_msg);
        $stmt->close();
        header("Location: taskbox.php");
        exit();
    } else {
        die("Database Error: " . $stmt->error);
    }
}

header("Location: taskbox.php");
exit();