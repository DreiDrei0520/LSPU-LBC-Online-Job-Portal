<?php
// Functions to handle security-related operations

/**
 * Check if the current request is coming from the same domain
 */
function isSameDomain() {
    if (!isset($_SERVER['HTTP_REFERER'])) {
        return false;
    }
    
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $server = parse_url($_SERVER['HTTP_HOST']);
    
    return ($referer['host'] === $server['host']);
}

/**
 * Validate session integrity
 */
function validateSession() {
    if (!isset($_SESSION['ip_address']) || !isset($_SESSION['user_agent'])) {
        return false;
    }
    
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        return false;
    }
    
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        return false;
    }
    
    return true;
}

/**
 * Check for session timeout
 */
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes in seconds
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Last request was more than 30 minutes ago
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time(); // Update last activity time stamp
    return true;
}

/**
 * Force HTTPS in production
 */
function forceHTTPS() {
    if ($_SERVER['HTTPS'] !== 'on' && $_SERVER['HTTP_HOST'] !== 'localhost') {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

/**
 * Sanitize output to prevent XSS
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32) {
    if (!function_exists('random_bytes')) {
        throw new Exception('random_bytes function not available');
    }
    return bin2hex(random_bytes($length));
}
?>