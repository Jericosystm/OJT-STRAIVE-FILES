<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'User';

/**
 * REVISION LOGIC: 
 * Chinecheck natin kung ang user ay Admin o Regular User para sa tamang redirect.
 * Kung walang manual na $back_link na sinet sa page, automatic itong babalik sa tamang Dashboard.
 */
if (!isset($back_link) || empty($back_link)) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'euc_admin') {
        $back_link = 'index_admin.php';
    } else {
        $back_link = 'index_user.php';
    }
}

// Para sa Dashboard (index_admin.php), i-set ang $back_link = "none" para maitago ang button
$show_back = ($back_link !== "none");
?>

<script>
    (function() {
        const savedTheme = localStorage.getItem('ojtbox_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        /* Default Dark Mode Variables */
        --primary-orange: #FF6600;
        --nav-bg: #1A1A1B;
        --text-color: #FFFFFF;
        --text-muted: #BBB;
        --btn-bg: rgba(255, 255, 255, 0.05);
        --btn-border: rgba(255, 255, 255, 0.2);
        --dropdown-bg: #FFFFFF;
        --dropdown-text: #1A1A1B;
        
        --nav-height: 72px;
        --transition: all 0.25s ease;
    }

    /* Light Mode Overrides */
    [data-theme="light"] {
        --nav-bg: #FFFFFF;
        --text-color: #1A1A1B;
        --text-muted: #666;
        --btn-bg: rgba(0, 0, 0, 0.05);
        --btn-border: rgba(0, 0, 0, 0.1);
        --dropdown-bg: #FFFFFF;
    }

    .navbar {
        font-family: 'Inter', sans-serif;
        background: var(--nav-bg);
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
        transition: var(--transition);
    }

    .nav-left, .nav-right {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .back-btn {
        background: var(--btn-bg);
        border: 1px solid var(--btn-border);
        color: var(--text-color);
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .back-btn:hover {
        background: var(--primary-orange);
        border-color: var(--primary-orange);
        color: white;
        transform: translateX(-3px);
    }

    .logo {
        font-weight: 800;
        font-size: 1.6rem;
        color: var(--text-color);
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
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
    }

    .nav-user-info strong {
        color: var(--text-color);
    }

    /* Theme Toggle Button Style */
    .theme-toggle-btn {
        background: var(--btn-bg);
        border: 1px solid var(--btn-border);
        color: var(--text-color);
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        transition: var(--transition);
    }

    .theme-toggle-btn:hover {
        border-color: var(--primary-orange);
        color: var(--primary-orange);
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
        color: white;
        transition: var(--transition);
        box-shadow: 0 4px 10px rgba(255, 102, 0, 0.3);
    }

    .user-menu:hover .icon-circle {
        transform: scale(1.05);
        background: #e65c00;
    }

    .dropdown-content {
        position: absolute;
        right: 0;
        top: calc(100% + 15px);
        background: var(--dropdown-bg);
        min-width: 200px;
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
        color: var(--dropdown-text);
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
        width: 18px;
        text-align: center;
        color: #666;
    }

    .dropdown-content a:hover {
        background-color: #F4F4F9;
        color: var(--primary-orange);
    }

    .dropdown-divider {
        height: 1px;
        background-color: #EEEEEE;
        margin: 4px 0;
    }
</style>

<nav class="navbar">
    <div class="nav-left">
        <?php if ($show_back): ?>
            <a href="<?php echo htmlspecialchars($back_link); ?>" class="back-btn">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        
        <div class="logo">OJT<span>BOX</span></div>
    </div>
    
    <div class="nav-right">
        <button class="theme-toggle-btn" onclick="toggleUniversalTheme()" id="themeBtn">
            <i class="fa-solid fa-moon"></i>
            <span id="themeLabel">Dark</span>
        </button>

        <span class="nav-user-info">Hello, Sir <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
        
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

<script>
    function updateThemeUI(theme) {
        const btn = document.getElementById('themeBtn');
        const label = document.getElementById('themeLabel');
        const icon = btn.querySelector('i');

        if (theme === 'light') {
            label.innerText = 'Light';
            icon.className = 'fa-solid fa-sun';
            btn.style.color = '#FF6600';
        } else {
            label.innerText = 'Dark';
            icon.className = 'fa-solid fa-moon';
            btn.style.color = '';
        }
    }

    function toggleUniversalTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('ojtbox_theme', newTheme);
        updateThemeUI(newTheme);
    }

    // Initialize UI on page load
    document.addEventListener('DOMContentLoaded', () => {
        const activeTheme = localStorage.getItem('ojtbox_theme') || 'dark';
        updateThemeUI(activeTheme);
    });
</script>