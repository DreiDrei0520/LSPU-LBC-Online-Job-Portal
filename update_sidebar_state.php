<?php
session_start();

// Check if the request is POST and contains the collapsed parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collapsed'])) {
    $_SESSION['sidebar_collapsed'] = (bool)$_POST['collapsed'];
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Return error if invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>