<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJTBox | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .logo { font-size: 2.2rem; font-weight: 800; color: #ff6600; margin-bottom: 5px; }
        .logo i { margin-right: 10px; }
        .subtitle { color: #666; font-size: 0.9rem; margin-bottom: 30px; display: block; }
        .error-msg { background: #ffe5e5; color: #dc3545; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #fabdbd; display: flex; align-items: center; gap: 10px; text-align: left; }
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; transition: 0.3s; z-index: 2; }
        .input-group input { width: 100%; padding: 14px 15px 14px 45px; border: 1px solid #ddd; border-radius: 10px; outline: none; box-sizing: border-box; font-size: 0.95rem; transition: 0.3s; }
        .input-group input:focus { border-color: #ff6600; box-shadow: 0 0 10px rgba(255,102,0,0.1); }
        .input-group input:focus + i { color: #ff6600; }
        .login-btn { width: 100%; padding: 14px; background: #ff6600; color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.3s, transform 0.1s; margin-top: 10px; }
        .login-btn:hover { background: #e65c00; }
        .login-btn:active { transform: scale(0.98); }
        .footer-note { margin-top: 25px; font-size: 0.8rem; color: #aaa; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><i class="fa-solid fa-box-open"></i>OJTBox</div>
        <span class="subtitle">Asset Management System</span>

        <?php if (isset($_GET['error'])): ?>
            <div class="er  ror-msg">
                <i class="fa-solid fa-circle-exclamation"></i> 
                <span>Invalid username or password.</span>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username or Email" required autocomplete="username">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <i class="fa-solid fa-lock"></i>
            </div>
            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div class="footer-note">
            &copy; <?php echo date("Y"); ?> OJTBox System. All rights reserved.
        </div>
    </div>
</body>
</html>