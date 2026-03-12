<?php
session_start();

// Security Check: If 'user_id' isn't set, they aren't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Now we can safely get the username (make sure you set this in auth.php!)
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
/* 1. The Container */
    .user-menu { 
        position: relative; 
        display: inline-block; 
        cursor: pointer;
    }

    /* 2. The Dropdown Content */
    .dropdown-content {
        /* Remove display:none; it breaks transitions */
        position: absolute;
        right: 0;
        top: 100%; /* Position it at the bottom of the parent */
        background-color: white;
        min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px;
        z-index: 1000;
        
        /* Instead of margin-top, use transform for a small gap effect */
        margin-top: 8px;

        /* Hidden State */
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        
        /* The "Slow Down" effect (0.7s delay when mouse leaves) */
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        transition-delay: 0.7s; 
    }

    /* 3. The "Hover Bridge" Fix */
    /* This invisible area fills the gap so the menu doesn't close while moving the mouse down */
    .user-menu::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        height: 15px; /* Adjust this to match your gap */
        display: none;
    }
    .user-menu:hover::after { display: block; }

    /* 4. The Visible State */
    .user-menu:hover .dropdown-content {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        /* Reset delay to 0s so it appears instantly when hovering ON */
        transition-delay: 0s; 
    }

    /* 5. Links styling */
    .dropdown-content a {
        color: #333;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 0.9rem;
        transition: background 0.2s;
    }

    .dropdown-content a:first-child { border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .dropdown-content a:last-child { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }

    .dropdown-content a:hover { 
        background-color: #fff5eb; 
        color: #ff6600; 
    }
    
    .nav-user-info { color: white; margin-right: 15px; font-size: 0.9rem; font-weight: 500; }

    </style>
</head>
<body>

    <?php include 'header.php'; ?>

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

            <a href="user_management.php" class="card" id="btn-user-management">
                <div class="icon-wrapper"><i class="fa-solid fa-users-gear"></i></div>
                <p>User Management</p>
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

        <div id="hdn-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">HDN Dashboard</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>HDN</strong></p>
            </div>
        </div>

        <div id="inventory-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Inventory Management</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Inventory</strong></p>
            </div>
        </div>

        <div id="taskbox-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">Task Box Dashboard</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Task Box</strong></p>
            </div>
        </div>

        <div id="user-management-view" class="sub-view" style="display: none; padding: 20px;">
            <h2 style="color: #ff6600;">User Management</h2>
            <div class="view-content" style="border: 2px dashed #ccc; padding: 40px; border-radius: 12px; text-align: center; margin-top: 20px;">
                <p>Displaying data for: <strong>Users</strong></p>
            </div>
        </div>

    </main>

    <script src="backend.js"></script>
</body>
</html>