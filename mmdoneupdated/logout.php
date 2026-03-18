<?php
// 1. Start the session so PHP knows which session to close
session_start();

// 2. Unset all session variables (clears $_SESSION['user_id'], etc.)
$_SESSION = array();

// 3. If it's desired to kill the session, also delete the session cookie.
// This is an extra security step to ensure the browser clears the session ID.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session on the server
session_destroy();

// 5. Redirect the user back to the login page
header("Location: login.php");
exit();
?>