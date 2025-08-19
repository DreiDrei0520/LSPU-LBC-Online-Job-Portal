<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'appjobsystem');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get work experience for an application
function getWorkExperience($conn, $applicationId) {
    $stmt = $conn->prepare("SELECT * FROM application_work_experience WHERE application_id = ? ORDER BY start_date DESC");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $workExperience = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $workExperience;
}

// Function to get education for an application
function getApplicationEducation($conn, $applicationId) {
    $stmt = $conn->prepare("SELECT * FROM application_education WHERE application_id = ? ORDER BY level DESC");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $education = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $education;
}
?>