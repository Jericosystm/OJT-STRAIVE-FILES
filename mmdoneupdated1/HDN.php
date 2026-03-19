<?php
session_start();

// Existing session check...
$username = $_SESSION['username'] ?? 'User'; 
$user_role = $_SESSION['role'] ?? 'euc_user'; 
$back_link = ($user_role === 'euc_admin') ? 'index_admin.php' : 'index_user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// --- 4. FETCH DATA ---
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
    <style>
        :root {
            /* Default Dark Mode */
            --bg-dark: #0a0a0a;
            --card-bg: #111111;
            --primary-orange: #ff6600;
            --neon-blue: #0ea5e9;
            --neon-green: #10b981;
            --text-main: #ffffff;
            --text-gray: #a0a0a0;
            --border-color: #222222;
            --input-bg: #151515;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Light Mode Implementation */
        [data-theme="light"] {
            --bg-dark: #f5f5f7;
            --card-bg: #ffffff;
            --text-main: #1d1d1f;
            --text-gray: #6e6e73;
            --border-color: rgba(0, 0, 0, 0.1);
            --input-bg: #e5e5e7;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            animation: fadeInPage 0.8s ease-out;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .inventory-container {
            padding: 100px 40px 40px; /* Inadjust ang top padding para sa header */
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header UI */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-flex h2 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-main);
        }

        /* Search & Filter Row */
        .search-row {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            align-items: center;
        }

        .search-group {
            flex: 1;
            display: flex;
            background: var(--input-bg);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
        }

        .search-group:focus-within {
            border-color: var(--primary-orange);
            box-shadow: 0 0 15px rgba(255, 102, 0, 0.1);
        }

        .search-input {
            width: 100%;
            background: transparent;
            border: none;
            color: var(--text-main);
            padding: 12px 15px;
            outline: none;
        }

        .btn-search-icon {
            background: none;
            border: none;
            color: var(--primary-orange);
            cursor: pointer;
            padding: 0 15px;
        }

        /* Filter Dropdown */
        .filter-dropdown { position: relative; }
        .btn-filter-trigger { 
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 11px 20px;
            border-radius: 10px; 
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-main);
            transition: var(--transition);
        }
        .btn-filter-trigger:hover { 
            border-color: var(--primary-orange);
            background: var(--input-bg);
        }

        .filter-menu { 
            display: none;
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--card-bg); 
            width: 300px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6); 
            z-index: 100;
            padding: 20px;
            border: 1px solid var(--border-color);
            animation: fadeInPage 0.3s ease;
        }

        .filter-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--primary-orange);
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .data-table th {
            text-align: left;
            color: var(--text-gray);
            padding: 0 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-table tr {
            background: var(--card-bg);
            transition: var(--transition);
        }

        .data-table tbody tr:hover {
            transform: scale(1.01) translateX(5px);
            background: var(--input-bg);
            box-shadow: -5px 0 0 var(--primary-orange), 0 10px 20px rgba(0,0,0,0.2);
        }

        .data-table td {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .data-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; }
        .data-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }

        .sort-link { text-decoration: none; color: inherit; transition: 0.3s; }
        .sort-link:hover { color: var(--primary-orange); }

        /* Action Buttons */
        .btn-group { display: flex; justify-content: center; gap: 8px; }
        .action-btn { 
            padding: 10px;
            border-radius: 8px;
            color: var(--text-main);
            border: 1px solid var(--border-color);
            cursor: pointer;
            background: var(--card-bg);
            transition: var(--transition);
        }
        .btn-view:hover { border-color: var(--neon-blue); color: var(--neon-blue); transform: scale(1.1); }
        .btn-download:hover { border-color: var(--neon-green); color: var(--neon-green); transform: scale(1.1); }
        .btn-delete:hover { border-color: #ef4444; color: #ef4444; transform: scale(1.1); }

        .btn-upload-main {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(255, 102, 0, 0.3);
            transition: var(--transition);
        }
        .btn-upload-main:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(255, 102, 0, 0.5); }

        /* PDF Modal */
        .pdf-modal { 
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.9); backdrop-filter: blur(8px);
        }
        .pdf-modal-content { 
            position: relative; background: var(--bg-dark); margin: 2% auto; width: 85%; height: 88vh; 
            border-radius: 15px; overflow: hidden; border: 1px solid var(--border-color);
            animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .pdf-header { padding: 15px 25px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        iframe { width: 100%; height: calc(100% - 70px); border: none; background: #fff; }

        /* Checkbox Styling */
        .custom-checkbox { 
            display: flex; align-items: center; position: relative; padding-left: 30px; 
            cursor: pointer; font-size: 14px; color: var(--text-gray); margin-bottom: 10px;
        }
        .custom-checkbox input { position: absolute; opacity: 0; }
        .checkmark { 
            position: absolute; left: 0; height: 18px; width: 18px; 
            background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 4px; 
        }
        .custom-checkbox input:checked ~ .checkmark { background: var(--primary-orange); border-color: var(--primary-orange); }

        .filter-actions { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); 
        }
        .btn-apply { background: var(--primary-orange); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
    
<?php include 'header.php'; ?>
    <main class="inventory-container">
        <div class="header-flex">
            <h2><span style="color:var(--primary-orange)">Archive</span> Nodes</h2>
            
            <form id="uploadForm" action="" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" onchange="submitForm()">
            </form>

            <button onclick="triggerFileSelect()" class="btn-upload-main">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
            </button>
        </div>

        <form action="" method="GET" class="search-row" id="filterForm">
            <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_ord; ?>">
            
            <div class="search-group">
                <input type="text" name="search" class="search-input" placeholder="Search archive by filename..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>

            <div class="filter-dropdown">
                <button type="button" class="btn-filter-trigger" onclick="toggleFilterMenu()">
                    <i class="fa-solid fa-sliders"></i> Filters
                </button>
                
                <div id="filterMenu" class="filter-menu">
                    <div class="filter-section">
                        <label class="filter-label">Year of Log</label>
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
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr);">
                            <?php 
                            for($m=1; $m<=12; $m++) {
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
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
                        <a href="hdn.php" style="color:var(--text-gray); text-decoration:none; font-size:13px;">Reset</a>
                        <button type="submit" class="btn-apply">Apply Data</button>
                    </div>
                </div>
            </div>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>
                        <a href="?search=<?php echo $search; ?>&year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&sort=file_name&order=<?php echo $next_order; ?>" class="sort-link">
                            Document Identity <i class="fa-solid fa-sort"></i>
                        </a>
                    </th>
                    <th>Weight (Size)</th>
                    <th>
                        <a href="?search=<?php echo $search; ?>&year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&sort=uploaded_at&order=<?php echo $next_order; ?>" class="sort-link">
                            Timestamp <i class="fa-solid fa-sort"></i>
                        </a>
                    </th>
                    <th style="text-align: center;">Command</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $size = file_exists($row['file_path']) ? formatSize(filesize($row['file_path'])) : 'N/A';
                    ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <i class="fa-solid fa-file-pdf" style="color:var(--primary-orange); margin-right:10px;"></i>
                            <?php echo htmlspecialchars($row['file_name']); ?>
                        </td>
                        <td style="color: var(--text-gray);"><?php echo $size; ?></td>
                        <td>
                            <span style="display:block; font-size: 14px;"><?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?></span>
                            <span style="font-size: 11px; color: var(--primary-orange);"><?php echo date('H:i A', strtotime($row['uploaded_at'])); ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button onclick="openPdf('<?php echo $row['file_path']; ?>', '<?php echo addslashes($row['file_name']); ?>')" class="action-btn btn-view" title="View">
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
                    <tr><td colspan="4" style="text-align:center; padding:50px; color:var(--text-gray);">NO DOCUMENTS DETECTED IN ARCHIVE.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div id="pdfModal" class="pdf-modal">
        <div class="pdf-modal-content">
            <div class="pdf-header">
                <span id="pdfTitle" style="font-weight: bold; color: var(--primary-orange);"><i class="fa-solid fa-file-lines"></i> Document Viewer</span>
                <button class="btn-upload-main" onclick="closePdf()" style="padding: 5px 15px; font-size: 12px;">
                    <i class="fa-solid fa-xmark"></i> Close Preview
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
                const menu = document.getElementById('filterMenu');
                if(menu) menu.style.display = 'none';
            }
            if (event.target == document.getElementById('pdfModal')) closePdf();
        }

        function openPdf(filePath, fileName) {
            document.getElementById('pdfFrame').src = filePath;
            document.getElementById('pdfTitle').innerHTML = '<i class="fa-solid fa-file-pdf"></i> ' + fileName;
            const modal = document.getElementById('pdfModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePdf() {
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('pdfFrame').src = '';
            document.body.style.overflow = 'auto';
        }

        function confirmDelete(id) {
            if (confirm("DECOMMISSION LOG: Are you sure you want to permanently delete this document?")) {
                window.location.href = "hdn.php?delete_id=" + id;
            }
        }
    </script>
</body>
</html>