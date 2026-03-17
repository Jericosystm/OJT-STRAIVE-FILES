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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* --- WORLD CLASS UI THEME --- */
    :root {
        --primary: #ff6600;
        --bg: #050505;
        --card-bg: rgba(20, 20, 20, 0.6);
        --border: rgba(255, 255, 255, 0.08);
        --text: #ffffff;
        --header-text: #ffffff;
        --sub-text: rgba(255, 255, 255, 0.6);
        --glow: rgba(255, 102, 0, 0.15);
    }

    /* --- LIGHT MODE OVERRIDES --- */
    body.light-mode {
        --bg: #f5f7fa;
        --card-bg: rgba(255, 255, 255, 0.9);
        --border: rgba(0, 0, 0, 0.1);
        --text: #1a1a1a;
        --header-text: #111111;
        --sub-text: #444444;
        --glow: rgba(255, 102, 0, 0.05);
    }

    body {
        background-color: var(--bg);
        color: var(--text);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        margin: 0;
        background-image: radial-gradient(circle at 50% -20%, var(--glow), transparent 50%);
        transition: background-color 0.4s ease, color 0.4s ease;
    }

    /* --- THEME TOGGLE BUTTON --- */
    .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--card-bg);
        border: 1px solid var(--border);
        color: var(--text);
        padding: 10px 15px;
        border-radius: 50px;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .theme-toggle:hover {
        transform: scale(1.05);
        border-color: var(--primary);
    }

    /* --- REVISED PREMIUM HEADER --- */
    .dashboard-header {
        padding: 60px 40px 30px;
        max-width: 1400px;
        margin: 0 auto;
        position: relative;
    }

    .welcome-banner {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0) 100%);
        backdrop-filter: blur(10px);
        border-left: 3px solid var(--primary);
        padding: 20px 25px;
        border-radius: 0 16px 16px 0;
        display: inline-block;
        animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    body.light-mode .welcome-banner {
        background: rgba(0, 0, 0, 0.02);
    }

    .dashboard-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
        color: var(--header-text);
        display: flex;
        align-items: center;
    }

    .welcome-banner p { color: var(--sub-text); margin-bottom: 5px; }

    /* --- TEXT TYPE CSS --- */
    .text-type__content { display: inline-block; white-space: pre-wrap; }
    .text-type__cursor {
        margin-left: 0.25rem;
        display: inline-block;
        color: var(--primary);
        font-weight: 400;
        animation: blink 0.8s infinite;
    }

    @keyframes blink { 50% { opacity: 0; } }

    /* --- BENTO GRID LAYOUT --- */
    .dashboard-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        grid-auto-rows: 200px; 
        gap: 30px; 
        padding: 0 40px 60px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 28px; 
        padding: 35px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.2, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: flex-end; 
        align-items: flex-start; 
        backdrop-filter: blur(20px);
    }

    #btn-machine-movement { grid-column: span 2; } 
    #btn-inventory { grid-column: span 2; }        
    #btn-baseline { grid-row: span 2; }            

    .card:hover {
        transform: translateY(-10px);
        background: var(--card-bg);
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        border-color: var(--primary);
    }

    .icon-wrapper {
        width: 55px;
        height: 55px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: auto; 
        border: 1px solid var(--border);
        transition: 0.4s ease;
    }

    #btn-machine-movement .icon-wrapper { background: rgba(0, 150, 255, 0.1); color: #0096ff; }
    #btn-prodmap .icon-wrapper { background: rgba(150, 0, 255, 0.1); color: #9600ff; }
    #btn-baseline .icon-wrapper { background: rgba(255, 200, 0, 0.1); color: #ffc800; }
    #btn-hdn .icon-wrapper { background: rgba(255, 50, 50, 0.1); color: #ff3232; }
    #btn-inventory .icon-wrapper { background: rgba(0, 255, 150, 0.1); color: #00ff96; }
    #btn-taskbox .icon-wrapper { background: rgba(255, 102, 0, 0.1); color: #ff6600; }

    .card:hover .icon-wrapper { transform: scale(1.1) rotate(-5deg); background: currentColor; }
    .icon-wrapper i { font-size: 1.4rem; transition: 0.3s; color: inherit; }
    .card:hover .icon-wrapper i { color: white; }

    .card p {
        color: var(--text);
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: 0.3px;
    }

    .sub-view {
        max-width: 1400px;
        margin: 0 auto 60px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 24px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes slideIn { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    </style>
</head>
<body>

    <button class="theme-toggle" id="theme-toggle">
        <i class="fa-solid fa-moon"></i>
        <span id="theme-text">Dark Mode</span>
    </button>

    <?php include 'header.php'; ?>

    <header class="dashboard-header">
        <div class="welcome-banner">
            <p>System Overview & Asset Intelligence</p>
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
                <p>Prod Map</p>
            </a>
            <a href="win_baseline.php" class="card" id="btn-baseline">
                <div class="icon-wrapper"><i class="fa-solid fa-folder-tree"></i></div>
                <p>Win BaseLine</p>
            </a>
            <a href="hdn.php" class="card" id="btn-hdn">
                <div class="icon-wrapper"><i class="fa-solid fa-server"></i></div>
                <p>HDN</p>
            </a>
            <a href="inventory.php" class="card" id="btn-inventory">
                <div class="icon-wrapper"><i class="fa-solid fa-boxes-stacked"></i></div>
                <p>Inventory</p>
            </a>
            <a href="taskbox.php" class="card" id="btn-taskbox">
                <div class="icon-wrapper"><i class="fa-solid fa-clipboard-list"></i></div>
                <p>Task Box</p>
            </a>
        </div>

        <div id="machine movement-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Machine Movement Items Dashboard</h2>
            <div class="view-content" style="border: 1px dashed var(--border); padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Returned</strong></p>
            </div>
        </div>
    </main>

    <script>
        // --- THEME TOGGLE LOGIC ---
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = themeToggle.querySelector('i');
        const themeText = document.getElementById('theme-text');
        const body = document.body;

        // Check for saved user preference
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'light') {
            body.classList.add('light-mode');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
            themeText.textContent = 'Light Mode';
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light-mode');
            
            let theme = 'dark';
            if (body.classList.contains('light-mode')) {
                theme = 'light';
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                themeText.textContent = 'Dark Mode';
            }
            localStorage.setItem('theme', theme);
        });

        // --- VANILLA JS TEXT TYPE EFFECT ---
        const textToType = ["Welcome back, <?php echo htmlspecialchars($username); ?>.", "System is ready.", "Manage your assets."];
        const typingSpeed = 75;
        const pauseDuration = 1500;
        const deletingSpeed = 50;
        
        let textIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const targetElement = document.getElementById('typewriter-text');

        function type() {
            const currentFullText = textToType[textIndex];
            
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
                textIndex = (textIndex + 1) % textToType.length;
                nextSpeed = 500;
            }

            setTimeout(type, nextSpeed);
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(type, 500);
        });
    </script>
    <script src="backend.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>