<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'User';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
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

    .nav-left, .nav-right {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .back-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--pure-white);
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
    }

    .back-btn:hover {
        background: var(--primary-orange);
        border-color: var(--primary-orange);
        transform: translateX(-3px);
    }

    .logo {
        font-weight: 800;
        font-size: 1.6rem;
        color: var(--pure-white);
        letter-spacing: -1px;
        text-transform: uppercase;
    }

    .logo span {
        color: var(--primary-orange);
        background: rgba(255, 102, 0, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 2px;
    }

    .nav-user-info {
        color: #BBB;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .nav-user-info strong {
        color: var(--pure-white);
    }

    .user-menu {
        position: relative;
    }

    .icon-circle {
        width: 44px;
        height: 44px;
        background: var(--primary-orange);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pure-white);
        transition: var(--transition);
        box-shadow: 0 4px 10px rgba(255, 102, 0, 0.3);
    }

    .user-menu:hover .icon-circle {
        transform: scale(1.05);
        background: #e65c00;
    }

    /* Dropdown Menu */
    .dropdown-content {
        position: absolute;
        right: 0;
        top: calc(100% + 15px);
        background: var(--pure-white);
        min-width: 200px; /* Slightly wider for longer text */
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        border: 1px solid #DDD;
        overflow: hidden;
    }

    .user-menu:hover .dropdown-content {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-content a {
        color: #1A1A1B;
        padding: 14px 20px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .dropdown-content a i {
        width: 18px; /* Fixed width for icon alignment */
        text-align: center;
        color: #666;
    }

    .dropdown-content a:hover {
        background-color: var(--off-white);
        color: var(--primary-orange);
    }

    .dropdown-content a:hover i {
        color: var(--primary-orange);
    }

    /* Visual Divider */
    .dropdown-divider {
        height: 1px;
        background-color: var(--border-light);
        margin: 4px 0;
    }

    .user-menu::after {
        content: "";
        position: absolute;
        top: 100%;
        right: 0;
        width: 100%;
        height: 20px;
    }
</style>

<nav class="navbar">
    <div class="nav-left">
        <button class="back-btn" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="logo">OJT<span>BOX</span></div>
    </div>
    
    <div class="nav-right">
        <span class="nav-user-info">Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong></span>
        
        <div class="user-menu">
            <div class="icon-circle">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="dropdown-content">
                <a href="settings.php">
                    <i class="fa-solid fa-gear"></i> 
                    Account Settings
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a href="logout.php" style="color: #d93025;">
                    <i class="fa-solid fa-power-off"></i> 
                    Sign Out
                </a>
            </div>
        </div>
    </div>
    
</nav>