<?php
require 'includes/db.php';

if (!isset($_GET['application_id'])) {
    echo json_encode(['success' => false, 'message' => 'Application ID not provided']);
    exit;
}

$applicationId = (int)$_GET['application_id'];

// Get application details
$stmt = $conn->prepare("
    SELECT 
        a.application_id, 
        CONCAT(u.first_name, ' ', u.last_name) as name,
        jp.title as position,
        jp.category
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions jp ON a.position_id = jp.position_id
    WHERE a.application_id = ?
");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
    echo json_encode(['success' => true, 'application' => $application]);
} else {
    echo json_encode(['success' => false, 'message' => 'Application not found']);
}
?>