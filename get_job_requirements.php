<?php
include('db_connection.php');

if (!isset($_GET['position_id'])) {
    die(json_encode(['error' => 'No position ID provided']));
}

$positionId = (int)$_GET['position_id'];

$sql = "SELECT 
            MAX(CASE WHEN requirement_type = 'eligibility' THEN description ELSE '' END) as eligibility,
            MAX(CASE WHEN requirement_type = 'qualification' THEN description ELSE '' END) as qualification,
            MAX(CASE WHEN requirement_type = 'experience' THEN description ELSE '' END) as experience,
            MAX(CASE WHEN requirement_type = 'training' THEN description ELSE '' END) as training
        FROM position_requirements
        WHERE position_id = ?
        GROUP BY position_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $positionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode([
        'eligibility' => 'Not specified',
        'qualification' => 'Not specified',
        'experience' => 'Not specified',
        'training' => 'Not specified'
    ]));
}

$requirements = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($requirements);
?>