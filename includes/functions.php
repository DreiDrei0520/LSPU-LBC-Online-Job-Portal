<?php
require 'db.php';

// Secure file path handling
function getProfilePicPath($filename) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($ext), $allowed)) {
        return 'uploads/profile_pics/default.jpg';
    }
    
    $safePath = 'uploads/profile_pics/' . basename($filename);
    return file_exists($safePath) ? $safePath : 'uploads/profile_pics/default.jpg';
}

// Time formatting
function getTimeAgo($timestamp) {
    $timeDiff = time() - strtotime($timestamp);
    
    if ($timeDiff < 60) return 'just now';
    if ($timeDiff < 3600) return floor($timeDiff/60) . ' min ago';
    if ($timeDiff < 86400) return floor($timeDiff/3600) . ' hours ago';
    return floor($timeDiff/86400) . ' days ago';
}

// Database functions
function getFilteredApplications($statuses, $limit = 10, $offset = 0) {
    global $conn;
    
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $types = str_repeat('s', count($statuses));
    
    $stmt = $conn->prepare("SELECT a.*, u.first_name, u.last_name, u.email, u.profile_pic 
                          FROM applications a
                          JOIN users u ON a.user_id = u.user_id
                          WHERE a.status IN ($placeholders)
                          LIMIT ? OFFSET ?");
    
    $params = array_merge($statuses, [$limit, $offset]);
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getNewApplications($limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT a.*, u.first_name, u.last_name 
                          FROM applications a
                          JOIN users u ON a.user_id = u.user_id
                          WHERE a.submitted_at >= NOW() - INTERVAL 1 DAY
                          ORDER BY a.submitted_at DESC
                          LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getUserData($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT user_id, profile_pic, name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

function updateApplicationStatus($applicationId, $status) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
    $stmt->bind_param("si", $status, $applicationId);
    return $stmt->execute();
}
?>