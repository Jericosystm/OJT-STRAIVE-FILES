<?php
session_start();
require_once 'db.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'euc_user'; 
$is_admin = ($user_role === 'euc_admin');

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$users_res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
$all_users = [];
while($u = $users_res->fetch_assoc()) { $all_users[] = $u; }

$schedules = [];
$sched_res = $conn->query("SELECT * FROM tech_schedules WHERE MONTH(shift_date) = $month AND YEAR(shift_date) = $year");
while($row = $sched_res->fetch_assoc()) {
    $schedules[$row['tech_id']][$row['shift_date']] = $row;
}

$start_date = new DateTime("$year-$month-01");
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$end_date = new DateTime("$year-$month-$days_in_month");
$weeks = [];
$current = clone $start_date;
while ($current <= $end_date) {
    $week_group = [];
    for ($i = 0; $i < 7; $i++) {
        if ($current <= $end_date) { $week_group[] = clone $current; }
        $current->modify('+1 day');
    }
    $weeks[] = $week_group;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | Tech Schedule</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff6600;
            --bg: #030303;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #FFFFFF;
            --text-muted: rgba(255, 255, 255, 0.5);
            --modal-bg: #080808;
            --rest-day-color: #92d050;
            --danger: #ff4444;
        }

        body { 
            background-color: var(--bg); color: var(--text-main); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; 
            background-image: radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.05), transparent 40%);
        }

        .inventory-container { padding: 40px; max-width: 1600px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .header-flex h2 { font-size: 2.8rem; font-weight: 800; margin: 0; letter-spacing: -2px; }

        .select-group { display: flex; gap: 10px; background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border); padding: 5px 15px; }
        .select-input { background: transparent; border: none; color: var(--text-main); padding: 10px 5px; outline: none; font-weight: 700; cursor: pointer; }

        .week-title { color: var(--primary); font-weight: 800; margin: 40px 0 15px 0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .data-table th { text-align: center; color: var(--text-muted); padding: 15px; font-size: 0.7rem; text-transform: uppercase; }
        .data-table tr { background: var(--card-bg); transition: 0.3s; }
        .data-table td { padding: 18px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); text-align: center; }
        .data-table td:first-child { border-left: 1px solid var(--border); border-radius: 20px 0 0 20px; text-align: left; padding-left: 25px; }
        .data-table td:last-child { border-right: 1px solid var(--border); border-radius: 0 20px 20px 0; }

        .time-box { background: rgba(255, 102, 0, 0.05); border: 1px solid var(--border); border-radius: 12px; padding: 10px; min-width: 100px; display: inline-block; }
        .time-text { font-family: 'JetBrains Mono'; font-size: 0.85rem; font-weight: 700; }
        .rest-day-badge { color: var(--rest-day-color); font-size: 0.7rem; font-weight: 800; border: 1px solid var(--rest-day-color); padding: 5px 10px; border-radius: 8px; }

        .clickable-cell { cursor: pointer; }
        .clickable-cell:hover { background: var(--card-hover) !important; }

        .modal { display: none; position: fixed; z-index: 9999; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); align-items: center; justify-content: center; }
        .modal-content { background: var(--modal-bg); padding: 40px; border-radius: 30px; width: 380px; border: 1px solid var(--border); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 8px; font-weight: 800; }
        .form-group input, .form-group select { width: 100%; padding: 12px; background: #111; border: 1px solid var(--border); color: #fff; border-radius: 12px; }

        .auto-apply-info { 
            background: rgba(255, 102, 0, 0.1); 
            border-left: 3px solid var(--primary);
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            font-size: 0.75rem;
            color: var(--text-main);
        }

        .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 15px;
        border: 1px solid var(--border);
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        font-size: 0.8rem;
        color: var(--text-muted);
        transition: 0.3s;
    }
    .checkbox-item:hover { color: var(--text-main); }
    .checkbox-item input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
        cursor: pointer;
    }

        .btn-delete { 
            background: rgba(255, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); 
            width: 100%; padding: 15px; border-radius: 12px; font-weight: 800; cursor: pointer; margin-top: 10px; transition: 0.3s;
        }
        .btn-delete:hover { background: var(--danger); color: white; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <div class="header-flex">
            <div>
                <p style="color: var(--primary); font-weight: 800; font-size: 0.75rem; letter-spacing: 5px; text-transform: uppercase; margin-bottom: 10px;">Operations Rota</p>
                <h2><span style="color:var(--primary)">Weekly</span> Scheduler</h2>
            </div>
            <form action="" method="GET" class="select-group">
                <select name="month" class="select-input" onchange="this.form.submit()">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($m == $month) ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="select-input" onchange="this.form.submit()">
                    <?php for($y=date('Y')-1; $y<=date('Y')+2; $y++): ?>
                        <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <?php foreach ($weeks as $index => $week_days): ?>
            <div class="week-title">Week <?= ($index + 1) ?> — <?= $week_days[0]->format('M d') ?> to <?= end($week_days)->format('M d') ?></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        <?php foreach ($week_days as $day): ?>
                            <th><?= $day->format('D') ?><br><small style="color: var(--primary)"><?= $day->format('j M') ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <?php foreach ($all_users as $user): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                    <?php foreach ($week_days as $day): 
                        $date_str = $day->format('Y-m-d');
                        $data = $schedules[$user['id']][$date_str] ?? null;
                        $stype = $data['shift_type'] ?? '';
                        $tin = $data['time_in'] ?? '';
                        $tout = $data['time_out'] ?? '';
                        $hasRecord = ($data !== null) ? 'true' : 'false'; 
                        $isRestDay = (strcasecmp($stype, 'Rest Day') == 0);
                    ?>
                        <td class="<?= $is_admin ? 'clickable-cell' : '' ?>" 
                            <?php if($is_admin): ?> 
                                onclick="openModal('<?= $user['id'] ?>', '<?= $date_str ?>', '<?= htmlspecialchars($stype) ?>', '<?= $tin ?>', '<?= $tout ?>', <?= $hasRecord ?>)" 
                            <?php endif; ?>>
                            
                            <?php if($isRestDay): ?>
                                <span class="rest-day-badge">Rest Day</span>
                            <?php elseif(!empty($tin) && $tin != '00:00:00'): ?>
                                <div class="time-box">
                                    <span class="time-text"><?= date('g:i A', strtotime($tin)) ?></span>
                                    <div style="font-size:0.5rem; color:var(--primary)">TO</div>
                                    <span class="time-text"><?= date('g:i A', strtotime($tout)) ?></span>
                                </div>
                            <?php else: ?>
                                <span style="opacity:0.2">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    </main>

    <div id="assignModal" class="modal">
    <div class="modal-content">
        <h2 style="color:var(--primary); margin-top:0;">Manage Schedule</h2>
        
        <form id="schedForm" action="save_schedule.php" method="POST">
            <input type="hidden" name="tech_id" id="modal_tech_id">
            <input type="hidden" name="shift_date" id="modal_date">
            <input type="hidden" name="action" id="modal_action" value="save">
            <input type="hidden" name="current_month" value="<?= $month ?>">
            <input type="hidden" name="current_year" value="<?= $year ?>">

            <div class="form-group">
                <label>Schedule Type</label>
                <select name="shift_type" id="shift_type_select" onchange="toggleTimeFields()">
                    <option value="Scheduled">Timed Shift</option>
                    <option value="Rest Day">Rest Day</option>
                </select>
            </div>

            <div id="time_fields">
                <div class="form-group">
                    <label>Time In</label>
                    <input type="time" name="time_in" id="time_in_input">
                </div>
                <div class="form-group">
                    <label>Time Out</label>
                    <input type="time" name="time_out" id="time_out_input">
                </div>
            </div>

           <div class="checkbox-container" style="margin-top: 20px; padding: 12px; background: rgba(255,102,0,0.05); border-radius: 12px; border: 1px solid var(--border);">
    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.85rem;">
        <input type="checkbox" name="apply_to_all_days" id="apply_to_all_days" style="width: 18px; height: 18px; accent-color: var(--primary);">
        <span>Apply this to all <strong id="dynamic_day_name" style="color: var(--primary);">Mondays</strong> this month</span>
    </label>
</div>
            
            <button type="submit" style="width:100%; background:var(--primary); color:white; border:none; padding:15px; border-radius:12px; font-weight:800; cursor:pointer; margin-top:20px;">SAVE CHANGES</button>
            <button type="button" onclick="deleteSchedule()" id="deleteBtn" class="btn-delete">DELETE SCHEDULE</button>
            <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:var(--text-muted); margin-top:15px; cursor:pointer;">Cancel</button>
        </form>
    </div>
</div>
    <script>
function openModal(techId, date, type, tin, tout, hasRecord) {
    document.getElementById('modal_tech_id').value = techId;
    document.getElementById('modal_date').value = date;
    
    // Reset the checkbox when opening the modal
    document.getElementById('apply_to_all_days').checked = false;

    // Set the Day Name (Mondays, Tuesdays, etc.)
    const dateObj = new Date(date);
    const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
    document.getElementById('dynamic_day_name').innerText = dayName + "s";

    // ... (rest of your existing openModal code) ...
    document.getElementById('shift_type_select').value = type ? type : "Scheduled";
    document.getElementById('time_in_input').value = (tin && tin !== '00:00:00') ? tin.substring(0,5) : "";
    document.getElementById('time_out_input').value = (tout && tout !== '00:00:00') ? tout.substring(0,5) : "";
    document.getElementById('deleteBtn').style.display = hasRecord ? "block" : "none";
    document.getElementById('assignModal').style.display = 'flex';
    toggleTimeFields();
}
    function deleteSchedule() {
        if(confirm("Permanently delete this specific date record?")) {
            document.getElementById('modal_action').value = "delete";
            document.getElementById('schedForm').submit();
        }
    }

    function toggleTimeFields() {
        const isRest = document.getElementById('shift_type_select').value === 'Rest Day';
        document.getElementById('time_fields').style.display = isRest ? 'none' : 'block';
    }

    function closeModal() { document.getElementById('assignModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('assignModal')) { closeModal(); } }
    </script>
</body>
</html>