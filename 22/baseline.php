<?php
session_start();

// 1. Security Check
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

// Ensure Table Structure
$tableSchema = "CREATE TABLE IF NOT EXISTS win_baseline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    box_no INT DEFAULT 1,
    hostname VARCHAR(100) DEFAULT '',
    asset_inventory VARCHAR(100) DEFAULT '',
    wds_formatting VARCHAR(50) DEFAULT '',
    set_password VARCHAR(50) DEFAULT '',
    enable_usb VARCHAR(50) DEFAULT '',
    enable_audio VARCHAR(50) DEFAULT '',
    removed_accounts VARCHAR(50) DEFAULT '',
    rename_admin VARCHAR(50) DEFAULT '',
    disable_usb_os VARCHAR(50) DEFAULT '',
    install_sentinel VARCHAR(50) DEFAULT '',
    verify_netskope VARCHAR(50) DEFAULT '',
    install_pc_visor VARCHAR(50) DEFAULT '',
    domain_join VARCHAR(50) DEFAULT '',
    windows_update VARCHAR(50) DEFAULT '',
    installed_softwares VARCHAR(50) DEFAULT '',
    bitlocker_verify VARCHAR(50) DEFAULT '',
    poc VARCHAR(100) DEFAULT '',
    additional_installs TEXT,
    remarks TEXT
)";
$conn->query($tableSchema);

// --- AJAX HANDLER: Update Cells ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'update') {
    $id = intval($_POST['id']);
    $column = $conn->real_escape_string($_POST['column']);
    $value = $conn->real_escape_string($_POST['value']);
    $conn->query("UPDATE win_baseline SET `$column` = '$value' WHERE id = $id");
    exit; 
}

// --- AJAX HANDLER: Delete Column ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_column') {
    $column = $conn->real_escape_string($_POST['column']);
    $conn->query("ALTER TABLE win_baseline DROP COLUMN `$column` ");
    exit;
}

// --- AJAX HANDLER: Delete Row and Re-index Box Numbers ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_row') {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM win_baseline WHERE id = $id");
    
    $result = $conn->query("SELECT id FROM win_baseline ORDER BY box_no ASC");
    $count = 1;
    while($row = $result->fetch_assoc()) {
        $rid = $row['id'];
        $conn->query("UPDATE win_baseline SET box_no = $count WHERE id = $rid");
        $count++;
    }
    exit;
}

// --- ADD COLUMN HANDLER ---
if (isset($_POST['add_column'])) {
    $colName = preg_replace('/[^A-Za-z0-9_ ]/', '', $_POST['col_name']); 
    $dbColName = strtolower(str_replace(' ', '_', $colName));
    $conn->query("ALTER TABLE win_baseline ADD `$dbColName` VARCHAR(255) DEFAULT ''");
    header("Location: win_baseline.php");
    exit;
}

// --- ADD ROW HANDLER ---
if (isset($_POST['add_row'])) {
    $hn = $conn->real_escape_string($_POST['new_hostname']);
    $res = $conn->query("SELECT MAX(CAST(box_no AS UNSIGNED)) as max_box FROM win_baseline");
    $row = $res->fetch_assoc();
    $nextBox = ($row['max_box'] !== null) ? intval($row['max_box']) + 1 : 1;
    $conn->query("INSERT INTO win_baseline (box_no, hostname) VALUES ($nextBox, '$hn')");
    header("Location: win_baseline.php");
    exit;
}

// Fetch Columns
$columns_res = $conn->query("SHOW COLUMNS FROM win_baseline");
$cols = [];
$requirementCols = [];
while($row = $columns_res->fetch_assoc()) { 
    if($row['Field'] != 'id') {
        $cols[] = $row['Field'];
        if(!in_array($row['Field'], ['box_no', 'hostname', 'asset_inventory', 'poc', 'remarks', 'additional_installs'])) {
            $requirementCols[] = $row['Field'];
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }

        body { 
            background-color: #f4f7f9; 
            font-family: 'Inter', sans-serif;
            overflow: hidden; 
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sticky-header-container { flex-shrink: 0; z-index: 1000; background-color: #f4f7f9; }
        .header-tools { padding: 20px 25px; border-bottom: 1px solid #e0e6ed; }

        .table-outer-wrapper { flex-grow: 1; padding: 0 25px 25px 25px; display: flex; flex-direction: column; overflow: hidden; }
        .baseline-card { border-radius: 16px; box-shadow: var(--shadow-lg); background: white; flex-grow: 1; overflow: hidden; display: flex; flex-direction: column; }
        
        .table-responsive { flex-grow: 1; overflow: auto; }
        .table { border-collapse: separate; border-spacing: 0; width: 100%; }

        .table thead th { 
            background: #2c3e50 !important; color: #fff; font-size: 11px; text-transform: uppercase; 
            padding: 20px 10px; position: sticky; top: 0; z-index: 10; text-align: center;
        }

        /* Hover Trash for Columns */
        .del-col-btn { position: absolute; top: 5px; right: 5px; color: #ff4d4d; cursor: pointer; opacity: 0; transition: 0.2s; }
        .table thead th:hover .del-col-btn { opacity: 1; }

        /* Hover Trash for Rows */
        .del-row-btn { 
            position: absolute; top: 5px; left: 5px; color: #ff4d4d; 
            cursor: pointer; opacity: 0; transition: 0.2s; font-size: 14px; z-index: 5;
        }
        .editable-cell:hover .del-row-btn { opacity: 1; }

        .editable-cell { padding: 15px !important; outline: none; border: 1px solid #f8f9fa; min-width: 140px; text-align: center; font-size: 13px; position: relative; }
        .text-input-cell { cursor: text; text-align: left; background: #fff !important; font-weight: 500; }
        .toggle-cell { cursor: pointer; user-select: none; font-weight: 700; color: transparent; }
        .status-done { background: #d1fae5 !important; color: #065f46 !important; }
        .status-done::after { content: 'DONE'; color: #065f46; }

        .readonly-cell { background: #fdfdfd !important; color: #2c3e50 !important; font-weight: bold; }
        
        .btn-modern { border-radius: 10px; padding: 10px 20px; font-weight: 600; border: none; }
        .btn-host { background: var(--primary-gradient); color: white; }
        .search-input { padding-left: 40px !important; width: 250px; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }
    </style>
</head>
<body>

    <div class="sticky-header-container">
        <?php include('header.php'); ?>
        <div class="header-tools d-flex justify-content-between align-items-center">
            <h2 class="m-0" style="font-weight: 800;">Windows Baseline</h2>
            <div class="d-flex gap-2">
                <div class="search-container position-relative">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Search..." onkeyup="triggerSearch()">
                </div>
                <button class="btn btn-modern btn-host" data-bs-toggle="modal" data-bs-target="#rowModal"><i class="fas fa-plus me-2"></i>New Host</button>
                <button class="btn btn-modern btn-dark" data-bs-toggle="modal" data-bs-target="#colModal"><i class="fas fa-tools me-2"></i>Add Column</button>
            </div>
        </div>
    </div>

    <div class="table-outer-wrapper">
        <div class="baseline-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="baselineTable">
                    <thead>
                        <tr>
                            <?php foreach($cols as $c): ?>
                                <th class="position-relative">
                                    <?= str_replace('_', ' ', strtoupper($c)) ?>
                                    <?php if(!in_array($c, ['box_no', 'hostname', 'asset_inventory', 'poc', 'remarks'])): ?>
                                        <i class="fa-solid fa-circle-xmark del-col-btn" onclick="deleteCol('<?= $c ?>')"></i>
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
                                $isTextCol = in_array($c, ['hostname', 'asset_inventory', 'poc', 'remarks', 'additional_installs', 'box_no']);
                                $isDone = (strtolower($row[$c]) == 'done');
                                
                                if ($isBoxNo) { 
                                    $cellClass = 'readonly-cell'; 
                                } elseif ($isTextCol) { 
                                    $cellClass = 'text-input-cell'; 
                                } else { 
                                    $cellClass = 'toggle-cell' . ($isDone ? ' status-done' : ''); 
                                }
                            ?>
                            <td class="editable-cell <?= $cellClass ?>" 
                                contenteditable="<?= ($isTextCol) ? 'true' : 'false' ?>" 
                                data-id="<?= $row['id'] ?>" 
                                data-col="<?= $c ?>"
                                onBlur="<?= ($isTextCol) ? 'saveData(this)' : '' ?>"
                                onclick="<?= (!$isTextCol) ? 'toggleDone(this)' : '' ?>">
                                
                                <?php if($isBoxNo): ?>
                                    <i class="fa-solid fa-trash del-row-btn" 
                                       onclick="event.stopPropagation(); deleteRow(<?= $row['id'] ?>, '<?= $row['hostname'] ?>')"></i>
                                    <?= sprintf('%02d', $row[$c]) ?>
                                <?php else: ?>
                                    <?= ($isDone ? '' : htmlspecialchars($row[$c])) ?>
                                <?php endif; ?>
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
            <form method="POST" class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Add Requirement Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="col_name" class="form-control" placeholder="Requirement Name" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="add_column" class="btn btn-modern btn-host w-100">Add Column</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Deploy New Host</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="new_hostname" class="form-control" placeholder="Enter Hostname" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="add_row" class="btn btn-modern btn-host w-100">Create Entry</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function saveData(el) {
        let val = $(el).text().trim();
        $.post('win_baseline.php', { ajax_action: 'update', id: $(el).data('id'), column: $(el).data('col'), value: val });
    }
    
    function toggleDone(el) {
        let isDone = $(el).hasClass('status-done');
        let newVal = isDone ? '' : 'DONE';
        $(el).toggleClass('status-done').text(isDone ? '' : '');
        $.post('win_baseline.php', { ajax_action: 'update', id: $(el).data('id'), column: $(el).data('col'), value: newVal });
    }

    function deleteCol(colName) {
        Swal.fire({
            title: 'Delete Column?',
            text: "Data for " + colName + " will be lost!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('win_baseline.php', { ajax_action: 'delete_column', column: colName }, () => location.reload());
            }
        });
    }

    function deleteRow(rowId, hostname) {
        Swal.fire({
            title: 'Delete Row?',
            text: "Remove host: " + hostname + "?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('win_baseline.php', { ajax_action: 'delete_row', id: rowId }, () => location.reload());
            }
        });
    }

    function triggerSearch() {
        let term = $("#searchInput").val().toLowerCase();
        $("#baselineTable tbody tr").each(function() {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(term));
        });
    }
    </script>
</body>
</html>
<?php include 'footer.php'; ?>