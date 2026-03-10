<?php
session_start();

// Security Check: If 'user_id' isn't set, they aren't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now we can safely get the username (make sure you set this in auth.php!)
$username = $_SESSION['username'] ?? 'User'; 
?>
<?php
require_once 'db.php';

// --- 1. SETTINGS & FILTERS ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$sort_col = isset($_GET['sort']) ? $_GET['sort'] : 'uploaded_at';
$sort_ord = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$next_order = ($sort_ord == 'ASC') ? 'DESC' : 'ASC';

function formatSize($bytes) {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT file_path FROM hdn_files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($file = $res->fetch_assoc()) {
        if (file_exists($file['file_path'])) unlink($file['file_path']);
        $del_stmt = $conn->prepare("DELETE FROM hdn_files WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
    }
    header("Location: hdn.php?msg=deleted");
    exit();
}

// --- 3. HANDLE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = basename($_FILES["pdf_file"]["name"]);
    $target_file = $target_dir . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
    
    if (strtolower(pathinfo($target_file, PATHINFO_EXTENSION)) == "pdf") {
        if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO hdn_files (file_name, file_path) VALUES (?, ?)");
            $stmt->bind_param("ss", $file_name, $target_file);
            $stmt->execute();
            header("Location: hdn.php?msg=uploaded");
            exit();
        }
    }
}

// --- 4. FETCH DATA & DYNAMIC FILTER LOGIC ---
$year_query = $conn->query("SELECT DISTINCT YEAR(uploaded_at) as yr FROM hdn_files ORDER BY yr DESC");
$years = [];
while($y = $year_query->fetch_assoc()) $years[] = $y['yr'];

$allowed_cols = ['file_name', 'uploaded_at'];
$sort_col = in_array($sort_col, $allowed_cols) ? $sort_col : 'uploaded_at';
$sort_ord = ($sort_ord == 'ASC') ? 'ASC' : 'DESC';

$query = "SELECT * FROM hdn_files WHERE file_name LIKE ?";
$params = ["%$search%"];
$types = "s";

if (!empty($filter_year)) {
    $query .= " AND YEAR(uploaded_at) = ?";
    $params[] = $filter_year;
    $types .= "i";
}
if (!empty($filter_month)) {
    $query .= " AND MONTH(uploaded_at) = ?";
    $params[] = $filter_month;
    $types .= "i";
}

$query .= " ORDER BY $sort_col $sort_ord";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | HDN Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="inventory.css">
    <style>
        /* PDF Modal Styling */
        .pdf-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
        .pdf-modal-content { position: relative; background-color: #fff; margin: 2% auto; width: 80%; height: 85vh; border-radius: 12px; overflow: hidden; }
        .pdf-header { padding: 15px; background: #333; color: white; display: flex; justify-content: space-between; align-items: center; }
        .btn-back { background: #ff6600; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        iframe { width: 100%; height: calc(100% - 60px); border: none; }

        /* General UI */
        .search-row { display: flex; gap: 10px; margin-bottom: 25px; align-items: center; position: relative; }
        .search-group { flex: 1; display: flex; position: relative; }
        .search-input { width: 100%; padding: 12px 45px 12px 15px; border-radius: 10px; border: 1px solid #ddd; font-size: 14px; }
        .btn-search-icon { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; padding: 10px; }

        /* Filter Dropdown Styling */
        .filter-dropdown { position: relative; }
        .btn-filter-trigger { 
            background: white; border: 1px solid #ddd; padding: 11px 20px; border-radius: 10px; 
            cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; color: #333;
            transition: 0.2s;
        }
        .btn-filter-trigger:hover { background: #f9f9f9; border-color: #ff6600; }

        .filter-menu { 
            display: none; position: absolute; top: 115%; right: 0; background: white; 
            width: 300px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            z-index: 100; padding: 20px; border: 1px solid #eee;
        }

        .filter-section { margin-bottom: 18px; }
        .filter-label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; }

        /* Custom Checkbox Design */
        .checkbox-container { display: flex; flex-wrap: wrap; gap: 8px; }
        .custom-checkbox { 
            display: flex; align-items: center; position: relative; padding-left: 28px; 
            cursor: pointer; font-size: 14px; color: #475569; user-select: none; margin-bottom: 8px;
            min-width: 80px;
        }
        .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }

        .checkmark { 
            position: absolute; left: 0; top: 50%; transform: translateY(-50%); height: 18px; width: 18px; 
            background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 5px; transition: 0.2s; 
        }
        .custom-checkbox:hover input ~ .checkmark { background-color: #e2e8f0; }
        .custom-checkbox input:checked ~ .checkmark { background-color: #ff6600; border-color: #ff6600; }
        .checkmark:after { content: ""; position: absolute; display: none; }
        .custom-checkbox input:checked ~ .checkmark:after { display: block; }
        .custom-checkbox .checkmark:after { left: 6px; top: 2px; width: 4px; height: 9px; border: solid white; border-width: 0 2.5px 2.5px 0; transform: rotate(45deg); }

        .filter-actions { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; 
        }
        .btn-reset { color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-reset:hover { color: #ef4444; }
        .btn-apply { background: #333; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; }

        .btn-group { display: flex; justify-content: center; gap: 5px; }
        .action-btn { padding: 6px 10px; border-radius: 6px; color: white; border: none; cursor: pointer; }
        .btn-view { background: #0ea5e9; }
        .btn-download { background: #10b981; }
        .btn-delete { background: #ef4444; }
        .sort-link { text-decoration: none; color: inherit; font-weight: bold; }
        .sort-link:hover { color: #ff6600; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo"><a href="index.php" style="color: white; text-decoration: none; margin-right: 15px;"><i class="fa-solid fa-arrow-left"></i></a>OJTBox <span>| HDN Documents</span></div>
        </div>
    </nav>

    <main class="inventory-container">
        <div class="header-flex">
            <h2>Hard Drive Notifications</h2>
            
            <form id="uploadForm" action="" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" onchange="submitForm()">
            </form>

            <button onclick="triggerFileSelect()" style="background:#ff6600; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold;">
                <i class="fa-solid fa-plus"></i> Upload
            </button>
        </div>

        <form action="" method="GET" class="search-row" id="filterForm">
            <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_ord; ?>">
            
            <div class="search-group">
                <input type="text" name="search" class="search-input" placeholder="Search files..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>

            <div class="filter-dropdown">
                <button type="button" class="btn-filter-trigger" onclick="toggleFilterMenu()">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                
                <div id="filterMenu" class="filter-menu">
                    <div class="filter-section">
                        <label class="filter-label">Year</label>
                        <div class="checkbox-container">
                            <?php foreach($years as $yr): ?>
                                <label class="custom-checkbox">
                                    <input type="radio" name="year" value="<?php echo $yr; ?>" <?php if($filter_year == $yr) echo 'checked'; ?>>
                                    <span class="checkmark"></span>
                                    <?php echo $yr; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <label class="filter-label">Month</label>
                        <div class="checkbox-container" style="display: grid; grid-template-columns: repeat(3, 1fr);">
                            <?php 
                            for($m=1; $m<=12; $m++) {
                                $monthName = date('M', mktime(0, 0, 0, $m, 1));
                                $checked = ($filter_month == $m) ? 'checked' : '';
                                echo "
                                <label class='custom-checkbox'>
                                    <input type='radio' name='month' value='$m' $checked>
                                    <span class='checkmark'></span>
                                    $monthName
                                </label>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <a href="hdn.php" class="btn-reset">Clear All</a>
                        <button type="submit" class="btn-apply">Apply Filters</button>
                    </div>
                </div>
            </div>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>
                        <a href="?search=<?php echo $search; ?>&year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&sort=file_name&order=<?php echo $next_order; ?>" class="sort-link">
                            Document Name <i class="fa-solid fa-sort"></i>
                        </a>
                    </th>
                    <th>Size</th>
                    <th>
                        <a href="?search=<?php echo $search; ?>&year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&sort=uploaded_at&order=<?php echo $next_order; ?>" class="sort-link">
                            Date Uploaded <i class="fa-solid fa-sort"></i>
                        </a>
                    </th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $size = file_exists($row['file_path']) ? formatSize(filesize($row['file_path'])) : 'N/A';
                    ?>
                    <tr>
                        <td><i class="fa-solid fa-file-pdf" style="color:#e11d48;"></i> <?php echo htmlspecialchars($row['file_name']); ?></td>
                        <td><?php echo $size; ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <button onclick="openPdf('<?php echo $row['file_path']; ?>', '<?php echo addslashes($row['file_name']); ?>')" class="action-btn btn-view" title="View PDF">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <a href="<?php echo $row['file_path']; ?>" download="<?php echo $row['file_name']; ?>" class="action-btn btn-download" title="Download">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="action-btn btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px;">No documents found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="pdfModal" class="pdf-modal">
        <div class="pdf-modal-content">
            <div class="pdf-header">
                <span id="pdfTitle" style="font-weight: bold;">Document Viewer</span>
                <button class="btn-back" onclick="closePdf()">
                    <i class="fa-solid fa-arrow-left"></i> Back to List
                </button>
            </div>
            <iframe id="pdfFrame" src=""></iframe>
        </div>
    </div>

    <script>
        function triggerFileSelect() { document.getElementById('pdf_file').click(); }
        function submitForm() { if (document.getElementById('pdf_file').files.length > 0) document.getElementById('uploadForm').submit(); }

        function toggleFilterMenu() {
            const menu = document.getElementById('filterMenu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }

        window.onclick = function(event) {
            if (!event.target.closest('.filter-dropdown')) {
                document.getElementById('filterMenu').style.display = 'none';
            }
            if (event.target == document.getElementById('pdfModal')) closePdf();
        }

        function openPdf(filePath, fileName) {
            document.getElementById('pdfFrame').src = filePath;
            document.getElementById('pdfTitle').innerText = fileName;
            document.getElementById('pdfModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePdf() {
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('pdfFrame').src = '';
            document.body.style.overflow = 'auto';
        }

        function confirmDelete(id) {
            if (confirm("Delete this PDF?")) {
                window.location.href = "hdn.php?delete_id=" + id;
            }
        }
    </script>
</body>
</html>