<?php
session_start();
require_once 'db.php';

// Access Control
if ($_SESSION['role'] !== 'euc_admin') {
    header("Location: index_user.php");
    exit();
}

// Fetch logs with Usernames
$sql = "SELECT al.*, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJTBox | Activity Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       :root {
        --bg-dark: #0a0a0a;
        --card-bg: #111111;
        --primary-orange: #ff6600;
        --neon-green: #00ff99;
        --neon-blue: #00d4ff;
        --text-gray: #a0a0a0;
        --text-main: #ffffff;
        --border-color: #222222;
    }
        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
        }
        .inventory-container { padding: 40px; max-width: 1400px; margin: 0 auto; }
.data-table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0 10px; 
        table-layout: fixed; /* Prevents long text from pushing columns off-screen */
    }        
    
    .data-table tr { background: var(--card-bg); transition: transform 0.2s; }
        .data-table tr:hover { transform: scale(1.005); background: #161616; }

.data-table tbody tr { 
        background: var(--card-bg); 
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .data-table tbody tr:hover { 
        background: #161616;
        transform: translateX(5px);
        box-shadow: -5px 0 0 var(--primary-orange);
    }


.data-table th, .data-table td { 
        padding: 16px 20px; 
        vertical-align: middle; 
    }
    .col-time { width: 180px; }
    .col-user { width: 220px; }
    .col-action { width: 140px; }
    .col-details { width: auto; } /* Fluid column */


    .detail-container {
        max-height: 80px; /* Limits height initially */
        overflow-y: auto; /* Allows scrolling for huge logs */
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 0.8rem;
        color: #ccd0d5;
        line-height: 1.5;
        padding-right: 10px;
        word-wrap: break-word;
    }

    /* Custom Scrollbar for the details */
    .detail-container::-webkit-scrollbar { width: 4px; }
    .detail-container::-webkit-scrollbar-track { background: transparent; }
    .detail-container::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    


.badge { 
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px; 
        border-radius: 6px; 
        font-size: 0.65rem; 
        font-weight: 800; 
        letter-spacing: 0.5px;
    }        
    
    
    .badge-insert { background: rgba(0, 255, 153, 0.1); color: var(--neon-green); border: 1px solid rgba(0, 255, 153, 0.2); }
    .badge-update { background: rgba(0, 212, 255, 0.1); color: var(--neon-blue); border: 1px solid rgba(0, 212, 255, 0.2); }
    .badge-delete { background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); }
        .timestamp { color: var(--text-gray); font-weight: 600; font-size: 0.8rem; }
        .username-text { font-weight: 600; color: #fff; }




        /* Enhanced Operator Styles */
.operator-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar-circle {
    width: 32px;
    height: 32px;
    background: rgba(255, 102, 0, 0.1);
    border: 1px solid rgba(255, 102, 0, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 0 10px rgba(255, 102, 0, 0.1);
}

.avatar-circle i {
    font-size: 0.9rem;
    color: var(--primary-orange);
}

/* The small "Online/Live" status dot on the avatar */
.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 8px;
    height: 8px;
    background: var(--neon-green);
    border-radius: 50%;
    border: 2px solid var(--card-bg);
    box-shadow: 0 0 5px var(--neon-green);
}

.username-info {
    display: flex;
    flex-direction: column;
}

.username-main {
    font-weight: 700;
    color: #fff;
    font-size: 0.95rem;
    letter-spacing: -0.3px;
}

.user-role {
    font-size: 0.65rem;
    color: var(--text-gray);
    text-transform: uppercase;
    letter-spacing: 1px;

    #btn-system-status { 
    grid-column: span 1; 
    grid-row: span 1; 
}

/* Optional: Add a subtle cyan glow for the mail card on hover */
#btn-system-status:hover {
    border-color: #00d4ff;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 15px rgba(0, 212, 255, 0.2);
}
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <section class="header-section" style="margin-bottom: 30px;">
            <h4 style="color: var(--primary-orange); margin: 0; text-transform: uppercase; letter-spacing: 2px;">System Audit</h4>
            <h1 style="margin: 10px 0; font-size: 2.5rem;">User Activity Tracker</h1>
        </section>

        <table class="data-table">
    <thead>
        <tr>
            <th class="col-time">Timestamp</th>
            <th class="col-user">Operator</th>
            <th class="col-action">Action</th>
            <th class="col-details">Modification Details</th>
        </tr>
    </thead>
    <tbody>
        <?php while($log = $result->fetch_assoc()): ?>
        <tr>
            <td>
                <div class="timestamp">
                    <i class="fa-regular fa-clock" style="margin-right: 5px; opacity: 0.5;"></i>
                    <?= date('M d, y', strtotime($log['created_at'])) ?><br>
                    <small style="opacity: 0.6;"><?= date('h:i A', strtotime($log['created_at'])) ?></small>
                </div>
            </td>
            <td>
                <div class="operator-wrapper">
                    <div class="avatar-circle">
                        <i class="fa-solid fa-user-shield"></i>
                        <div class="status-indicator"></div>
                    </div>
                    <div class="username-info">
                        <span class="username-main"><?= htmlspecialchars($log['username'] ?? 'System') ?></span>
                        <span class="user-role">Logistics Admin</span>
                    </div>
                </div>
            </td>
            <td>
                <?php 
                    $action = strtoupper($log['action']);
                    $class = 'badge-update'; $icon = 'fa-rotate';
                    if (strpos($action, 'INSERT') !== false) { $class = 'badge-insert'; $icon = 'fa-plus'; }
                    elseif (strpos($action, 'DELETE') !== false) { $class = 'badge-delete'; $icon = 'fa-trash'; }
                ?>
                <span class="badge <?= $class ?>">
                    <i class="fa-solid <?= $icon ?>"></i> <?= $action ?>
                </span>
            </td>
            <td>
                <div class="detail-container">
                    <?= htmlspecialchars($log['details']) ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
    </main>
</body>
</html>