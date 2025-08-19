<?php
require 'db_connection.php'; // Include your DB connection

$category = $_GET['category'] ?? '';
$stmt = $conn->prepare("SELECT job_id, title FROM jobs WHERE category = ?");
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode($jobs);
?>
