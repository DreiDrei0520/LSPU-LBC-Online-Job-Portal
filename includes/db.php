<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'appjobsystem';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");
?>