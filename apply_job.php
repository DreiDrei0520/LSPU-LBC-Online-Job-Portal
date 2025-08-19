<?php
session_start();
require 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$job_id = $data['job_id'];
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && $job_id) {
    $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $job_id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Application submitted successfully.']);
    } else {
        echo json_encode(['message' => 'Error submitting application.']);
    }
} else {
    echo json_encode(['message' => 'Invalid user or job ID.']);
}
?>
    