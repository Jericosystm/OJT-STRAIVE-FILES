<?php
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$user_role = $_SESSION['role'] ?? 'euc_user';
$username = $_SESSION['username'] ?? 'User'; 

$back_link = ($user_role === 'euc_admin') ? 'index_admin.php' : 'index_user.php';

// Fetch all tasks
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
        /* Base Page Styles */
        body { 
            background: #030303; 
            margin: 0; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            color: white;
            background-image: radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.05), transparent 40%);
            background-attachment: fixed;
        }
        
        .task-container { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        .view-switcher { display: flex; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 10px; gap: 5px; width: fit-content; border: 1px solid rgba(255,255,255,0.1); }
        .view-btn { border: none; padding: 8px 15px; border-radius: 7px; cursor: pointer; background: transparent; color: #64748b; font-weight: 600; transition: 0.3s; }
        .view-btn.active { background: #ff6600; color: white; }

        .status-tabs { display: flex; gap: 20px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .tab-btn { background: none; border: none; padding: 12px 20px; cursor: pointer; font-weight: 600; color: #718096; border-bottom: 3px solid transparent; transition: 0.3s; }
        .tab-btn.active-tab { color: #ff6600; border-bottom: 3px solid #ff6600; }
        .hidden-task { display: none !important; }

        .task-card { 
            background: rgba(255, 255, 255, 0.03); 
            border-radius: 15px; 
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .task-card.show { opacity: 1; transform: translateY(0); }

        .list-view { display: flex; flex-direction: column; gap: 15px; }
        .list-view .task-card { display: flex; align-items: center; justify-content: space-between; border-left: 6px solid #ccc; padding: 20px 25px; }

        .box-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .box-view .task-card { flex-direction: column; align-items: flex-start; gap: 15px; border-top: 6px solid #ccc; border-left: none; padding: 25px; }

        /* Status Colors */
        .status-pending { border-color: #ffa800 !important; }
        .status-inprogress { border-color: #3699ff !important; }
        .status-done { border-color: #28a745 !important; }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: rgba(255, 168, 0, 0.1); color: #ffa800; }
        .badge-inprogress { background: rgba(54, 153, 255, 0.1); color: #3699ff; }
        .badge-done { background: rgba(40, 167, 69, 0.1); color: #28a745; }

        .check-btn { width: 32px; height: 32px; border: 2px solid rgba(255,255,255,0.2); border-radius: 8px; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; color: white; }
        .check-btn.checked { background: #28a745; border-color: #28a745; }

        /* Modal Styles - FIXED DROPDOWN VISIBILITY */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); }
        .modal-content { background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); margin: 5% auto; padding: 30px; border-radius: 20px; width: 450px; color: white; }
        input, select, textarea { 
            background: rgba(255,255,255,0.1); 
            color: white; 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 8px; 
            box-sizing: border-box; 
        }
        
        /* Fix for dropdown text being invisible on some browsers */
        select option {
            background: #1a1a1a;
            color: white;
        }

        label { font-size: 0.85rem; color: #cbd5e0; font-weight: 600; }
        .save-btn { background: #ff6600; color: white; border: none; padding: 14px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .delete-btn { background: rgba(220, 38, 38, 0.15); color: #ef4444; border: 1px solid rgba(220, 38, 38, 0.2); padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="task-container">
        <div class="header-flex">
            <div>
                <h2 style="margin:0; color: #fff;">Project Tasks</h2>
                <div class="view-switcher">
                    <button class="view-btn" id="listBtn" onclick="switchView('list')"><i class="fa-solid fa-list"></i> List</button>
                    <button class="view-btn" id="boxBtn" onclick="switchView('box')"><i class="fa-solid fa-grip"></i> Grid</button>
                </div>
            </div>
            <button onclick="openTaskModal()" style="background:#ff6600; color:white; border:none; padding:12px 25px; border-radius:10px; cursor:pointer; font-weight:bold; box-shadow: 0 4px 10px rgba(255,102,0,0.2);">
                <i class="fa-solid fa-plus"></i> Assign New Task
            </button>
        </div>

        <div class="status-tabs">
            <button onclick="filterStatus('all')" id="tabAll" class="tab-btn active-tab">All Tasks</button>
            <button onclick="filterStatus('active')" id="tabActive" class="tab-btn">Active</button>
            <button onclick="filterStatus('completed')" id="tabCompleted" class="tab-btn">Completed</button>
        </div>

        <div id="taskWrapper" class="list-view">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $statusKey = strtolower(str_replace('-', '', $row['status']));
                    $isDone = ($row['status'] === 'Done');
                ?>
                <div class="task-card status-<?php echo $statusKey; ?>" style="<?php echo $isDone ? 'opacity: 0.7;' : ''; ?>">
                    <div style="display: flex; align-items: center; gap: 20px; flex: 2;">
                        <form action="task_CRUD.php" method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="toggle_status" value="1">
                            <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                            <button type="submit" class="check-btn <?php echo $isDone ? 'checked' : ''; ?>">
                                <?php if($isDone): ?><i class="fa-solid fa-check"></i><?php endif; ?>
                            </button>
                        </form>

                        <div>
                            <span style="color: #ff6600; font-size: 0.75rem; font-weight: 800; display: block;">
                                <?php 
                                    if(!empty($row['date_given']) && $row['date_given'] !== '0000-00-00') {
                                        echo date("M d, Y", strtotime($row['date_given']));
                                    } else {
                                        echo "No Date Set";
                                    }
                                ?>
                            </span>
                            <strong style="display:block; font-size:1.15rem; margin: 5px 0; color: #fff; <?php echo $isDone ? 'text-decoration: line-through; color: #a0aec0;' : ''; ?>">
                                <?php echo htmlspecialchars($row['task_description'] ?: 'Untitled Task'); ?>
                            </strong>
                            <p style="margin:0; color: rgba(255,255,255,0.5); font-size: 0.9rem;"><?php echo htmlspecialchars($row['comment']); ?></p>
                        </div>
                    </div>
                    
                    <div style="flex: 1; min-width: 120px;">
                        <div style="font-size: 0.7rem; color: #718096; text-transform: uppercase; font-weight: bold;">Assignee</div>
                        <div style="font-weight: 600; color: #cbd5e0;"><i class="fa-solid fa-user-tag" style="color:#ff6600"></i> <?php echo htmlspecialchars($row['assigned_to'] ?: 'N/A'); ?></div>
                    </div>

                    <div style="flex: 0.8; text-align: center;">
                        <span class="status-badge badge-<?php echo $statusKey; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </div>

                    <div style="text-align: right;">
                        <button onclick='editTask(<?php echo json_encode($row); ?>)' style="border:none; background:rgba(255,255,255,0.1); color:#ff6600; padding:10px; border-radius:50%; cursor:pointer; width:40px; height:40px;">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:80px 20px; background:rgba(255,255,255,0.02); border-radius:15px; color:#718096; border: 1px dashed rgba(255,255,255,0.1); width:100%; box-sizing:border-box;">
                    <i class="fa-solid fa-folder-open" style="font-size:3rem; margin-bottom:15px; opacity:0.2;"></i>
                    <p>No tasks found in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="taskModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-top:0; color: #fff;">Assign New Task</h3>
            <form id="taskForm" action="task_CRUD.php" method="POST">
                <input type="hidden" name="id" id="taskId">
                
                <label>Task Description</label>
                <textarea name="task_description" id="taskDesc" rows="3" required placeholder="What needs to be done?"></textarea>
                
                <label>Assigned To</label>
                <input type="text" name="assigned_to" id="taskAssigned" required placeholder="Name">
                
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

                <label>Comment / Notes</label>
                <textarea name="comment" id="taskComment" maxlength="250" oninput="checkLimit(this)"></textarea>
                <div id="charCount" style="font-size: 0.75rem; color: #718096; text-align: right; margin-top: -8px;">0 / 250</div>

                <button type="submit" class="save-btn">SAVE TASK</button>
                
                <button type="button" id="deleteBtn" class="delete-btn" onclick="confirmDelete()" style="display:none;">
                    <i class="fa-solid fa-trash-can"></i> DELETE TASK
                </button>

                <button type="button" onclick="closeTaskModal()" style="background:none; border:none; color:#718096; width:100%; cursor:pointer; margin-top:10px; font-weight:600;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function filterStatus(statusType) {
            const tasks = document.querySelectorAll('.task-card');
            document.getElementById('tabAll').classList.toggle('active-tab', statusType === 'all');
            document.getElementById('tabActive').classList.toggle('active-tab', statusType === 'active');
            document.getElementById('tabCompleted').classList.toggle('active-tab', statusType === 'completed');

            tasks.forEach(task => {
                const statusBadge = task.querySelector('.status-badge');
                const status = statusBadge ? statusBadge.innerText.trim().toLowerCase() : '';

                if (statusType === 'all') {
                    task.classList.remove('hidden-task');
                } else if (statusType === 'completed') {
                    status === 'done' ? task.classList.remove('hidden-task') : task.classList.add('hidden-task');
                } else if (statusType === 'active') {
                    status !== 'done' ? task.classList.remove('hidden-task') : task.classList.add('hidden-task');
                }
            });
        }

        function switchView(viewType) {
            const wrapper = document.getElementById('taskWrapper');
            const listBtn = document.getElementById('listBtn');
            const boxBtn = document.getElementById('boxBtn');

            wrapper.className = (viewType === 'box') ? 'box-view' : 'list-view';
            listBtn.classList.toggle('active', viewType === 'list');
            boxBtn.classList.toggle('active', viewType === 'box');

            localStorage.setItem('taskViewPref', viewType);
        }

        function openTaskModal() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('modalTitle').innerText = "Assign New Task";
            document.getElementById('deleteBtn').style.display = 'none';
            document.getElementById('taskGiven').valueAsDate = new Date();
            document.getElementById('taskModal').style.display = 'block';
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
            document.getElementById('deleteBtn').style.display = 'block';
            document.getElementById('taskModal').style.display = 'block';
            checkLimit(document.getElementById('taskComment'));
        }

        function closeTaskModal() { document.getElementById('taskModal').style.display = 'none'; }

        function confirmDelete() {
            const id = document.getElementById('taskId').value;
            if (confirm("Permanently delete this task?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'task_CRUD.php';
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'delete_task'; input.value = id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function checkLimit(textarea) {
            const countDisplay = document.getElementById('charCount');
            countDisplay.innerText = `${textarea.value.length} / 250`;
        }

        window.onload = () => { 
            const savedView = localStorage.getItem('taskViewPref') || 'list';
            switchView(savedView);
            const cards = document.querySelectorAll('.task-card');
            cards.forEach((card, index) => {
                setTimeout(() => card.classList.add('show'), 70 * index);
            });
        };

        window.onclick = (e) => { if (e.target == document.getElementById('taskModal')) closeTaskModal(); }
    </script>
</body>
</html>