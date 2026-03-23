<?php
session_start();
require_once 'db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'euc_admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Collect and Sanitize Input
    $tech_id    = (int)$_POST['tech_id'];
    $shift_date = $conn->real_escape_string($_POST['shift_date']);
    $shift_type = $conn->real_escape_string($_POST['shift_type']);
    
    // Handle Time In/Out (Set to NULL if it's a Rest Day/OFF)
    $time_in  = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
    $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;

    // 3. Check if a schedule already exists for this tech on this date
    $check = $conn->prepare("SELECT id FROM tech_schedules WHERE tech_id = ? AND shift_date = ?");
    $check->bind_param("is", $tech_id, $shift_date);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // 4. UPDATE existing record
        $stmt = $conn->prepare("UPDATE tech_schedules SET shift_type = ?, time_in = ?, time_out = ? WHERE tech_id = ? AND shift_date = ?");
        $stmt->bind_param("sssis", $shift_type, $time_in, $time_out, $tech_id, $shift_date);
        $action = "updated";
    } else {
        // 5. INSERT new record
        $stmt = $conn->prepare("INSERT INTO tech_schedules (tech_id, shift_date, shift_type, time_in, time_out) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $tech_id, $shift_date, $shift_type, $time_in, $time_out);
        $action = "saved";
    }

    if ($stmt->execute()) {
        // Redirect back to your scheduler page
        header("Location: tech_scheduler.php?msg=success&action=$action");
    } else {
        echo "Error executing query: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

// 6. Handle Deletion (If you click a delete link)
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM tech_schedules WHERE id = $id");
    header("Location: tech_scheduler.php?msg=deleted");
}
?>