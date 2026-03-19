<?php
session_start();
require_once 'db.php';

// If someone tries to access this file directly via URL, send them back
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index_user.php"); 
    exit();
}

// 1. Handle Status Toggle (Checkbox on the task list)
if (isset($_POST['toggle_status'])) {
    $id = $_POST['id'];
    $current = $_POST['current_status'];
    $newStatus = ($current === 'Done') ? 'Pending' : 'Done';
    $dateComp = ($newStatus === 'Done') ? date('Y-m-d') : NULL;

    // ADDED: last_updated = CURRENT_TIMESTAMP to trigger the "Time Ago" update
    $stmt = $conn->prepare("UPDATE tasks SET status = ?, date_completed = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ssi", $newStatus, $dateComp, $id);
    $stmt->execute();
    $stmt->close();
}

// 2. Handle Deletion (Delete button in Modal)
elseif (isset($_POST['delete_task']) || (isset($_POST['action_type']) && $_POST['action_type'] === 'delete_task')) {
    $id = $_POST['id'] ?? $_POST['delete_task']; 
    
    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// 3. Handle Save (The Modal Form - Both Add and Edit)
elseif (isset($_POST['task_description'])) {
    $id = $_POST['id'] ?? '';
    $desc = $_POST['task_description'];
    $assign = $_POST['assigned_to'];
    $given = !empty($_POST['date_given']) ? $_POST['date_given'] : date('Y-m-d');
    $comp = !empty($_POST['date_completed']) ? $_POST['date_completed'] : NULL;
    $status = $_POST['status'];
    $comment = $_POST['comment'];

    // Validation: Comment limit
    if (mb_strlen($comment) > 250) {
        die("Error: Comment exceeds 250 characters.");
    }

    if (!empty($id)) {
        // UPDATE EXISTING - Added last_updated
        $stmt = $conn->prepare("UPDATE tasks SET task_description=?, assigned_to=?, date_given=?, date_completed=?, status=?, comment=?, last_updated=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("ssssssi", $desc, $assign, $given, $comp, $status, $comment, $id);
    } else {
        // INSERT NEW - Added last_updated
        $stmt = $conn->prepare("INSERT INTO tasks (task_description, assigned_to, date_given, date_completed, status, comment, last_updated) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("ssssss", $desc, $assign, $given, $comp, $status, $comment);
    }

    if (!$stmt->execute()) {
        die("Database Error: " . $stmt->error);
    }
    $stmt->close();
}

// GLOBAL REDIRECT
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: index_user.php");
}

$conn->close();
exit();