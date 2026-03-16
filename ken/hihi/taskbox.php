<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$username = $_SESSION['username'] ?? 'User';

$sql = "SELECT * FROM tasks ORDER BY date_given DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Task Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* DASHBOARD THEME: BLACK BACKGROUND */
        body { 
            background: #000; /* Matched to your black dashboard screenshot */
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden; 
        }
        
        .navbar { background: #ff8c00 ; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-left .logo { font-size: 1.5rem; font-weight: 800; color: #f4f7f6; }
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .user-menu { position: relative; display: inline-block; cursor: pointer; }
        .dropdown-content { display: none; position: absolute; right: 0; background: white; min-width: 150px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); border-radius: 8px; z-index: 100; }
        .dropdown-content a { color: #333; padding: 12px; text-decoration: none; display: block; }
        .user-menu:hover .dropdown-content { display: block; }

        /* Task Container */
        .task-container { 
            padding: 20px 40px; 
            max-width: 1200px; 
            margin: 0 auto; 
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative; /* Anchor for blur overlays */
            overflow: hidden;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; color: white; }
        
        /* SCROLLABLE AREA */
        .scroll-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 150px; /* Space for the fade area */
            scrollbar-width: none; 
        }
        .scroll-wrapper::-webkit-scrollbar { display: none; }

        /* --- THE GRADUAL FADE/BLUR EFFECT --- */
        .gradual-blur-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 180px; /* Height of the fade-to-black effect */
            pointer-events: none; 
            z-index: 99;
            background: linear-gradient(to bottom, 
                rgba(0, 0, 0, 0) 0%, 
                rgba(0, 0, 0, 0.4) 30%, 
                rgba(0, 0, 0, 0.8) 70%, 
                #000 100%
            );
        }

        /* View Switcher Controls */
        .view-switcher { display: flex; background: #1a1a1a; padding: 5px; border-radius: 10px; gap: 5px; margin-top: 10px; width: fit-content; }
        .view-btn { border: none; padding: 8px 15px; border-radius: 7px; cursor: pointer; background: transparent; color: #888; font-weight: 600; transition: 0.3s; }
        .view-btn.active { background: #333; color: #ff6600; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }

        /* Task Card Shared Styles (Dark Theme) */
        .task-card { 
            background: #111; /* Dark cards to match the dashboard */
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.5); 
            transition: 0.3s; 
            color: #eee;
            margin-bottom: 15px;
        }
        .task-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.6); }

        /* Layout 1: Horizontal (List) View */
        .list-view { display: flex; flex-direction: column; }
        .list-view .task-card { 
            display: flex; align-items: center; justify-content: space-between; 
            border-left: 6px solid #ccc; padding: 20px 25px; 
        }

        /* Layout 2: Box (Grid) View */
        .box-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .box-view .task-card { 
            flex-direction: column; align-items: flex-start; gap: 15px;
            border-top: 6px solid #ccc; border-left: none; padding: 25px;
        }

        /* Status Colors */
        .status-pending { border-color: #ffa800 !important; }
        .status-inprogress { border-color: #3699ff !important; }
        .status-done { border-color: #28a745 !important; }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: rgba(255, 168, 0, 0.1); color: #ffa800; }
        .badge-inprogress { background: rgba(54, 153, 255, 0.1); color: #3699ff; }
        .badge-done { background: rgba(40, 167, 69, 0.1); color: #28a745; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); }
        .modal-content { background: #1a1a1a; color: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        input, select, textarea { background: #222; color: white; border: 1px solid #333; width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .save-btn { background: #ff6600; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="task-container">
        <div class="header-flex">
            <div>
                <h2 style="margin:0;">Project Tasks</h2>
                <div class="view-switcher">
                    <button class="view-btn active" id="listBtn" onclick="switchView('list')"><i class="fa-solid fa-list"></i> Horizontal</button>
                    <button class="view-btn" id="boxBtn" onclick="switchView('box')"><i class="fa-solid fa-grip"></i> Box Type</button>
                </div>
            </div>
            <button onclick="openTaskModal()" style="background:#ff6600; color:white; border:none; padding:12px 25px; border-radius:10px; cursor:pointer; font-weight:bold; box-shadow: 0 4px 10px rgba(255,102,0,0.4);">
                <i class="fa-solid fa-plus"></i> Assign New Task
            </button>
        </div>

        <div class="scroll-wrapper">
            <div id="taskWrapper" class="list-view">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $statusKey = strtolower(str_replace('-', '', $row['status']));
                    ?>
                    <div class="task-card status-<?php echo $statusKey; ?>">
                        <div style="flex: 2;">
                            <span style="color: #ff6600; font-size: 0.75rem; font-weight: 800;"><?php echo $row['date_given']; ?></span>
                            <strong style="display:block; font-size:1.15rem; margin: 5px 0;"><?php echo htmlspecialchars($row['task_description']); ?></strong>
                            <p style="margin:0; color: #888; font-size: 0.9rem;"><?php echo htmlspecialchars($row['comment']); ?></p>
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="font-size: 0.7rem; color: #555; text-transform: uppercase; font-weight: bold;">Assignee</div>
                            <div style="font-weight: 600; color: #aaa;"><i class="fa-solid fa-user-tag" style="color:#444"></i> <?php echo htmlspecialchars($row['assigned_to']); ?></div>
                        </div>

                        <div style="flex: 1; text-align: center;">
                            <span class="status-badge badge-<?php echo $statusKey; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>

                        <div style="text-align: right;">
                            <button onclick='editTask(<?php echo json_encode($row); ?>)' style="border:none; background:#222; color:#ff6600; padding:10px; border-radius:50%; cursor:pointer; width:40px; height:40px;">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:50px; background:#111; border-radius:15px; color:#555;">
                        <i class="fa-solid fa-folder-open" style="font-size:3rem; margin-bottom:15px; opacity:0.2;"></i>
                        <p>No tasks found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="gradual-blur-overlay"></div>
    </main>

    <div id="taskModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-top:0;">Assign New Task</h3>
            <form id="taskForm" action="task_CRUD.php" method="POST">
                <input type="hidden" name="id" id="taskId">
                
                <label>Task Description</label>
                <textarea name="task_description" id="taskDesc" rows="3" required></textarea>
                
                <label>Assigned To</label>
                <input type="text" name="assigned_to" id="taskAssigned" required>
                
                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label>Date Given</label>
                        <input type="date" name="date_given" id="taskGiven" required>
                    </div>
                    <div style="flex:1;">
                        <label>Date Completed</label>
                        <input type="date" name="date_completed" id="taskCompleted">
                    </div>
                </div>

                <label>Status</label>
                <select name="status" id="taskStatus">
                    <option value="Pending">Pending</option>
                    <option value="In-Progress">In-Progress</option>
                    <option value="Done">Done</option>
                </select>

                <label>Comment</label>
                <textarea name="comment" id="taskComment" maxlength="250" oninput="checkLimit(this)"></textarea>
                <div id="charCount" style="font-size: 0.8rem; color: #718096; text-align: right; margin-top: -8px;">0 / 250 characters</div>
                <div id="limitWarning" style="color: #e53e3e; font-size: 0.8rem; display: none; font-weight: bold;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Limit reached! You cannot type more.
                </div>

                <button type="submit" class="save-btn">SAVE TASK</button>
                <button type="button" onclick="closeTaskModal()" style="background:none; border:none; color:#718096; width:100%; cursor:pointer; margin-top:10px; font-weight:600;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function switchView(viewType) {
            const wrapper = document.getElementById('taskWrapper');
            const listBtn = document.getElementById('listBtn');
            const boxBtn = document.getElementById('boxBtn');

            if (viewType === 'box') {
                wrapper.classList.remove('list-view');
                wrapper.classList.add('box-view');
                boxBtn.classList.add('active');
                listBtn.classList.remove('active');
            } else {
                wrapper.classList.remove('box-view');
                wrapper.classList.add('list-view');
                listBtn.classList.add('active');
                boxBtn.classList.remove('active');
            }
        }

        function openTaskModal() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('modalTitle').innerText = "Assign New Task";
            document.getElementById('taskModal').style.display = 'block';
        }

        function closeTaskModal() { 
            document.getElementById('taskModal').style.display = 'none'; 
        }

        function editTask(data) {
            document.getElementById('taskId').value = data.id;
            document.getElementById('taskDesc').value = data.task_description;
            document.getElementById('taskAssigned').value = data.assigned_to;
            document.getElementById('taskGiven').value = data.date_given;
            document.getElementById('taskCompleted').value = data.date_completed;
            document.getElementById('taskStatus').value = data.status;
            document.getElementById('taskComment').value = data.comment;
            document.getElementById('modalTitle').innerText = "Edit Task";
            document.getElementById('taskModal').style.display = 'block';
            checkLimit(document.getElementById('taskComment'));
        }

        window.onclick = function(event) {
            let modal = document.getElementById('taskModal');
            if (event.target == modal) closeTaskModal();
        }

        function checkLimit(textarea) {
            const limit = 250;
            const countDisplay = document.getElementById('charCount');
            const warning = document.getElementById('limitWarning');
            const currentLength = textarea.value.length;

            countDisplay.innerText = `${currentLength} / ${limit} characters`;

            if (currentLength >= limit) {
                textarea.style.borderColor = "#e53e3e";
                warning.style.display = "block";
                countDisplay.style.color = "#e53e3e";
            } else {
                textarea.style.borderColor = "#ddd";
                warning.style.display = "none";
                countDisplay.style.color = "#718096";
            }
        }
    </script>
</body>
</html>