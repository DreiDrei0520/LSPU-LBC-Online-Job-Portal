<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

// Get new applications (last 24 hours) for notifications
$newApplicationsQuery = "SELECT a.*, u.first_name, u.last_name 
                        FROM applications a
                        JOIN users u ON a.user_id = u.user_id
                        WHERE a.submitted_at >= NOW() - INTERVAL 1 DAY
                        ORDER BY a.submitted_at DESC";
$newApplicationsResult = $conn->query($newApplicationsQuery);
$newApplications = $newApplicationsResult->fetch_all(MYSQLI_ASSOC);

// Prepare notifications
$notifications = [];
foreach ($newApplications as $app) {
    $timeDiff = time() - strtotime($app['submitted_at']);
    $timeAgo = '';
    
    if ($timeDiff < 60) {
        $timeAgo = 'just now';
    } elseif ($timeDiff < 3600) {
        $mins = floor($timeDiff / 60);
        $timeAgo = $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($timeDiff / 86400);
        $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    
    $notifications[] = [
        'icon' => 'file-alt',
        'message' => 'New application from ' . $app['first_name'] . ' ' . $app['last_name'],
        'time' => $timeAgo,
        'application_id' => $app['application_id']
    ];
}
?>

<div class="dropdown-menu notification-menu">
    <div class="notification-header">
        <h3>Notifications</h3>
        <a href="#">View All</a>
    </div>
    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $note): ?>
            <a href="application_details.php?id=<?= $note['application_id'] ?>" class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-<?= $note['icon'] ?>"></i>
                </div>
                <div class="notification-content">
                    <p><?= htmlspecialchars($note['message']) ?></p>
                    <span class="notification-time"><?= $note['time'] ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="notification-item">
            <div class="notification-content">
                <p>No new notifications</p>
            </div>
        </div>
    <?php endif; ?>
</div>