<?php
// 1. Force error reporting so we can see what's wrong
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once 'db.php';

// 2. Access Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'euc_admin') {
    die("Access Denied: You are not an admin.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. Clean and Validate Inputs
    $tech_id    = (int)$_POST['tech_id'];
    $shift_date = $_POST['shift_date'];
    $shift_type = trim($_POST['shift_type']); // Trim to remove accidental spaces

    // 4. Handle Rest Day vs Timed Shift
    if ($shift_type === 'Rest Day') {
        $time_in  = null; 
        $time_out = null;
    } else {
        $time_in  = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
        $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
    }

    try {
        // 5. Check if record exists
        $check = $conn->prepare("SELECT id FROM tech_schedules WHERE tech_id = ? AND shift_date = ?");
        $check->bind_param("is", $tech_id, $shift_date);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            // 6. UPDATE (Notice the order of parameters)
            $stmt = $conn->prepare("UPDATE tech_schedules SET shift_type = ?, time_in = ?, time_out = ? WHERE tech_id = ? AND shift_date = ?");
            $stmt->bind_param("sssis", $shift_type, $time_in, $time_out, $tech_id, $shift_date);
        } else {
            // 7. INSERT
            $stmt = $conn->prepare("INSERT INTO tech_schedules (tech_id, shift_date, shift_type, time_in, time_out) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $tech_id, $shift_date, $shift_type, $time_in, $time_out);
        }

        $stmt->execute();
        
        // Success!
        header("Location: tech_scheduler.php?msg=success");
        exit();

    } catch (Exception $e) {
        // This will stop the page and show you the EXACT SQL error
        echo "<h2>Database Error!</h2>";
        echo "Error Message: " . $e->getMessage();
        echo "<br>Check if your 'shift_type' column is long enough (e.g., VARCHAR(50)).";
        exit();
    }
}