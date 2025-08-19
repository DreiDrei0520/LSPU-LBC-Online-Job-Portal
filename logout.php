<?php
session_start();

// Include database connection if needed for token cleanup
require_once('db_connection.php');

// Delete remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    // Clear the remember me cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Close database connection if opened
if (isset($conn)) {
    $conn->close();
}

// Redirect to login page with a cache-busting parameter
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: login.php?logout=1&t=" . time());
exit();
?>