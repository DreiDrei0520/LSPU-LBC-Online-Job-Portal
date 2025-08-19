<?php
// db_connection.php

// Database credentials
$host     = 'localhost';
$db       = 'appjobsystem';
$user     = 'root';
$password = '';

// Enable MySQLi error reporting (optional, for development)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create connection
    $conn = new mysqli($host, $user, $password, $db);
    // Set charset to avoid charset issues
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    // If something goes wrong, display (or log) the error and exit
    error_log('MySQL Connection Error: ' . $e->getMessage());
    die('Database connection failed.');
}
