<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security & Database
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db.php';

// --- 1. SETTINGS & FILTERS ---
$username = $_SESSION['username'] ?? 'User';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// --- 2. SMART SEARCH QUERY ---
$searchTerm = "%$search%";
$query = "SELECT * FROM tasks WHERE (task_description LIKE ? OR assigned_to LIKE ?)";
$params = [$searchTerm, $searchTerm];
$types = "ss";

// Tab Logic
if ($filter_tab === 'active') {
    $query .= " AND status != 'Done'";
} elseif ($filter_tab === 'completed') {
    $query .= " AND status = 'Done'";
}

// Sort by the most recently updated activity
$query .= " ORDER BY last_updated DESC, date_given DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | Task Archive</title>
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
        }

        [data-theme="light"] {
            --bg-dark: #f5f5f7;
            --card-bg: #ffffff;
            --text-main: #1d1d1f;
            --text-gray: #6e6e73;
            --border-color: rgba(0, 0, 0, 0.1);
            --input-bg: #e5e5e7;
        }

        /* --- Page Reveal Animations From Prod Map --- */
        @keyframes pageReveal {
            from { opacity: 0; transform: translateY(20px) scale(0.98); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        @keyframes staggerIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            transition: var(--transition);
            /* Added Page Reveal */
            animation: pageReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .inventory-container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .header-flex h2 { font-size: 2.5rem; font-weight: 800; margin: 0; letter-spacing: -1px; }

        .tab-container { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .tab-link { 
            text-decoration: none; color: var(--text-gray); padding: 8px 20px; 
            border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: var(--transition);
        }
        .tab-link:hover { color: var(--primary-orange); }
        .tab-link.active { background: var(--primary-orange); color: white; }

        .search-row { display: flex; gap: 15px; margin-bottom: 30px; }
        .search-group { flex: 1; display: flex; background: var(--input-bg); border-radius: 10px; border: 1px solid var(--border-color); }
        .search-input { width: 100%; background: transparent; border: none; color: var(--text-main); padding: 12px 15px; outline: none; }

        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .data-table th { 
            text-align: left; 
            color: var(--primary-orange); 
            padding: 15px 20px; 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            background: var(--input-bg);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
        }
        .data-table th:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; }
        .data-table th:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }

        .data-table tr { background: var(--card-bg); transition: var(--transition); }
        .data-table td { padding: 15px 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .data-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; width: 40px; }
        .data-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }

        /* Row Hover Effect from Prod Map logic */
        .data-table tbody tr:hover {
            transform: translateY(-4px);
            border-color: var(--primary-orange);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .custom-checkbox { width: 22px; height: 22px; cursor: pointer; accent-color: var(--primary-orange); }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .badge-done { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-pending { background: rgba(255, 102, 0, 0.1); color: #ff6600; }

        .action-btn { 
            padding: 8px; border-radius: 8px; color: var(--text-main); 
            border: 1px solid var(--border-color); cursor: pointer; background: var(--card-bg); 
        }
        .btn-edit:hover { border-color: #0ea5e9; color: #0ea5e9; }
        .btn-delete:hover { border-color: #ef4444; color: #ef4444; }
        .strikethrough { text-decoration: line-through; opacity: 0.5; }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <div class="header-flex">
            <h2><span style="color:var(--primary-orange)">Task</span> Archive</h2>
            <button onclick="window.location.href='add_task.php'" style="background:var(--primary-orange); color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:bold; cursor:pointer;">
                <i class="fa-solid fa-plus"></i> New Task
            </button>
        </div>

        <div class="tab-container">
            <a href="?tab=all&search=<?php echo urlencode($search); ?>" class="tab-link <?php echo ($filter_tab == 'all') ? 'active' : ''; ?>">All</a>
            <a href="?tab=active&search=<?php echo urlencode($search); ?>" class="tab-link <?php echo ($filter_tab == 'active') ? 'active' : ''; ?>">Active</a>
            <a href="?tab=completed&search=<?php echo urlencode($search); ?>" class="tab-link <?php echo ($filter_tab == 'completed') ? 'active' : ''; ?>">Completed</a>
        </div>

        <form action="" method="GET" class="search-row">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($filter_tab); ?>">
            <div class="search-group">
                <input type="text" name="search" class="search-input" placeholder="Search tasks or assignees..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="background:none; border:none; color:var(--primary-orange); padding:0 15px; cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th></th> 
                    <th>Task Description</th>
                    <th>Assigned To</th>
                    <th>Date Given</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php 
                    $index = 0;
                    while($row = $result->fetch_assoc()): 
                        $isDone = ($row['status'] === 'Done');
                        $delay = $index * 0.05; // Stagger effect logic
                    ?>
                    <tr style="animation: staggerIn 0.5s ease forwards; animation-delay: <?php echo $delay; ?>s; opacity: 0;">
                        <td>
                            <input type="checkbox" class="custom-checkbox" 
                                   onchange="toggleTaskStatus(<?php echo $row['id']; ?>, this.checked)"
                                   <?php echo $isDone ? 'checked' : ''; ?>>
                        </td>
                        <td class="<?php echo $isDone ? 'strikethrough' : ''; ?>" style="font-weight: 600;">
                            <?php echo htmlspecialchars($row['task_description']); ?>
                        </td>
                        <td style="color: var(--text-gray);">
                            <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($row['assigned_to']); ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['date_given'])); ?></td>
                        <td>
                            <span class="badge <?php echo $isDone ? 'badge-done' : 'badge-pending'; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap: 8px; justify-content:center;">
                                <button onclick="editTask(<?php echo $row['id']; ?>)" class="action-btn btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <button onclick="deleteTask(<?php echo $row['id']; ?>)" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php 
                    $index++;
                    endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:50px; color:var(--text-gray);">NO TASKS FOUND FOR "<?php echo htmlspecialchars($search); ?>"</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script>
        // Theme Sync
        (function() {
            const savedTheme = localStorage.getItem('ojtbox_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        function toggleTaskStatus(taskId, isChecked) {
            const newStatus = isChecked ? 'Done' : 'Pending';
            const currentFile = window.location.pathname.split('/').pop();
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search') || '';
            const targetTab = urlParams.get('tab') || 'all';

            window.location.href = `task_CRUD.php?toggle_id=${taskId}&status=${newStatus}&redirect=${currentFile}&tab=${targetTab}&search=${encodeURIComponent(searchQuery)}`;
        }

        function deleteTask(id) {
            const currentFile = window.location.pathname.split('/').pop();
            if(confirm("Are you sure you want to delete this task?")) {
                window.location.href = `task_CRUD.php?delete_id=${id}&redirect=${currentFile}`;
            }
        }

        function editTask(id) {
            window.location.href = "add_task.php?id=" + id;
        }
    </script>
</body>
</html>