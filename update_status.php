<?php
session_start();

// Check if POST data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Basic status validation (ensure it's one of the allowed statuses)
    $allowedStatuses = ['Under Review', 'Documents Verified', 'Interview Scheduled', 'Hired', 'Rejected', 'Pending'];
    
    if ($id > 0 && in_array($status, $allowedStatuses)) {
        // DB Connection
        $conn = new mysqli('localhost', 'root', '', 'appjobsystems');

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare and execute update query
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Status updated successfully.";
        } else {
            $_SESSION['msg'] = "Failed to update status.";
        }

        $stmt->close();
        $conn->close();
    } else {
        $_SESSION['msg'] = "Invalid data provided.";
    }

    // Redirect back to dashboard
    header("Location: admin_dashboard.php");
    exit;
} else {
    // If accessed directly, redirect to dashboard
    header("Location: admin_dashboard.php");
    exit;
}
