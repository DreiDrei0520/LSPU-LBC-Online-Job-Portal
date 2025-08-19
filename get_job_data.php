<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Job ID not provided']);
    exit;
}

$jobId = intval($_GET['id']);

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$userDb = 'root';
$pass = '';
$conn = new mysqli($host, $userDb, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch job data
$stmt = $conn->prepare("SELECT jp.*, d.name AS department_name, l.name AS location_name 
                      FROM job_positions jp 
                      JOIN departments d ON jp.department_id = d.department_id
                      JOIN locations l ON jp.location_id = l.location_id
                      WHERE jp.position_id = ?");
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    echo json_encode(['error' => 'Job not found']);
    exit;
}

// Fetch requirements for this job
$requirements = [
    'eligibility' => '',
    'qualification' => '',
    'experience' => '',
    'training' => ''
];

$reqStmt = $conn->prepare("SELECT requirement_type, description FROM position_requirements WHERE position_id = ?");
$reqStmt->bind_param("i", $jobId);
$reqStmt->execute();
$reqResult = $reqStmt->get_result();
while ($req = $reqResult->fetch_assoc()) {
    $requirements[$req['requirement_type']] = $req['description'];
}
$reqStmt->close();

// Prepare response
$response = [
    'position_id' => $job['position_id'],
    'title' => $job['title'],
    'department_name' => $job['department_name'],
    'type' => $job['type'],
    'category' => $job['category'],
    'location_name' => $job['location_name'],
    'place_of_assignment' => $job['place_of_assignment'],
    'description' => $job['description'],
    'salary_range' => $job['salary_range'],
    'status' => $job['status'],
    'requirements' => $requirements
];

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>