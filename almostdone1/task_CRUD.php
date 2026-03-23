<?php
session_start();
require_once 'db.php';

// --- NEW REDIRECT LOGIC ---
// This checks if we know where the user came from. 
// If they came from taskbox.php, it will send them back there.
$referer = $_SERVER['HTTP_REFERER'] ?? 'taskbox.php';

// --- 1. HANDLE CHECKBOX TOGGLE (GET) ---
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $status = $_GET['status'];
    $tab = $_GET['tab'] ?? 'all';
    $dateComp = ($status === 'Done') ? date('Y-m-d') : null;

    $stmt = $conn->prepare("UPDATE tasks SET status = ?, date_completed = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ssi", $status, $dateComp, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        // Send back to the exact page + the tab they were viewing
        header("Location: " . $referer . (strpos($referer, '?') !== false ? "&tab=$tab" : "?tab=$tab"));
        exit();
    }
}

// --- 2. HANDLE DELETE (GET) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $referer);
    exit();
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
    } else {
        // INSERT NEW
        $sql = "INSERT INTO tasks (task_description, assigned_to, status, date_given, date_completed, comment, last_updated) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $desc, $assign, $status, $given, $comp, $comment);
    }

    if ($stmt->execute()) {
        $stmt->close();
        // Success! Go back to taskbox.php
        header("Location: taskbox.php");
        exit();
    } else {
        die("Database Error: " . $stmt->error);
    }
}

// Global Fallback
header("Location: taskbox.php");
exit();