<?php
session_start();
require 'db.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Authentication check
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Authorization check (basic example)
function requireAdmin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('HTTP/1.0 403 Forbidden');
        echo "Access Denied";
        exit;
    }
}
?>