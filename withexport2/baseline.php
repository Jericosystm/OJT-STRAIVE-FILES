<?php
session_start();

// 1. Get current filename automatically
$current_page = basename($_SERVER['PHP_SELF']);

// 2. Security & Role Check
$user_role = $_SESSION['role'] ?? 'euc_user'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['user_id']; 
$username = $_SESSION['username'] ?? 'User';

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "ojt project";

$conn = new mysqli($servername, $username_db, $password_db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($dbname);

// --- ACTIVITY LOGGING FUNCTION ---
function recordActivity($conn, $user_id, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// --- AJAX HANDLERS ---
if (isset($_POST['ajax_action'])) {
    if ($_POST['ajax_action'] == 'update') {
        $id = intval($_POST['id']);
        $column = $conn->real_escape_string($_POST['column']);
        $value = $conn->real_escape_string($_POST['value']);
        $logVal = ($value == 'DONE') ? "marked as DONE" : "updated to '$value'";
        recordActivity($conn, $u_id, "BASELINE_UPDATE", "ID $id: Column '$column' $logVal");
        $conn->query("UPDATE win_baseline SET `$column` = '$value' WHERE id = $id");
        exit; 
    }
    if ($_POST['ajax_action'] == 'delete_column') {
        $column = $conn->real_escape_string($_POST['column']);
        recordActivity($conn, $u_id, "BASELINE_COL_DELETE", "Deleted column: $column");
        $conn->query("ALTER TABLE win_baseline DROP COLUMN `$column` ");
        exit;
    }
    if ($_POST['ajax_action'] == 'delete_row') {
        $id = intval($_POST['id']);
        recordActivity($conn, $u_id, "BASELINE_ROW_DELETE", "Deleted Row ID: $id");
        $conn->query("DELETE FROM win_baseline WHERE id = $id");
        $conn->query("SET @count = 0");
        $conn->query("UPDATE win_baseline SET box_no = (@count:= @count + 1) ORDER BY box_no ASC");
        exit;
    }
}

// --- FORM HANDLERS ---
if (isset($_POST['add_column'])) {
    $colName = preg_replace('/[^A-Za-z0-9_ ]/', '', $_POST['col_name']); 
    $dbColName = strtolower(str_replace(' ', '_', $colName));
    recordActivity($conn, $u_id, "BASELINE_COL_ADD", "Added requirement: $colName");
    $conn->query("ALTER TABLE win_baseline ADD `$dbColName` VARCHAR(255) DEFAULT ''");
    header("Location: $current_page");
    exit;
}

if (isset($_POST['add_row'])) {
    $hn = $conn->real_escape_string($_POST['new_hostname']);
    recordActivity($conn, $u_id, "BASELINE_HOST_ADD", "Deployed new host: $hn");
    $res = $conn->query("SELECT COUNT(*) as total FROM win_baseline");
    $row = $res->fetch_assoc();
    $nextBox = intval($row['total'] ?? 0) + 1; 
    $conn->query("INSERT INTO win_baseline (box_no, hostname) VALUES ($nextBox, '$hn')");
    header("Location: $current_page");
    exit;
}

// Fetch Columns - Skipping 'id' and 'asset_inventory'
$columns_res = $conn->query("SHOW COLUMNS FROM win_baseline");
$cols = [];
if ($columns_res) {
    while($row = $columns_res->fetch_assoc()) { 
        if(!in_array($row['Field'], ['id', 'asset_inventory'])) {
            $cols[] = $row['Field'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Win Baseline | OJTBox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { 
            --primary-gradient: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%);
            --primary-orange: #ff6600;
            --neon-blue: #00d4ff;
            --page-bg: #121212;
            --card-bg: #1e1e1e;
            --text-main: #ffffff;
            --text-sub: #bbbbbb;
            --border-color: #333333;
            --table-head: #000000;
            --input-bg: #2d2d2d;
            --row-hover: #252525;
            --done-bg: #064e3b;
            --done-text: #34d399;
            --theme-invert: 1;
        }

        [data-theme="light"] {
            --page-bg: #f4f7f9;
            --card-bg: #ffffff;
            --text-main: #1a1a1b;
            --text-sub: #666666;
            --border-color: #e0e0e0;
            --table-head: #2c3e50;
            --input-bg: #ffffff;
            --row-hover: #f8f9fa;
            --done-bg: #d1fae5;
            --done-text: #065f46;
            --theme-invert: 0;
        }

        @keyframes fadeInPage { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        body { 
            background-color: var(--page-bg); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            overflow: hidden; 
            transition: background 0.3s ease, color 0.3s ease;
            animation: fadeInPage 0.8s ease-out;
        }

        .status-done {
            background-color: var(--done-bg) !important;
            color: var(--done-text) !important;
        }
        .status-done::after {
            content: 'DONE';
            font-weight: bold;
        }

        .sticky-header-container { flex-shrink: 0; z-index: 1050; }
        .sub-header { 
            background-color: var(--card-bg); 
            border-bottom: 1px solid var(--border-color); 
            transition: background 0.3s ease, border 0.3s ease;
        }

        #baselineTable tbody tr {
            transition: transform 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
        }

        #baselineTable tbody tr:hover { 
            transform: translateX(8px); 
            background-color: var(--row-hover) !important; 
            box-shadow: -4px 0 0 var(--neon-blue);
        }

        #baselineTable tbody tr:hover td.sticky-col-1,
        #baselineTable tbody tr:hover td.sticky-col-2 {
            background-color: var(--row-hover) !important;
        }

        .table-outer-wrapper { 
            flex: 1; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            min-height: 0; 
        }

        .baseline-card { 
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .table-responsive { 
            flex: 1;
            overflow: auto !important; 
            position: relative;
            height: 0; 
        }

        #baselineTable { 
            border-collapse: separate !important; 
            border-spacing: 0 4px;
        }

        #baselineTable thead th { 
            position: sticky !important; 
            top: 0 !important; 
            z-index: 1025 !important; 
            background-color: var(--table-head) !important; 
            color: white !important;
            box-shadow: inset 0 -1px 0 var(--border-color);
            border: none;
            padding: 15px 12px;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .sticky-col-1 {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: var(--card-bg) !important;
            min-width: 80px !important;
        }

        .sticky-col-2 {
            position: sticky !important;
            left: 80px; 
            z-index: 10;
            background-color: var(--card-bg) !important;
            min-width: 180px !important;
        }

        #baselineTable thead th.sticky-col-1, 
        #baselineTable thead th.sticky-col-2 {
            z-index: 1030 !important;
            background-color: var(--table-head) !important;
        }

        .editable-cell { 
            padding: 12px !important; 
            border-top: 1px solid var(--border-color) !important; 
            border-bottom: 1px solid var(--border-color) !important; 
            border-right: 1px solid var(--border-color) !important;
            font-size: 13px; 
            text-align: center; 
            position: relative; 
            min-width: 120px;
            background-color: var(--card-bg) !important; 
            color: var(--text-main) !important;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .editable-cell:first-child {
            border-left: 1px solid var(--border-color) !important;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .editable-cell:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Container styling */
.filter-group {
    display: flex;
    align-items: center;
    background: #2d2d2d; /* Hardcoded to match your var(--input-bg) */
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 0 10px;
    height: 32px; /* Slimmer height */
    transition: border 0.2s;
}

.filter-group:focus-within {
    border-color: var(--primary-orange);
}

.filter-group i {
    font-size: 12px;
    color: var(--text-sub);
    margin-right: 8px;
}

/* Select element styling */
.filter-select {
    background-color: #2d2d2d !important; /* Forces dark background */
    color: #ffffff !important;           /* Forces white text */
    border: none;
    font-size: 11px;                     /* Small, professional font */
    font-weight: 600;
    text-transform: uppercase;
    outline: none;
    cursor: pointer;
    padding: 2px 0;
    width: 100%;
}

/* This targets the actual dropdown list (the options) */
.filter-select option {
    background-color: #1e1e1e; /* Match your card-bg */
    color: #ffffff;
    padding: 10px;
}


/* 1. Prevent the container from wrapping items to a new line */
.sub-header .d-flex.gap-3.align-items-center {
    flex-wrap: nowrap !important; /* Forces everything on one line */
    width: auto;
}

/* 2. Slim down the search wrapper and input */
.search-wrapper {
    display: flex;
    align-items: center;
    background: #2d2d2d;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0 10px;
    height: 32px; /* Matches the filter height */
    min-width: 150px; /* Minimum width so it doesn't disappear */
    flex-shrink: 1;   /* Allows it to shrink slightly if space is tight */
}
/* Sync the search input height and font */
.search-input-modern {
    height: 32px !important;
    font-size: 12px !important;
    background-color: #2d2d2d !important;
    border: 1px solid var(--border-color) !important;
    color: white !important;
    border-radius: 6px !important;
}

.search-input-modern {
    background: transparent !important;
    border: none !important;
    font-size: 12px !important;
    color: white !important;
    padding: 0 5px !important;
    height: 100% !important;
    box-shadow: none !important; /* Removes blue outline on click */
}


.btn-modern {
    white-space: nowrap; /* Prevents button text from breaking into 2 lines */
    padding: 5px 12px;
    font-size: 12px;
    height: 32px;
    display: flex;
    align-items: center;
}        .btn-host { background: var(--primary-orange); color: white; border: none; }
        .btn-host:hover { background: #e65c00; box-shadow: 0 0 15px rgba(255,102,0,0.4); }

        .modal-content { 
            background-color: var(--card-bg); 
            color: var(--text-main); 
            border: 1px solid var(--border-color); 
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body data-theme="dark"> 

    <div class="sticky-header-container">
        <?php include('header.php'); ?>
        <div class="p-3 sub-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 fw-bold">Windows Baseline</h3>
            <div class="d-flex gap-3 align-items-center">
                <div class="filter-group">
                    <i class="fas fa-filter"></i>
                    <select id="filterColumn" class="filter-select" style="width: 150px;" onchange="triggerSearch()">
                        <option value="all">FILTER</option>
                        <?php foreach($cols as $c): 
                            if(!in_array($c, ['box_no', 'hostname', 'poc', 'remarks', 'additional_installs'])): ?>
                            <option value="<?= $c ?>"><?= str_replace('_', ' ', strtoupper($c)) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <i class="fas fa-check-circle"></i>
                    <select id="filterStatus" class="filter-select" style="width: 120px;" onchange="triggerSearch()">
                        <option value="any">ANY STATUS</option>
                        <option value="done">DONE</option>
                        <option value="pending">PENDING</option>
                    </select>
                </div>

                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control search-input-modern" placeholder="Search hosts..." onkeyup="triggerSearch()" style="width: 220px;">
                </div>

                <div class="vr mx-2" style="opacity: 0.1;"></div>

                <button class="btn btn-modern btn-host" data-bs-toggle="modal" data-bs-target="#rowModal"><i class="fas fa-plus me-2"></i>New Host</button>
                <button class="btn btn-modern btn-dark" data-bs-toggle="modal" data-bs-target="#colModal"><i class="fas fa-tools me-2"></i>Add Column</button>
            </div>
        </div>
    </div>

    <div class="table-outer-wrapper">
        <div class="baseline-card">
            <div class="table-responsive">
                <table class="table table-hover" id="baselineTable">
                    <thead>
                        <tr>
                            <?php foreach($cols as $c): 
                                $stickyClass = ($c == 'box_no') ? 'sticky-col-1' : (($c == 'hostname') ? 'sticky-col-2' : '');
                            ?>
                                <th class="position-relative <?= $stickyClass ?>">
                                    <?= str_replace('_', ' ', strtoupper($c)) ?>
                                    <?php if(!in_array($c, ['box_no', 'hostname'])): ?>
                                        <i class="fas fa-times-circle del-col-btn" title="Delete Column" onclick="deleteCol('<?= $c ?>')"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM win_baseline ORDER BY box_no ASC");
                        if ($result) {
                            while($row = $result->fetch_assoc()):
                        ?>
                        <tr id="row-<?= $row['id'] ?>">
                            <?php foreach($cols as $c): 
                                $isBoxNo = ($c == 'box_no');
                                $isHostname = ($c == 'hostname');
                                $isTextCol = in_array($c, ['hostname', 'poc', 'remarks', 'additional_installs']);
                                $isDone = (isset($row[$c]) && strtolower($row[$c]) == 'done');
                                
                                $class = $isBoxNo ? 'readonly-cell' : ($isTextCol ? 'text-input-cell' : 'toggle-cell');
                                if($isDone) $class .= ' status-done';
                                
                                if($isBoxNo) $class .= ' sticky-col-1';
                                if($isHostname) $class .= ' sticky-col-2';
                            ?>
                            <td class="editable-cell <?= $class ?>" 
                                contenteditable="<?= $isTextCol ? 'true' : 'false' ?>"
                                data-id="<?= $row['id'] ?>" data-col="<?= $c ?>"
                                onblur="<?= $isTextCol ? 'saveData(this)' : '' ?>"
                                onclick="<?= (!$isTextCol && !$isBoxNo) ? 'toggleDone(this)' : '' ?>">
                                
                                <?php if($isBoxNo): ?>
                                    <i class="fas fa-trash del-row-btn" onclick="deleteRow(<?= $row['id'] ?>)"></i>
                                    <?= sprintf('%02d', $row[$c]) ?>
                                <?php elseif($isDone): echo ''; ?>
                                <?php else: echo htmlspecialchars($row[$c] ?? '') ?: '&nbsp;'; endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="colModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header"><h5 class="fw-bold">Add Requirement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><input type="text" id="col_name_input" name="col_name" class="form-control" placeholder="e.g. Chrome Install" required></div>
                <div class="modal-footer border-0"><button type="submit" name="add_column" class="btn btn-host w-100">Add Column</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header"><h5 class="fw-bold">Deploy New Host</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><input type="text" id="new_hostname_input" name="new_hostname" class="form-control" placeholder="Enter Hostname" required></div>
                <div class="modal-footer border-0"><button type="submit" name="add_row" class="btn btn-host w-100">Create Entry</button></div>
            </form>
        </div>
    </div>

    <script>
    const phpTarget = "<?= $current_page ?>";

    $(document).ready(function() {
        const currentTheme = localStorage.getItem('theme') || 'dark';
        document.body.setAttribute('data-theme', currentTheme);
        $('#colModal').on('shown.bs.modal', function () { $('#col_name_input').focus(); });
        $('#rowModal').on('shown.bs.modal', function () { $('#new_hostname_input').focus(); });
    });

    function saveData(el) {
        let val = $(el).text().trim();
        $.post(phpTarget, { ajax_action: 'update', id: $(el).data('id'), column: $(el).data('col'), value: val });
    }
    
    function toggleDone(el) {
        let isDone = $(el).hasClass('status-done');
        let newVal = isDone ? '' : 'DONE';
        $(el).toggleClass('status-done');
        if(!isDone) $(el).text(''); else $(el).html('&nbsp;');
        $.post(phpTarget, { ajax_action: 'update', id: $(el).data('id'), column: $(el).data('col'), value: newVal });
        triggerSearch();
    }

    function deleteRow(id) {
        Swal.fire({
            title: 'Delete this host?',
            text: "Remaining boxes will be automatically re-numbered.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff6600',
            cancelButtonColor: '#333',
            confirmButtonText: 'Yes, delete it!',
            background: 'var(--card-bg)',
            color: 'var(--text-main)'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(phpTarget, { ajax_action: 'delete_row', id: id }, () => location.reload());
            }
        });
    }

    function deleteCol(col) {
        let displayName = col.replace(/_/g, ' ').toUpperCase();
        Swal.fire({
            title: 'Remove Column?',
            html: `Are you sure you want to permanently delete <b>"${displayName}"</b>?<br><small class="text-danger">This action cannot be undone.</small>`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#333',
            confirmButtonText: 'Permanently Delete',
            background: 'var(--card-bg)',
            color: 'var(--text-main)'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(phpTarget, { ajax_action: 'delete_column', column: col }, () => location.reload());
            }
        });
    }

    function triggerSearch() {
        let term = $("#searchInput").val().toLowerCase();
        let targetCol = $("#filterColumn").val();
        let targetStatus = $("#filterStatus").val();

        $("#baselineTable tbody tr").each(function() {
            let rowText = $(this).text().toLowerCase();
            let matchesSearch = rowText.includes(term);
            let matchesFilter = true;

            if (targetStatus !== 'any') {
                if (targetCol === 'all') {
                    let hasMatch = false;
                    $(this).find('td.toggle-cell').each(function() {
                        let isDone = $(this).hasClass('status-done');
                        if (targetStatus === 'done' && isDone) hasMatch = true;
                        if (targetStatus === 'pending' && !isDone) hasMatch = true;
                    });
                    matchesFilter = hasMatch;
                } else {
                    let cell = $(this).find('td[data-col="' + targetCol + '"]');
                    let isDone = cell.hasClass('status-done');
                    if (targetStatus === 'done') matchesFilter = isDone;
                    if (targetStatus === 'pending') matchesFilter = !isDone;
                }
            }
            $(this).toggle(matchesSearch && matchesFilter);
        });
    }
    </script>
</body>
</html>