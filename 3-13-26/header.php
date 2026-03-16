<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Get the username from session, fallback to 'User' if not set
$username = $_SESSION['username'] ?? 'User'; 
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Production Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root { 
            --primary: #ff6b00; 
            --bg: #f1f5f9; 
            --card-bg: #ffffff; 
            --text-dark: #1e293b; 
            --border: #e2e8f0; 
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1); 
        }
        html, body { height: 100%; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); overflow: hidden; }
        
        /* Updated Navbar to handle user info */
        .navbar { 
            background: #ff9800; 
            padding: 0.5rem 2rem; 
            display: flex; 
            justify-content: space-between; /* Pushes content to sides */
            align-items: center; 
            height: 60px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            transition: opacity 0.2s;
        }
        
        .logout-btn:hover { opacity: 0.8; }

        .container { max-width: 1600px; margin: 0 auto; padding: 1rem 2rem; height: calc(100vh - 60px); display: flex; flex-direction: column; }
        /* Rest of your CSS... */
    </style>
</head>
<body>

<?php
// Get the current filename (e.g., 'inventory.php' or 'prod_map.php')
$current_page = basename($_SERVER['PHP_SELF']);

// Determine the title based on the page
if ($current_page == 'inventory.php') {
    $page_title = "OJT Inventory Page";
} elseif ($current_page == 'prod_map.php') {
    $page_title = "OJT Production Map";
} else {
    $page_title = "OJTBox";
}
?>

<nav class="navbar">
    <div style="display: flex; align-items: center; gap: 20px;">
        <a href="javascript:history.back()" style="color:#fff; text-decoration:none; font-size: 1.1rem; display: flex; align-items: center; gap: 5px; opacity: 0.8;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
            <i class="fa-solid fa-chevron-left"></i> 
            <span style="font-size: 0.9rem; font-weight: 600;">Back</span>
        </a>

        <a href="index.php" style="color:#fff; text-decoration:none; font-weight:800; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-box-archive"></i> 
            <span><?php echo $page_title; ?></span>
        </a>
    </div>

    <div class="user-profile">
        <span class="user-name">
            <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($username); ?>
        </span>
        <a href="logout.php" class="logout-btn" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</nav>

<div class="container">