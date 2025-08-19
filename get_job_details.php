<?php
include('db_connection.php');

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'No job ID provided']));
}

$jobId = (int)$_GET['id'];

$sql = "SELECT p.*, d.name as department, l.name as location 
        FROM job_positions p
        JOIN departments d ON p.department_id = d.department_id
        JOIN locations l ON p.location_id = l.location_id
        WHERE p.position_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $jobId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(['error' => 'Job not found']));
}

$job = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($job);
?>