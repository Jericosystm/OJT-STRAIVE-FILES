<?php
include 'db.php';

// The password we want both accounts to use
$password = 'P@ssword2026';

// Generate a fresh, clean hash using PHP's internal engine
$new_hash = password_hash($password, PASSWORD_DEFAULT);

// Update BOTH users at the same time
$sql = "UPDATE users SET password = ? WHERE username IN ('euc_admin', 'euc_user')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_hash);

if ($stmt->execute()) {
    echo "<h2 style='color:green'>Success! Both accounts have been repaired.</h2>";
    echo "<b>New Hash saved to Database:</b> <code>$new_hash</code><br><br>";
    echo "1. <a href='test_match.php'>Run Test Match Again</a> (Should be Green now)<br>";
    echo "2. <a href='login.php'>Go to Login Page</a>";
} else {
    echo "Database Error: " . $conn->error;
}
?>