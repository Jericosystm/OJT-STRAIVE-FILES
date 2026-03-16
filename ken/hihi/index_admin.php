<?php
session_start();

// 1. Kung hindi naka-login, balik sa login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/** * 2. ROLE SECURITY CHECK
 * Base sa database mo, 'admin' ang value.
 * Kapag HINDI admin, itatapon natin sa index.php (normal user dashboard).
 * Ito ang pampigil sa "Too many redirects" error.
 */
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php"); 
    exit();
}

// 3. Kunin ang username para sa display
$username = $_SESSION['username'] ?? 'Administrator'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');

    :root {
        --primary: #ff6600;
        --primary-glow: rgba(255, 102, 0, 0.3);
        --bg: #030303;
        --card-bg: rgba(15, 15, 15, 0.7);
        --border: rgba(255, 255, 255, 0.05);
        --text-muted: rgba(255, 255, 255, 0.86);
    }

    body {
        background-color: var(--bg);
        color: white;
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        background-image: 
            radial-gradient(circle at 10% 10%, rgba(255, 102, 0, 0.08), transparent 40%),
            radial-gradient(circle at 90% 90%, rgba(255, 102, 0, 0.05), transparent 40%);
        overflow-x: hidden;
        min-height: 100vh;
    }

    /* --- PREMIUM HEADER SECTION --- */
    .dashboard-header {
        padding: 60px 40px 30px;
        max-width: 1300px;
        margin: 0 auto;
    }

    .welcome-banner {
        position: relative;
        animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .welcome-banner p {
        color: var(--primary);
        font-size: 0.85rem;
        margin: 0 0 8px 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 3px;
    }

    .welcome-banner h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -1px;
        display: flex;
        align-items: center;
        min-height: 3.5rem; /* Prevents layout jump while typing */
    }

    /* --- TEXT TYPE CSS EFFECTS --- */
    .text-type__content {
        background: linear-gradient(to right, #fff, #888);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        white-space: pre-wrap;
    }

    .text-type__cursor {
        margin-left: 0.25rem;
        display: inline-block;
        color: var(--primary);
        -webkit-text-fill-color: var(--primary); /* Override the h1 gradient for cursor */
        animation: blink 0.8s infinite;
    }

    @keyframes blink { 50% { opacity: 0; } }

    /* --- BENTO GRID LAYOUT --- */
    .dashboard-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-auto-rows: 220px;
        gap: 24px;
        padding: 20px 40px 80px;
        max-width: 1300px;
        margin: 0 auto;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 32px; 
        padding: 30px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        display: flex;
        flex-direction: column;
        justify-content: flex-end; 
        align-items: flex-start;
        backdrop-filter: blur(25px);
    }

    #btn-machine-movement { grid-column: span 2; } 
    #btn-inventory { grid-column: span 2; }         
    #btn-baseline { grid-row: span 2; }             

    .card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(800px circle at var(--mouse-x, 0) var(--mouse-y, 0), rgba(255, 102, 0, 0.06), transparent 40%);
        opacity: 0;
        transition: opacity 0.5s;
    }

    .card:hover {
        transform: translateY(-10px) scale(1.02);
        border-color: rgba(255, 102, 0, 0.3);
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
    }

    .card:hover::before { opacity: 1; }

    .icon-wrapper {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: auto; 
        border: 1px solid var(--border);
        transition: 0.4s;
    }

    .card:hover .icon-wrapper {
        background: var(--primary);
        color: white;
        box-shadow: 0 0 20px var(--primary-glow);
        transform: rotate(-10deg);
    }

    .icon-wrapper i { font-size: 1.4rem; color: #fff; transition: 0.3s; }

    .card p {
        color: #fff;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: -0.3px;
        z-index: 2;
    }

    .card::after {
        content: "\f061";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        right: 30px; bottom: 30px;
        font-size: 1rem;
        color: var(--text-muted);
        opacity: 0;
        transform: translateX(-10px);
        transition: 0.3s;
    }

    .card:hover::after {
        opacity: 1;
        transform: translateX(0);
        color: var(--primary);
    }

    /* --- DROPDOWN REFINED --- */
    .user-menu { position: relative; }
    .dropdown-content {
        position: absolute; right: 0; top: 120%; 
        background: rgba(10, 10, 10, 0.95);
        backdrop-filter: blur(15px);
        min-width: 180px;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: 0.3s;
    }
    .user-menu:hover .dropdown-content { opacity: 1; visibility: visible; transform: translateY(0); }
    .dropdown-content a { 
        color: #fff; padding: 12px 20px; text-decoration: none; display: block; 
        font-size: 0.85rem; transition: 0.2s;
    }
    .dropdown-content a:hover { background: var(--primary); }

    /* --- SUB-VIEW PROFESSIONAL --- */
    .sub-view {
        max-width: 1300px;
        margin: 0 auto 60px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 32px;
        padding: 40px !important;
    }

    @keyframes slideIn { 
        from { opacity: 0; transform: translateY(20px); } 
        to { opacity: 1; transform: translateY(0); } 
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: #222; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <header class="dashboard-header">
        <div class="welcome-banner">
            <p>System Administrator</p>
            <h1>
                <span id="typewriter-text" class="text-type__content"></span>
                <span class="text-type__cursor">_</span>
            </h1>
        </div>
    </header>

    <main class="dashboard-wrapper">
        <div id="main-grid" class="dashboard-container">
            
            <a href="machine_movement.php" class="card" id="btn-machine-movement">
                <div class="icon-wrapper"><i class="fa-solid fa-database"></i></div>
                <p>Machine Movement</p>
            </a>

            <a href="prod_map.php" class="card" id="btn-prodmap">
                <div class="icon-wrapper"><i class="fa-solid fa-layer-group"></i></div>
                <p>Production Map</p>
            </a>

            <a href="baseline.php" class="card" id="btn-baseline">
                <div class="icon-wrapper"><i class="fa-solid fa-folder-tree"></i></div>
                <p>Win BaseLine</p>
            </a>

            <a href="hdn.php" class="card" id="btn-hdn">
                <div class="icon-wrapper"><i class="fa-solid fa-server"></i></div>
                <p>HDN Node</p>
            </a>

            <a href="inventory.php" class="card" id="btn-inventory">
                <div class="icon-wrapper"><i class="fa-solid fa-boxes-stacked"></i></div>
                <p>Inventory System</p>
            </a>

            <a href="taskbox.php" class="card" id="btn-taskbox">
                <div class="icon-wrapper"><i class="fa-solid fa-clipboard-list"></i></div>
                <p>Task Box</p>
            </a>

            <a href="user_management.php" class="card" id="btn-user-management">
                <div class="icon-wrapper"><i class="fa-solid fa-users-gear"></i></div>
                <p>User Management</p>
            </a>

        </div>

        <div id="machine-movement-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary); margin-top:0;">Machine Movement Intelligence</h2>
            <div class="view-content" style="border: 1px solid var(--border); padding: 40px; border-radius: 20px; text-align: center; background: rgba(0,0,0,0.2);">
                <p style="color: var(--text-muted);">Displaying data for: <strong style="color: #fff;">Returned Assets</strong></p>
            </div>
        </div>

        <div id="prodmap-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary); margin-top:0;">Production Map</h2>
            <div class="view-content" style="border: 1px solid var(--border); padding: 40px; border-radius: 20px; text-align: center; background: rgba(0,0,0,0.2);">
                <p style="color: var(--text-muted);">Displaying data for: <strong style="color: #fff;">Prod Map</strong></p>
            </div>
        </div>

        <div id="baseline-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary);">Win BaseLine Dashboard</h2>
        </div>
        <div id="hdn-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary);">HDN Dashboard</h2>
        </div>
        <div id="inventory-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary);">Inventory Management</h2>
        </div>
        <div id="taskbox-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary);">Task Box Dashboard</h2>
        </div>
        <div id="user-management-view" class="sub-view" style="display: none;">
            <h2 style="color: var(--primary);">User Management Control</h2>
        </div>

    </main>

    <script>
        // --- TEXT TYPEWRITER LOGIC (FIXED) ---
        const textArray = [
            "Welcome, <?php echo htmlspecialchars($username); ?>!",
            "Accessing Admin Portal...",
            "Manage System Assets."
        ];
        
        const typingSpeed = 75;
        const pauseDuration = 2000;
        const deletingSpeed = 40;
        
        let textIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const target = document.getElementById('typewriter-text');

        function handleType() {
            // Priority: Get current full text before checking length
            const currentFullText = textArray[textIndex]; 
            
            if (isDeleting) {
                target.textContent = currentFullText.substring(0, charIndex - 1);
                charIndex--;
            } else {
                target.textContent = currentFullText.substring(0, charIndex + 1);
                charIndex++;
            }

            let typeTimeout = isDeleting ? deletingSpeed : typingSpeed;

            if (!isDeleting && charIndex === currentFullText.length) {
                // Done typing
                isDeleting = true;
                typeTimeout = pauseDuration;
            } else if (isDeleting && charIndex === 0) {
                // Done deleting
                isDeleting = false;
                textIndex = (textIndex + 1) % textArray.length;
                typeTimeout = 500;
            }
            
            setTimeout(handleType, typeTimeout);
        }

        // --- MOUSE FOLLOW EFFECT ---
        document.querySelectorAll('.card').forEach(card => {
            card.onmousemove = e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty("--mouse-x", `${x}px`);
                card.style.setProperty("--mouse-y", `${y}px`);
            };
        });

        // Initialize typewriter
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(handleType, 1000);
        });
    </script>
    <script src="backend.js"></script>
</body>
</html>