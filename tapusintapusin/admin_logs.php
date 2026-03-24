<?php
session_start();
require_once 'db.php';

// Access Control
if ($_SESSION['role'] !== 'euc_admin') {
    header("Location: index_user.php");
    exit();
}

// Fetch logs with Usernames
$sql = "SELECT al.*, u.username, u.role 
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Default Dark Mode (Kinuha sa prod_map) */
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.4);
            --bg: #030303;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #FFFFFF;
            --text-muted: rgba(255, 255, 255, 0.5);
            
            --neon-green: #00ff99;
            --neon-blue: #00d4ff;
        }

        /* Light Mode Overrides (Kinuha sa prod_map) */
        [data-theme="light"] {
            --bg: #F5F5F7;
            --card-bg: #FFFFFF;
            --card-hover: #E8E8ED;
            --border: rgba(0, 0, 0, 0.1);
            --text-main: #1D1D1F;
            --text-muted: #6E6E73;
        }

        /* Page Reveal Animations */
        @keyframes pageReveal {
            from { opacity: 0; transform: translateY(20px); filter: blur(10px); }
            to { opacity: 1; transform: translateY(0); filter: blur(0); }
        }

        @keyframes staggerIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        body { 
            background-color: var(--bg); 
            color: var(--text-main); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            min-height: 100vh;
            transition: background-color 0.4s ease, color 0.4s ease;
            animation: pageReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .inventory-container { padding: 40px; max-width: 1400px; margin: 0 auto; }

        .data-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0 10px; 
            table-layout: fixed;
        }        
    
        .data-table tbody tr { 
            background: var(--card-bg); 
            border: 1px solid var(--border);
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: staggerIn 0.5s ease forwards;
        }

        .data-table tbody tr:hover { 
            background: var(--card-hover);
            transform: translateX(8px);
            box-shadow: -5px 0 0 var(--primary);
        }

        .data-table th {
            text-align: left;
            padding: 10px 20px;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-table td { 
            padding: 16px 20px; 
            vertical-align: middle; 
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .col-time { width: 180px; }
        .col-user { width: 220px; }
        .col-action { width: 140px; }
        .col-details { width: auto; }

        .detail-container {
            max-height: 80px; 
            overflow-y: auto; 
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-main);
            opacity: 0.9;
            line-height: 1.5;
            word-wrap: break-word;
        }

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
        
        .timestamp { color: var(--text-muted); font-weight: 600; font-size: 0.8rem; }

        /* Enhanced Operator Styles */
        .operator-wrapper { display: flex; align-items: center; gap: 12px; }

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
        }

        .avatar-circle i { font-size: 0.9rem; color: var(--primary); }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 8px;
            height: 8px;
            background: var(--neon-green);
            border-radius: 50%;
            border: 2px solid var(--bg);
        }

        .username-info { display: flex; flex-direction: column; }
        .username-main { font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
        .user-role { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="inventory-container">
        <section class="header-section" style="margin-bottom: 30px;">
            <h4 style="color: var(--primary); margin: 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 800; font-size: 0.75rem;">System Audit</h4>
            <h1 style="margin: 10px 0; font-size: 2.8rem; font-weight: 800; letter-spacing: -1.5px;">User Activity Tracker</h1>
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
                <?php $delay = 0; while($log = $result->fetch_assoc()): ?>
                <tr style="animation-delay: <?= $delay ?>s">
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
                                <span class="user-role"><?= htmlspecialchars($log['role'] ?? 'System') ?></span>
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
                <?php $delay += 0.05; endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>