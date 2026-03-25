<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'euc_admin') {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id   = $_SESSION['user_id']; 
    $tech_id    = (int)$_POST['tech_id'];
    $shift_date = $_POST['shift_date']; 
    $curr_m     = (int)$_POST['current_month'];
    $curr_y     = (int)$_POST['current_year'];
    $action_req = $_POST['action'] ?? 'save'; 

    $apply_weekday = isset($_POST['apply_to_all_days']);
    $apply_month   = isset($_POST['apply_to_entire_month']);

    try {
        // 1. Get Technician Name for the logs
        $u_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $u_stmt->bind_param("i", $tech_id);
        $u_stmt->execute();
        $tech_name = $u_stmt->get_result()->fetch_assoc()['username'] ?? "Unknown Tech";

        // 2. Determine dates
        $dates_to_process = [$shift_date];
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $curr_m, $curr_y);

        if ($apply_month) {
            $dates_to_process = [];
            for ($d = 1; $d <= $days_in_month; $d++) {
                $dates_to_process[] = sprintf("%04d-%02d-%02d", $curr_y, $curr_m, $d);
            }
        } elseif ($apply_weekday) {
            $dates_to_process = [];
            $target_day = date('N', strtotime($shift_date));
            for ($d = 1; $d <= $days_in_month; $d++) {
                $loop_date = sprintf("%04d-%02d-%02d", $curr_y, $curr_m, $d);
                if (date('N', strtotime($loop_date)) == $target_day) {
                    $dates_to_process[] = $loop_date;
                }
            }
        }

        // 3. Execute DB Change
        if ($action_req === 'delete') {
            $stmt = $conn->prepare("DELETE FROM tech_schedules WHERE tech_id = ? AND shift_date = ?");
            foreach ($dates_to_process as $date) {
                $stmt->bind_param("is", $tech_id, $date);
                $stmt->execute();
            }
            $log_action = "DELETE";
            $log_details = "Removed schedule for $tech_name on: " . implode(', ', $dates_to_process);
        } else {
            $shift_type = $_POST['shift_type'];
            $is_not_timed = ($shift_type === 'Rest Day' || $shift_type === 'Day Off');
            $time_in  = ($is_not_timed) ? null : $_POST['time_in'];
            $time_out = ($is_not_timed) ? null : $_POST['time_out'];

            $stmt = $conn->prepare("INSERT INTO tech_schedules (tech_id, shift_date, shift_type, time_in, time_out) 
                                   VALUES (?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   shift_type=VALUES(shift_type), time_in=VALUES(time_in), time_out=VALUES(time_out)");

            foreach ($dates_to_process as $date) {
                $stmt->bind_param("issss", $tech_id, $date, $shift_type, $time_in, $time_out);
                $stmt->execute();
            }
            
            $time_info = $is_not_timed ? "" : " ($time_in to $time_out)";
            $log_action = "UPDATE"; 
            $log_details = "Set $tech_name to $shift_type$time_info for dates: " . implode(', ', $dates_to_process);
        }

        // 4. INSERT INTO YOUR EXISTING LOG TABLE
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->bind_param("iss", $admin_id, $log_action, $log_details);
        $log_stmt->execute();

        header("Location: tech_scheduler.php?month=$curr_m&year=$curr_y&msg=success");
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}