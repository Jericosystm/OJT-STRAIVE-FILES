<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $id = $_POST['id'] ?? ''; 
    $task = $_POST['task_description'];
    $assigned = $_POST['assigned_to'];
    $given = $_POST['date_given'];
    $completed = !empty($_POST['date_completed']) ? $_POST['date_completed'] : NULL;
    $status = $_POST['status'];
    $comment = $_POST['comment'];

    // 1. Server-side Validation: Character Limit
    // This acts as a safety net in case the JavaScript on the frontend is bypassed.
    if (mb_strlen($comment) > 250) {
        die("Error: Comment exceeds the 250-character limit. Please shorten your note and try again.");
    }

    if (!empty($id)) {
        // 2. Update Existing Task
        $stmt = $conn->prepare("UPDATE tasks SET task_description=?, assigned_to=?, date_given=?, date_completed=?, status=?, comment=? WHERE id=?");
        // "ssssssi" means 6 strings and 1 integer (the ID)
        $stmt->bind_param("ssssssi", $task, $assigned, $given, $completed, $status, $comment, $id);
    } else {
        // 3. Create New Task
        $stmt = $conn->prepare("INSERT INTO tasks (task_description, assigned_to, date_given, date_completed, status, comment) VALUES (?, ?, ?, ?, ?, ?)");
        // "ssssss" means 6 strings
        $stmt->bind_param("ssssss", $task, $assigned, $given, $completed, $status, $comment);
    }

    // 4. Execution and Redirect
    if ($stmt->execute()) {
        header("Location: taskbox.php?status=success");
    } else {
        // Log error if execution fails
        error_log("Database Error: " . $stmt->error);
        die("An error occurred while saving the task. Please try again.");
    }

    $stmt->close();
    $conn->close();
    exit();
}