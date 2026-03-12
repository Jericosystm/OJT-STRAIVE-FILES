<?php
include 'db.php';

$password_to_test = 'P@ssword2026';
$username_to_check = 'euc_admin';

$sql = "SELECT password FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username_to_check);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $hash_in_db = $user['password'];
    echo "Testing User: " . $username_to_check . "<br>";
    echo "Hash found in DB: " . $hash_in_db . "<br>";
    
    if (password_verify($password_to_test, $hash_in_db)) {
        echo "<h2 style='color:green'>MATCH SUCCESS!</h2>";
        echo "The PHP logic works. The issue is in your login.php form or session redirects.";
    } else {
        echo "<h2 style='color:red'>MATCH FAILED!</h2>";
        echo "PHP cannot match this password to that hash. The hash in the DB is likely corrupted or has hidden characters.";
    }
} else {
    echo "User not found in database.";
}
?>