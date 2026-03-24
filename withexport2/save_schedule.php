<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'euc_admin') {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tech_id    = (int)$_POST['tech_id'];
    $shift_date = $_POST['shift_date']; // The specific date clicked
    $action     = $_POST['action'] ?? 'save'; 
    
    $curr_m = (int)($_POST['current_month'] ?? date('m'));
    $curr_y = (int)($_POST['current_year'] ?? date('Y'));
    
    // THE MAGIC CHECKBOX: If checked, we propagate the day
    $apply_to_all_days = isset($_POST['apply_to_all_days']);

    $redirect_url = "tech_scheduler.php?month=$curr_m&year=$curr_y";

    try {
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM tech_schedules WHERE tech_id = ? AND shift_date = ?");
            $stmt->bind_param("is", $tech_id, $shift_date);
            $stmt->execute();
            header("Location: $redirect_url&msg=deleted");
            exit();
        }

        $shift_type = trim($_POST['shift_type']);
        $time_in  = ($shift_type === 'Rest Day' || empty($_POST['time_in'])) ? null : $_POST['time_in'];
        $time_out = ($shift_type === 'Rest Day' || empty($_POST['time_out'])) ? null : $_POST['time_out'];
        
        $dates_to_process = [$shift_date]; // Default: specific date only

        if ($apply_to_all_days) {
            $dates_to_process = []; 
            $target_day_of_week = date('N', strtotime($shift_date)); // 1 (Mon) to 7 (Sun)
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $curr_m, $curr_y);

            for ($d = 1; $d <= $days_in_month; $d++) {
                $loop_date = sprintf("%04d-%02d-%02d", $curr_y, $curr_m, $d);
                if (date('N', strtotime($loop_date)) == $target_day_of_week) {
                    $dates_to_process[] = $loop_date;
                }
            }
        }

        // Execute DB updates
        $stmt = $conn->prepare("INSERT INTO tech_schedules (tech_id, shift_date, shift_type, time_in, time_out) 
                               VALUES (?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE 
                               shift_type=VALUES(shift_type), time_in=VALUES(time_in), time_out=VALUES(time_out)");

        foreach ($dates_to_process as $date) {
            $stmt->bind_param("issss", $tech_id, $date, $shift_type, $time_in, $time_out);
            $stmt->execute();
        }

        header("Location: $redirect_url&msg=success");
        exit();

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}