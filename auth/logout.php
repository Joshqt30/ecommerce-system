<?php
session_start();

// completely clear session array
$_SESSION = [];

// destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// destroy session
session_destroy();

// IMPORTANT: stop execution
exit(header("Location: login.php?logout=success"));