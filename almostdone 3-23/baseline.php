<?php
session_start();

// 1. Get current filename automatically to avoid "Not Found" errors
$current_page = basename($_SERVER['PHP_SELF']);

// 2. Security & Role Check
$user_role = $_SESSION['role'] ?? 'euc_user'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "ojt project";

$conn = new mysqli($servername, $username_db, $password_db);
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($dbname);

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
    recordActivity($conn, $_SESSION['user_id'] ?? 0, "BASELINE_COL_ADD", "Added requirement: $colName");
    $conn->query("ALTER TABLE win_baseline ADD `$dbColName` VARCHAR(255) DEFAULT ''");
    header("Location: $current_page");
    exit;
}

if (isset($_POST['add_row'])) {
    $hn = $conn->real_escape_string($_POST['new_hostname']);
    recordActivity($conn, $_SESSION['user_id'] ?? 0, "BASELINE_HOST_ADD", "Deployed new host: $hn");
    $res = $conn->query("SELECT COUNT(*) as total FROM win_baseline");
    $row = $res->fetch_assoc();
    $nextBox = intval($row['total']) + 1; 
    $conn->query("INSERT INTO win_baseline (box_no, hostname) VALUES ($nextBox, '$hn')");
    header("Location: $current_page");
    exit;
}

// Fetch Columns
$columns_res = $conn->query("SHOW COLUMNS FROM win_baseline");
$cols = [];
while($row = $columns_res->fetch_assoc()) { 
    if($row['Field'] != 'id') $cols[] = $row['Field'];
}

// --- ACTIVITY LOGGING FUNCTION ---
function recordActivity($conn, $user_id, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
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

        body { 
            background-color: var(--page-bg); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            overflow: hidden; 
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sticky-header-container { flex-shrink: 0; z-index: 1050; }
        .sub-header { 
            background-color: var(--card-bg); 
            border-bottom: 1px solid var(--border-color); 
            transition: background 0.3s ease, border 0.3s ease;
        }

        .filter-group {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 2px 10px;
            transition: all 0.3s ease;
        }
        .filter-group:focus-within {
            border-color: #ff6600;
            box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.25);
        }
        .filter-group i { color: #ff6600; margin-right: 8px; font-size: 0.9rem; }
        .filter-select {
            background: transparent;
            border: none;
            color: var(--text-main);
            padding: 8px 5px;
            font-size: 0.85rem;
            font-weight: 500;
            outline: none;
            cursor: pointer;
        }
        .filter-select option { background: var(--card-bg); color: var(--text-main); }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-wrapper i {
            position: absolute;
            left: 12px;
            color: var(--text-sub);
        }
        .search-input-modern {
            padding-left: 35px !important;
            border-radius: 10px !important;
            background: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            height: 40px;
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
            border-spacing: 0; 
        }

        /* --- Updated Column Header Styling --- */
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

        /* --- STICKY SIDE COLUMNS --- */
        .sticky-col-1 {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: var(--card-bg) !important;
            min-width: 80px !important;
        }

        .sticky-col-2 {
            position: sticky !important;
            left: 80px; /* Offset by width of Box No column */
            z-index: 10;
            background-color: var(--card-bg) !important;
            min-width: 180px !important;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); /* Optional shadow for depth */
        }

        /* Ensure sticky headers are above sticky bodies */
        #baselineTable thead th.sticky-col-1, 
        #baselineTable thead th.sticky-col-2 {
            z-index: 1030 !important;
            background-color: var(--table-head) !important;
        }

        .table td, .table th {
            border-bottom: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        .table-hover tbody tr:hover td { 
            background-color: var(--row-hover) !important; 
            color: var(--text-main) !important;
        }

        .del-col-btn { 
            position: absolute;
            top: 4px;
            right: 4px;
            opacity: 0; 
            transition: opacity 0.2s, color 0.2s; 
            cursor: pointer; 
            color: #ff4d4d; 
            font-size: 12px; 
        }
        th:hover .del-col-btn { opacity: 1; }
        .del-col-btn:hover { color: #ff0000; scale: 1.1; }

        .del-row-btn { opacity: 0; transition: opacity 0.2s; margin-right: 8px; cursor: pointer; color: #dc3545; }
        .readonly-cell:hover .del-row-btn { opacity: 1; }

        .editable-cell { 
            padding: 12px !important; 
            border: 1px solid var(--border-color) !important; 
            font-size: 13px; 
            text-align: center; 
            position: relative; 
            min-width: 120px;
            background-color: var(--card-bg) !important; 
            color: var(--text-main) !important;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .toggle-cell { cursor: pointer; }
        .status-done { 
            background-color: var(--done-bg) !important; 
            color: var(--done-text) !important; 
            font-weight: bold; 
        }
        .status-done::after { content: 'DONE'; }
        
        .btn-modern { border-radius: 8px; padding: 8px 16px; font-weight: 600; }
        .btn-host { background: #ff6600; color: white; border: none; }
        .btn-host:hover { background: #e65c00; color: white; }

        .form-control, .form-select { 
            background-color: var(--input-bg); 
            border: 1px solid var(--border-color); 
            color: var(--text-main); 
        }
        .form-control:focus, .form-select:focus { 
            border-color: #ff6600;
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.25);
        }

        .modal-content { 
            background-color: var(--card-bg); 
            color: var(--text-main); 
            border: 1px solid var(--border-color); 
        }
        .modal-header { border-bottom: 1px solid var(--border-color); }
        .btn-close { filter: invert(var(--theme-invert)); }

        /* Custom SweetAlert Styling to match theme */
        .swal2-popup {
            background-color: var(--card-bg) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
        }
        .swal2-title { color: var(--text-main) !important; }
        .swal2-confirm { background-color: #ff6600 !important; }
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
                            if(!in_array($c, ['box_no', 'hostname', 'asset_inventory', 'poc', 'remarks', 'additional_installs'])): ?>
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
                                    <?php if(!in_array($c, ['box_no', 'hostname', 'asset_inventory'])): ?>
                                        <i class="fas fa-times-circle del-col-btn" title="Delete Column" onclick="deleteCol('<?= $c ?>')"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM win_baseline ORDER BY box_no ASC");
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr id="row-<?= $row['id'] ?>">
                            <?php foreach($cols as $c): 
                                $isBoxNo = ($c == 'box_no');
                                $isHostname = ($c == 'hostname');
                                $isTextCol = in_array($c, ['hostname', 'asset_inventory', 'poc', 'remarks', 'additional_installs']);
                                $isDone = (strtolower($row[$c]) == 'done');
                                
                                $class = $isBoxNo ? 'readonly-cell' : ($isTextCol ? 'text-input-cell' : 'toggle-cell');
                                if($isDone) $class .= ' status-done';
                                
                                // Apply Sticky Classes
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
                        <?php endwhile; ?>
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