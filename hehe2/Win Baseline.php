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
Win Baseline.php