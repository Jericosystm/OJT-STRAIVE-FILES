<?php
session_start();

// Security Check: If 'user_id' isn't set, they aren't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now we can safely get the username
$username = $_SESSION['username'] ?? 'User'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=JetBrains+Mono:wght@500&family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
     <style>
    :root {
        /* Default Dark Mode */
        --primary: #ff6600;
        --primary-glow: rgba(255, 102, 0, 0.4);
        --bg: #050505;
        --card-bg: rgba(255, 255, 255, 0.04);
        --card-hover: rgba(255, 255, 255, 0.07);
        --border: rgba(255, 255, 255, 0.1);
        --text-main: #ffffff;
        --text-muted: rgba(255, 255, 255, 0.5);
        --card-shadow: rgba(0, 0, 0, 0.5);
    }

    /* Light Mode Implementation */
    [data-theme="light"] {
        --bg: #f5f5f7;
        --card-bg: #ffffff;
        --card-hover: #eeeeee;
        --border: rgba(0, 0, 0, 0.08);
        --text-main: #1d1d1f;
        --text-muted: #6e6e73;
        --card-shadow: rgba(0, 0, 0, 0.1);
    }

    body {
        background-color: var(--bg);
        color: var(--text-main);
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        background-image: 
            radial-gradient(circle at 0% 0%, rgba(255, 102, 0, 0.1), transparent 35%),
            radial-gradient(circle at 100% 100%, rgba(255, 102, 0, 0.05), transparent 35%);
        background-attachment: fixed;
        overflow-x: hidden;
        /* PREVENT SCROLL */
        height: 100vh;
        overflow-y: hidden;
        transition: background-color 0.4s ease, color 0.4s ease;
    }

    .dashboard-header {
        /* Reduced padding for tighter layout */
        padding: 50px 40px 0px;
        max-width: 1300px;
        margin: 0 auto;
    }

    .welcome-banner {
        position: relative;
        animation: fadeInSlide 1s ease-out;
    }

    .welcome-banner p {
        color: var(--primary);
        font-size: 0.7rem;
        margin: 0 0 5px 0;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 5px;
        opacity: 0.8;
    }

    .welcome-banner h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -2px;
        line-height: 1;
        color: var(--text-main);
    }

    @keyframes fadeInSlide {
        from { opacity: 0; transform: translateY(-20px); filter: blur(10px); }
        to { opacity: 1; transform: translateY(0); filter: blur(0); }
    }

    .text-type__content { color: var(--text-main); }
    .text-type__cursor {
        color: var(--primary);
        animation: blink 0.8s infinite;
        font-weight: 300;
    }

    @keyframes blink { 50% { opacity: 0; } }

    /* BENTO GRID REFINED */
    .dashboard-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        /* Tightened row height for compact look */
        grid-auto-rows: 155px;
        gap: 15px;
        padding: 20px 40px 40px;
        max-width: 1300px;
        margin: 0 auto;
        grid-auto-flow: dense;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 22px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 10px 30px var(--card-shadow);
    }

    /* PREMIUM FLOATING ANIMATION */
    .card { animation: float 6s ease-in-out infinite; }
    .card:nth-child(2n) { animation-delay: 1s; }
    .card:nth-child(3n) { animation-delay: 2s; }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-6px); }
    }

    /* SURGICAL REVISION: INVENTORY TOP, PRODMAP BOTTOM, TIGHTER SPAN */
    #btn-inventory { grid-column: span 2; grid-row: span 2; } 
    #btn-prodmap { grid-column: span 2; grid-row: span 2; } 
    #btn-machine-movement, #btn-baseline, #btn-hdn, #btn-taskbox, #btn-user-management { grid-column: span 1; grid-row: span 1; }
#btn-activity-tracker { 
    grid-column: span 1; 
    grid-row: span 1; 
}
    .card:hover {
        animation-play-state: paused;
        background: var(--card-hover);
        border-color: var(--primary);
        transform: scale(1.015) translateY(-3px) !important;
        box-shadow: 0 20px 40px -10px var(--card-shadow), 0 0 15px var(--primary-glow);
    }

    .icon-wrapper {
        width: 45px;
        height: 45px;
        background: rgba(120, 120, 120, 0.1);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: auto;
        border: 1px solid var(--border);
        transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .card:hover .icon-wrapper {
        background: var(--primary);
        transform: rotate(-8deg) scale(1.05);
    }

    .icon-wrapper i { font-size: 1.3rem; color: var(--text-main); transition: color 0.3s; }
    .card:hover .icon-wrapper i { color: #fff; }

    .card p {
        color: var(--text-main);
        font-size: 1.1rem;
        font-weight: 700;
        margin: 12px 0 2px 0;
        letter-spacing: -0.4px;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #00ff88;
        border-radius: 50%;
        display: inline-block;
        margin-right: 10px;
        box-shadow: 0 0 12px rgba(0, 255, 136, 0.5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 255, 136, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0); }
    }

    .data-text {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.65rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(600px circle at var(--mouse-x) var(--mouse-y), rgba(255, 102, 0, 0.04), transparent 40%);
        z-index: 1;
        opacity: 0;
        transition: opacity 0.5s;
    }
    .card:hover::before { opacity: 1; }

    ::-webkit-scrollbar { width: 0; }

    /* --- ADDED: GLASS REFLECTION & FROSTING OVERRIDE --- */
    .card {
        backdrop-filter: blur(25px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    .card::after {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            45deg,
            transparent 45%,
            rgba(255, 255, 255, 0.05) 50%,
            transparent 55%
        );
        transform: rotate(25deg);
        transition: all 0.7s ease;
        pointer-events: none;
        z-index: 5;
    }

    .card:hover::after {
        top: 40%;
        left: 40%;
    }
    /* -------------------------------------------------- */
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <header class="dashboard-header">
        <div class="welcome-banner">
            <p>System Overview & Asset Intelligence</p>
            <h1>
                <span id="typewriter-text" class="text-type__content"></span>
                <span class="text-type__cursor">|</span>
            </h1>
        </div>
    </header>

    <main class="dashboard-wrapper">
        <div id="main-grid" class="dashboard-container">
            
            <a href="inventory.php" class="card" id="btn-inventory">
                <div class="icon-wrapper"><i class="fa-solid fa-boxes-stacked"></i></div>
                <p>Inventory System</p>
                <span class="data-text">Asset Management</span>
            </a>

            <a href="machine_movement.php" class="card" id="btn-machine-movement">
                <div class="icon-wrapper"><i class="fa-solid fa-database"></i></div>
                <div>
                    <p>Machine Movement</p>
                    <span class="data-text">Live Asset Movement</span>
                </div>
            </a>

            <a href="baseline.php" class="card" id="btn-baseline">
                <div class="icon-wrapper"><i class="fa-solid fa-folder-tree"></i></div>
                <p>Win BaseLine</p>
                <span class="data-text">Checklist</span>
            </a>

            <a href="hdn.php" class="card" id="btn-hdn">
                <div class="icon-wrapper"><i class="fa-solid fa-server"></i></div>
                <p><span class="pulse-dot"></span>HDN</p>
                <span class="data-text">PDF Files</span>
            </a>

            <a href="taskbox.php" class="card" id="btn-taskbox">
                <div class="icon-wrapper"><i class="fa-solid fa-clipboard-list"></i></div>
                <p>Task Box</p>
                <span class="data-text">EUC Task List</span>
            </a>

            <a href="prod_map.php" class="card" id="btn-prodmap">
                <div class="icon-wrapper"><i class="fa-solid fa-layer-group"></i></div>
                <p>Production Map</p>
                <span class="data-text">Cubicle Visualization</span>
            </a>
             <a href="tech_scheduler.php" class="card" id="btn-scheduler">
    <div class="icon-wrapper"><i class="fa-solid fa-calendar-days"></i></div>
    <p>Tech Scheduler</p>
    <span class="data-text">Tech Support Schedules</span>
</a>

        </div>

        <div id="machine-movement-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Machine Movement Items Dashboard</h2>
            <div class="view-content" style="border: 1px dashed #333; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Returned</strong></p>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script>
        // Mouse glow effect
        document.querySelectorAll(".card").forEach(card => {
            card.onmousemove = e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty("--mouse-x", `${x}px`);
                card.style.setProperty("--mouse-y", `${y}px`);
            };
        });

        // Initialize Tilt Effect
        VanillaTilt.init(document.querySelectorAll(".card"), {
            max: 4,
            speed: 400,
            glare: true,
            "max-glare": 0.1,
            perspective: 1500,
        });

        // Typewriter Effect Logic
        const textArray = [
            "Welcome back, <?php echo htmlspecialchars($username); ?>.",
            "System is ready.",
            "Manage your assets.",
            "All nodes active."
        ];
        
        const typingSpeed = 60;
        const pauseDuration = 2500;
        const deletingSpeed = 30;
        
        let textIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const targetElement = document.getElementById('typewriter-text');

        function type() {
            const currentFullText = textArray[textIndex];
            
            if (isDeleting) {
                targetElement.textContent = currentFullText.substring(0, charIndex - 1);
                charIndex--;
            } else {
                targetElement.textContent = currentFullText.substring(0, charIndex + 1);
                charIndex++;
            }

            let nextSpeed = isDeleting ? deletingSpeed : typingSpeed;

            if (!isDeleting && charIndex === currentFullText.length) {
                isDeleting = true;
                nextSpeed = pauseDuration;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                textIndex = (textIndex + 1) % textArray.length;
                nextSpeed = 500;
            }

            setTimeout(type, nextSpeed);
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(type, 1000);
        });
    </script>
    <script src="backend.js"></script>
</body>
</html>