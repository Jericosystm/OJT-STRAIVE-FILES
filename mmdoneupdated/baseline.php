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
        $conn->query("UPDATE win_baseline SET `$column` = '$value' WHERE id = $id");
        exit; 
    }
    if ($_POST['ajax_action'] == 'delete_column') {
        $column = $conn->real_escape_string($_POST['column']);
        // Use backticks to handle column names with spaces/special characters
        $conn->query("ALTER TABLE win_baseline DROP COLUMN `$column` ");
        exit;
    }
    if ($_POST['ajax_action'] == 'delete_row') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM win_baseline WHERE id = $id");
        exit;
    }
}

// --- FORM HANDLERS ---
if (isset($_POST['add_column'])) {
    $colName = preg_replace('/[^A-Za-z0-9_ ]/', '', $_POST['col_name']); 
    $dbColName = strtolower(str_replace(' ', '_', $colName));
    $conn->query("ALTER TABLE win_baseline ADD `$dbColName` VARCHAR(255) DEFAULT ''");
    header("Location: $current_page");
    exit;
}

if (isset($_POST['add_row'])) {
    $hn = $conn->real_escape_string($_POST['new_hostname']);
    $res = $conn->query("SELECT MAX(CAST(box_no AS UNSIGNED)) as max_box FROM win_baseline");
    $row = $res->fetch_assoc();
    $nextBox = ($row['max_box'] !== null) ? intval($row['max_box']) + 1 : 1;
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
        :root { --primary-gradient: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); }
        body { background-color: #f4f7f9; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .sticky-header-container { flex-shrink: 0; z-index: 1050; }
        .table-outer-wrapper { flex-grow: 1; padding: 20px; display: flex; flex-direction: column; overflow: hidden; }
        .baseline-card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .table-responsive { flex-grow: 1; overflow: auto !important; }
        
        /* Fixed "Slashes" - Prevent compression */
        .table { margin-bottom: 0; table-layout: auto; min-width: 2200px; border-collapse: separate; border-spacing: 0; }
        
        .table thead th { 
        background: #2c3e50 !important; 
        color: white; 
        position: sticky; 
        top: 0; 
        z-index: 20; 
        padding: 18px 10px; /* Increased vertical padding for a more premium look */
        font-size: 11px; 
        text-align: center; 
        white-space: nowrap;
        position: relative; /* CRITICAL: This keeps the button inside the cell */
    }

        .editable-cell { padding: 12px !important; border: 1px solid #f1f1f1; font-size: 13px; text-align: center; position: relative; min-width: 120px; }
        .toggle-cell { cursor: pointer; }
        .status-done { background: #d1fae5 !important; color: #065f46 !important; font-weight: bold; }
        .status-done::after { content: 'DONE'; }
        
      
        .btn-modern { border-radius: 8px; padding: 8px 16px; font-weight: 600; }
    </style>
</head>
<body>

    <div class="sticky-header-container">
        <?php include('header.php'); ?>
        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h3 class="m-0 fw-bold">Windows Baseline</h3>
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search..." onkeyup="triggerSearch()" style="width: 200px;">
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
        <?php foreach($cols as $c): ?>
            <th class="position-relative">
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
                                $isTextCol = in_array($c, ['hostname', 'asset_inventory', 'poc', 'remarks', 'additional_installs']);
                                $isDone = (strtolower($row[$c]) == 'done');
                                $class = $isBoxNo ? 'readonly-cell' : ($isTextCol ? 'text-input-cell' : 'toggle-cell');
                                if($isDone) $class .= ' status-done';
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
                                <?php else: echo htmlspecialchars($row[$c]) ?: '&nbsp;'; endif; ?>
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
                <div class="modal-body"><input type="text" name="col_name" class="form-control" placeholder="e.g. Chrome Install" required></div>
                <div class="modal-footer border-0"><button type="submit" name="add_column" class="btn btn-host w-100">Add Column</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header"><h5 class="fw-bold">Deploy New Host</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><input type="text" name="new_hostname" class="form-control" placeholder="Enter Hostname" required></div>
                <div class="modal-footer border-0"><button type="submit" name="add_row" class="btn btn-host w-100">Create Entry</button></div>
            </form>
        </div>
    </div>

    <script>
    // Automatically uses whatever name your file has (baseline.php)
    const phpTarget = "<?= $current_page ?>";

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
    }

    function deleteRow(id) {
        if(confirm('Delete this host?')) {
            $.post(phpTarget, { ajax_action: 'delete_row', id: id }, () => location.reload());
        }
    }

    function deleteCol(col) {
        if(confirm('Permanently delete column "' + col + '"?')) {
            $.post(phpTarget, { ajax_action: 'delete_column', column: col }, () => location.reload());
        }
    }

    function triggerSearch() {
        let term = $("#searchInput").val().toLowerCase();
        $("#baselineTable tbody tr").each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    }
    </script>
</body>
</html>