<?php
require 'includes/db.php';

if (!isset($_GET['application_id'])) {
    echo json_encode(['success' => false, 'message' => 'Application ID not provided']);
    exit;
}

$applicationId = (int)$_GET['application_id'];

// Get evaluation data
$stmt = $conn->prepare("SELECT * FROM evaluations WHERE application_id = ?");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $evaluation = $result->fetch_assoc();
    echo json_encode(['success' => true, 'evaluation' => $evaluation]);
} else {
    echo json_encode(['success' => false, 'message' => 'No evaluation found']);
}
?>