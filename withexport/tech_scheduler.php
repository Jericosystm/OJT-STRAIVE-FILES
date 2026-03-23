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

// --- 1. SETTINGS & FILTERS ---
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// --- 2. DATA FETCHING ---
$users_res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
$all_users = [];
while($u = $users_res->fetch_assoc()) { $all_users[] = $u; }

$schedules = [];
$sched_res = $conn->query("SELECT * FROM tech_schedules WHERE MONTH(shift_date) = $month AND YEAR(shift_date) = $year");
while($row = $sched_res->fetch_assoc()) {
    $schedules[$row['tech_id']][$row['shift_date']] = $row;
}

// --- 3. CALENDAR GENERATION ---
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --text-main: #ffffff;
            --text-gray: #a0a0a0;
            --border-color: #222222;
            --input-bg: #151515;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --rest-day-color: #92d050;
        }

        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; }
        .inventory-container { padding: 40px; max-width: 1600px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-flex h2 { font-size: 2.5rem; font-weight: 800; margin: 0; letter-spacing: -1px; }

        .select-group { display: flex; gap: 10px; background: var(--input-bg); border-radius: 10px; border: 1px solid var(--border-color); padding: 5px 15px; }
        .select-input { background: transparent; border: none; color: var(--text-main); padding: 10px 5px; outline: none; font-weight: 600; cursor: pointer; }

        .week-title { color: var(--primary-orange); font-weight: bold; margin: 20px 0 10px 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; }
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-bottom: 40px; }
        .data-table th { text-align: center; color: var(--text-gray); padding: 15px; font-size: 0.75rem; text-transform: uppercase; background: var(--input-bg); font-weight: 700; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .data-table th:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; text-align: left; width: 200px; }
        .data-table th:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }

        .data-table tr { background: var(--card-bg); transition: var(--transition); }
        .data-table td { padding: 12px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); text-align: center; }
        .data-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; text-align: left; font-weight: 600; }
        .data-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }

        .time-box { background: rgba(255, 102, 0, 0.05); border: 1px solid var(--border-color); border-radius: 6px; padding: 8px; display: inline-block; min-width: 90px; }
        .time-text { font-size: 0.8rem; font-weight: 700; color: var(--text-main); display: block; white-space: nowrap; }
        
        .rest-day-badge { color: var(--rest-day-color); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border: 1px solid var(--rest-day-color); padding: 4px 8px; border-radius: 4px; background: rgba(146, 208, 80, 0.1); display: inline-block; }
        .cell-rest-day { background: rgba(146, 208, 80, 0.03) !important; }

        <?php if($is_admin): ?>
        .clickable-cell { cursor: pointer; }
        .clickable-cell:hover { background: rgba(255,102,0,0.1) !important; }
        <?php endif; ?>

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 15px; width: 350px; border: 1px solid var(--border-color); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--text-gray); margin-bottom: 8px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: white; border-radius: 8px; outline: none; }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <div class="header-flex">
            <h2><span style="color:var(--primary-orange)">Weekly</span> Scheduler</h2>
            <div class="filter-row">
                <form action="" method="GET" class="select-group">
                    <select name="month" class="select-input" onchange="this.form.submit()">
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($m == $month) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0,0,0,$m,1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="select-input" onchange="this.form.submit()">
                        <?php for($y=date('Y')-1; $y<=date('Y')+2; $y++): ?>
                            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php foreach ($weeks as $index => $week_days): ?>
            <div class="week-title">Week <?= ($index + 1) ?> — <?= $week_days[0]->format('M d') ?> to <?= end($week_days)->format('M d') ?></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        <?php foreach ($week_days as $day): ?>
                            <th><?= $day->format('D') ?><br><small><?= $day->format('j M') ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><div style="font-size: 0.9rem;"><?= htmlspecialchars($user['username']) ?></div></td>
                        <?php foreach ($week_days as $day): 
                            $date_str = $day->format('Y-m-d');
                            $data = $schedules[$user['id']][$date_str] ?? null;
                            
                            // TRIPLE CHECK LOGIC
                            $shift_type = trim($data['shift_type'] ?? '');
                            $isRestDay = (strcasecmp($shift_type, 'Rest Day') == 0);
                            $hasTime = (!empty($data['time_in']) && $data['time_in'] !== '00:00:00' && $data['time_in'] !== '00:00');
                        ?>
                            <td class="clickable-cell <?= $isRestDay ? 'cell-rest-day' : '' ?>" 
                                <?php if($is_admin): ?> onclick="openModal('<?= $user['id'] ?>', '<?= $date_str ?>')" <?php endif; ?>>
                                
                                <?php if($isRestDay): ?>
                                    <span class="rest-day-badge">Rest Day</span>
                                <?php elseif($hasTime): ?>
                                    <div class="time-box">
                                        <span class="time-text"><?= date('g:i A', strtotime($data['time_in'])) ?></span>
                                        <span style="font-size: 0.5rem; color: var(--primary-orange); text-transform: uppercase;">to</span>
                                        <span class="time-text"><?= date('g:i A', strtotime($data['time_out'])) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--border-color);">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </main>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-top:0; font-size: 1.2rem;"><span style="color:var(--primary-orange)">Set</span> Schedule</h2>
            <form action="save_schedule.php" method="POST">
                <input type="hidden" name="tech_id" id="modal_tech_id">
                <input type="hidden" name="shift_date" id="modal_date">
                
                <div class="form-group">
                    <label>SCHEDULE TYPE</label>
                    <select name="shift_type" id="shift_type_select" onchange="toggleTimeFields()" required>
                        <option value="Scheduled">Timed Shift</option>
                        <option value="Rest Day">Rest Day</option>
                    </select>
                </div>

                <div id="time_fields">
                    <div class="form-group">
                        <label>TIME IN</label>
                        <input type="time" name="time_in" id="time_in_input" required>
                    </div>
                    <div class="form-group">
                        <label>TIME OUT</label>
                        <input type="time" name="time_out" id="time_out_input" required>
                    </div>
                </div>
                
                <button type="submit" style="width:100%; background:var(--primary-orange); color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">SAVE CHANGES</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:transparent; border:none; color:var(--text-gray); margin-top:10px; cursor:pointer;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function toggleTimeFields() {
            const type = document.getElementById('shift_type_select').value;
            const fields = document.getElementById('time_fields');
            const inInput = document.getElementById('time_in_input');
            const outInput = document.getElementById('time_out_input');

            if (type === 'Rest Day') {
                fields.style.display = 'none';
                inInput.required = false;
                outInput.required = false;
                inInput.value = ""; 
                outInput.value = "";
            } else {
                fields.style.display = 'block';
                inInput.required = true;
                outInput.required = true;
            }
        }

        function openModal(techId, date) {
            document.getElementById('modal_tech_id').value = techId;
            document.getElementById('modal_date').value = date;
            document.getElementById('shift_type_select').value = "Scheduled"; 
            document.getElementById('assignModal').style.display = 'flex';
            toggleTimeFields(); 
        }
        function closeModal() { document.getElementById('assignModal').style.display = 'none'; }
        window.onclick = function(event) { if (event.target == document.getElementById('assignModal')) closeModal(); }
    </script>
</body>
</html>