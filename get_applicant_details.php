<?php
require 'db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$appId = $_GET['id'];

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$query = "
    SELECT a.*, u.profile_pic 
    FROM applications a 
    LEFT JOIN users u ON a.email = u.email 
    WHERE a.application_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $applicant = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $applicant]);
} else {
    echo json_encode(['success' => false, 'message' => 'Applicant not found']);
}

$stmt->close();
$conn->close();
?>  