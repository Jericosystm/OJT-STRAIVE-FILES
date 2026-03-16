<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
    /* --- Your Original Navbar Styles --- */
    :root {
        --primary-orange: #FF6600;
        --deep-charcoal: #1A1A1B;
        --pure-white: #FFFFFF;
        --off-white: #F4F4F9;
        --border-light: #EEEEEE;
        --nav-height: 72px;
        --transition: all 0.25s ease;
    }

    .navbar {
        font-family: 'Inter', sans-serif;
        background: var(--deep-charcoal);
        height: var(--nav-height);
        padding: 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 3px solid var(--primary-orange);
    }

    .nav-left, .nav-right { display: flex; align-items: center; gap: 18px; }

    .logo {
        font-weight: 800; font-size: 1.6rem; color: var(--pure-white);
        letter-spacing: -1px; text-transform: uppercase;
    }

    .logo span {
        color: var(--primary-orange); background: rgba(255, 102, 0, 0.1);
        padding: 2px 6px; border-radius: 4px; margin-left: 2px;
    }

    /* --- Your Original Dropdown Styles --- */
    .user-menu { position: relative; cursor: pointer; }
    
    .icon-circle {
        width: 44px; height: 44px; background: var(--primary-orange);
        border-radius: 12px; display: flex; align-items: center;
        justify-content: center; color: var(--pure-white);
        transition: var(--transition); box-shadow: 0 4px 10px rgba(255, 102, 0, 0.3);
    }

    .dropdown-content {
        position: absolute; right: 0; top: 100%;
        background-color: white; min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px; z-index: 1000;
        margin-top: 8px; opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        transition-delay: 0.7s; 
    }

    .user-menu::after {
        content: ""; position: absolute; top: 100%; left: 0;
        width: 100%; height: 15px; display: none;
    }
    .user-menu:hover::after { display: block; }

    .user-menu:hover .dropdown-content {
        opacity: 1; visibility: visible; transform: translateY(0);
        transition-delay: 0s; 
    }

    .dropdown-content a {
        color: #333; padding: 12px 16px; text-decoration: none;
        display: block; font-size: 0.9rem; transition: background 0.2s;
    }

    .dropdown-content a:hover { background-color: #fff5eb; color: #ff6600; }
    
    .nav-user-info { color: white; margin-right: 15px; font-size: 0.9rem; font-weight: 500; }

    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <div class="logo">OJT<span>BOX</span></div>
        </div>
        
        <div class="nav-right">
            <span class="nav-user-info">Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong></span>
            
            <div class="user-menu">
                <div class="icon-circle">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="dropdown-content">
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> Account Settings</a>
                    <a href="logout.php" style="color: #d93025;"><i class="fa-solid fa-power-off"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

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

            <a href="baseline.php" class="card" id="btn-baseline">
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
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Returned</strong></p>
            </div>
        </div>

        <div id="prodmap-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Production Map</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Prod Map</strong></p>
            </div>
        </div>

        <div id="baseline-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Win BaseLine Dashboard</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Win BaseLine</strong></p>
            </div>
        </div>

        </main>

    <script src="backend.js"></script>
</body>
</html>