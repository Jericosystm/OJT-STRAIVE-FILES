<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'User';
$back_link = $back_link ?? 'index_user.php'; // Fallback if not set
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-orange: #FF6600;
        --nav-bg: #1A1A1B;
        --nav-text: #FFFFFF;
        --nav-subtext: #BBB;
        --dropdown-bg: #FFFFFF;
        --dropdown-text: #1A1A1B;
        --border-color: rgba(255, 255, 255, 0.1);
        --nav-height: 72px;
        --transition: all 0.25s ease;
    }

    /* Light Mode Overrides for Header */
    body.light-mode {
        --nav-bg: #FFFFFF;
        --nav-text: #1A1A1B;
        --nav-subtext: #666;
        --dropdown-bg: #FFFFFF;
        --dropdown-text: #1A1A1B;
        --border-color: rgba(0, 0, 0, 0.1);
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
        transition: background 0.4s ease;
    }

    .nav-left, .nav-right {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .back-btn {
        background: rgba(128, 128, 128, 0.1);
        border: 1px solid var(--border-color);
        color: var(--nav-text);
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
        color: white;
        transform: translateX(-3px);
    }

    .logo {
        font-weight: 800;
        font-size: 1.6rem;
        color: var(--nav-text);
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

    /* Theme Toggle Button Style */
    .theme-toggle-btn {
        background: rgba(128, 128, 128, 0.1);
        border: 1px solid var(--border-color);
        color: var(--nav-text);
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: var(--transition);
    }

    .theme-toggle-btn:hover {
        background: rgba(128, 128, 128, 0.2);
        border-color: var(--primary-orange);
    }

    .nav-user-info {
        color: var(--nav-subtext);
        font-size: 0.85rem;
        font-weight: 500;
    }

    .nav-user-info strong {
        color: var(--nav-text);
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
        color: #FFFFFF;
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
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        border: 1px solid var(--border-color);
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
    }

    .dropdown-content a:hover {
        background-color: #f8f9fa;
        color: var(--primary-orange);
    }

    .dropdown-divider {
        height: 1px;
        background-color: var(--border-color);
        margin: 4px 0;
    }
</style>

<nav class="navbar">
    <div class="nav-left">
        <a href="<?php echo $back_link; ?>" class="back-btn" style="text-decoration: none;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div class="logo">OJT<span>BOX</span></div>
    </div>
    
    <div class="nav-right">
        <button class="theme-toggle-btn" id="header-theme-toggle" title="Toggle Light/Dark Mode">
            <i class="fa-solid fa-moon"></i>
        </button>

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

<script>
    // Theme logic in header so it works on all pages
    const themeBtn = document.getElementById('header-theme-toggle');
    const themeIcon = themeBtn.querySelector('i');
    const bodyEl = document.body;

    // Apply theme on load
    if (localStorage.getItem('theme') === 'light') {
        bodyEl.classList.add('light-mode');
        themeIcon.classList.replace('fa-moon', 'fa-sun');
    }

    themeBtn.addEventListener('click', () => {
        bodyEl.classList.toggle('light-mode');
        const isLight = bodyEl.classList.contains('light-mode');
        
        localStorage.setItem('theme', isLight ? 'light' : 'dark');
        themeIcon.classList.replace(isLight ? 'fa-moon' : 'fa-sun', isLight ? 'fa-sun' : 'fa-moon');
    });
</script>