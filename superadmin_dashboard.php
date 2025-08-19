<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// Check if user is superadmin
if ($_SESSION['role'] !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$userDb = 'root';
$pass = '';
$conn = new mysqli($host, $userDb, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS system_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45) NULL,
        log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        affected_table VARCHAR(100) NULL,
        record_id INT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )"
];

foreach ($tables as $query) {
    if (!$conn->query($query)) {
        die("Error creating table: " . $conn->error);
    }
}

// Get system statistics
$stats = [
    'total_users' => 0,
    'total_applications' => 0,
    'total_jobs' => 0,
    'pending_applications' => 0,
    'hired_applicants' => 0,
    'admins_count' => 0,
    'database_size' => 0,
    'active_jobs' => 0,
    'departments_count' => 0,
    'locations_count' => 0
];

// Helper function to safely get count
function getCount($conn, $query) {
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc()['count'] : 0;
}

// Get all statistics
$stats['total_users'] = getCount($conn, "SELECT COUNT(*) as count FROM users");
$stats['total_applications'] = getCount($conn, "SELECT COUNT(*) as count FROM applications");
$stats['total_jobs'] = getCount($conn, "SELECT COUNT(*) as count FROM job_positions");
$stats['pending_applications'] = getCount($conn, "SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'");
$stats['hired_applicants'] = getCount($conn, "SELECT COUNT(*) as count FROM applications WHERE status = 'Hired'");
$stats['admins_count'] = getCount($conn, "SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'superadmin')");
$stats['active_jobs'] = getCount($conn, "SELECT COUNT(*) as count FROM job_positions WHERE status = 'Open'");
$stats['departments_count'] = getCount($conn, "SELECT COUNT(*) as count FROM departments");
$stats['locations_count'] = getCount($conn, "SELECT COUNT(*) as count FROM locations");

// Database size
$result = $conn->query("SELECT SUM(data_length + index_length) / 1024 / 1024 as size FROM information_schema.TABLES WHERE table_schema = '$db'");
$stats['database_size'] = $result ? round($result->fetch_assoc()['size'], 2) : 0;

// Recent activity logs
$activityLogs = [];
$result = $conn->query("SELECT * FROM system_logs ORDER BY log_time DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activityLogs[] = $row;
    }
}

// Recent applications
$recentApplications = [];
$result = $conn->query("
    SELECT a.*, u.first_name, u.last_name, u.email, u.profile_pic, j.title as job_title, d.name as department_name
    FROM applications a 
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions j ON a.position_id = j.position_id
    JOIN departments d ON j.department_id = d.department_id
    ORDER BY a.submitted_at DESC LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentApplications[] = $row;
    }
}

// Recent job postings
$recentJobs = [];
$result = $conn->query("
    SELECT j.*, d.name as department_name, l.name as location_name 
    FROM job_positions j
    JOIN departments d ON j.department_id = d.department_id
    JOIN locations l ON j.location_id = l.location_id
    ORDER BY j.date_posted DESC LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentJobs[] = $row;
    }
}

// Get all applicants for modal
$allApplicants = [];
$result = $conn->query("
    SELECT user_id, CONCAT(first_name, ' ', last_name) as name, email, profile_pic, created_at 
    FROM users 
    WHERE role = 'applicant' 
    ORDER BY created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allApplicants[] = $row;
    }
}

// Get all admins for modal
$allAdmins = [];
$result = $conn->query("
    SELECT user_id, CONCAT(first_name, ' ', last_name) as name, email, profile_pic, role, created_at 
    FROM users 
    WHERE role IN ('admin', 'superadmin') 
    ORDER BY created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allAdmins[] = $row;
    }
}

// Get all jobs for modal
$allJobs = [];
$result = $conn->query("
    SELECT j.*, d.name as department_name, l.name as location_name 
    FROM job_positions j
    JOIN departments d ON j.department_id = d.department_id
    JOIN locations l ON j.location_id = l.location_id
    ORDER BY j.date_posted DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allJobs[] = $row;
    }
}

// Get all applications for modal
$allApplications = [];
$result = $conn->query("
    SELECT a.*, u.first_name, u.last_name, u.email, u.profile_pic, 
           j.title as job_title, d.name as department_name
    FROM applications a 
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions j ON a.position_id = j.position_id
    JOIN departments d ON j.department_id = d.department_id
    ORDER BY a.submitted_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allApplications[] = $row;
    }
}

// Get departments statistics
$departmentStats = [];
$result = $conn->query("
    SELECT d.department_id, d.name, COUNT(j.position_id) as job_count
    FROM departments d
    LEFT JOIN job_positions j ON d.department_id = j.department_id
    GROUP BY d.department_id, d.name
    ORDER BY job_count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departmentStats[] = $row;
    }
}

// Get application status statistics
$statusStats = [];
$result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM applications 
    GROUP BY status
    ORDER BY count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $statusStats[] = $row;
    }
}

// Get user details
$userId = $_SESSION['user_id'];
$user = null;
$stmt = $conn->prepare("SELECT user_id, profile_pic, first_name, last_name, email FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Notification system
$notifications = [];
$unreadCount = 0;

// Get all admin notifications (with error handling)
try {
    $result = $conn->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'icon' => 'exclamation-circle',
                'message' => $row['message'],
                'time' => date('M j, Y', strtotime($row['created_at'])),
                'unread' => $row['is_read'] == 0
            ];
            if ($row['is_read'] == 0) $unreadCount++;
        }
    }
} catch (Exception $e) {
    // Log error but don't stop execution
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications[] = [
        'icon' => 'exclamation-triangle',
        'message' => 'Notification system is currently unavailable',
        'time' => date('M j, Y'),
        'unread' => true
    ];
    $unreadCount = 1;
}

// Mark notifications as read if requested
if (isset($_POST['mark_all_read'])) {
    try {
        $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
        $_SESSION['notifications_read'] = true;
        header('Location: superadmin_dashboard.php');
        exit;
    } catch (Exception $e) {
        error_log("Error marking notifications as read: " . $e->getMessage());
    }
}

// Handle backup request
if (isset($_POST['backup_database'])) {
    $backup_file = 'backups/db_backup_' . date("Y-m-d-H-i-s") . '.sql';
    
    // Create backup directory if it doesn't exist
    if (!file_exists('backups')) {
        mkdir('backups', 0755, true);
    }
    
    // Execute mysqldump command
    $command = "mysqldump --user={$userDb} --password={$pass} --host={$host} {$db} > {$backup_file}";
    system($command, $output);
    
    if ($output === 0) {
        $_SESSION['success_message'] = "Database backup created successfully!";
        $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Database Backup', 'Backup created: {$backup_file}')");
    } else {
        $_SESSION['error_message'] = "Error creating database backup";
        $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Database Backup Failed', 'Error creating backup')");
    }
    
    header('Location: superadmin_dashboard.php');
    exit;
}

// Handle restore request
if (isset($_POST['restore_database'])) {
    if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['backup_file']['tmp_name'];
        $file_ext = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);
        
        if ($file_ext === 'sql') {
            // Execute mysql command to restore
            $command = "mysql --user={$userDb} --password={$pass} --host={$host} {$db} < {$tmp_name}";
            system($command, $output);
            
            if ($output === 0) {
                $_SESSION['success_message'] = "Database restored successfully!";
                $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Database Restore', 'Database restored from backup')");
            } else {
                $_SESSION['error_message'] = "Error restoring database";
                $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Database Restore Failed', 'Error restoring database')");
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type. Please upload a .sql file";
        }
    } else {
        $_SESSION['error_message'] = "Error uploading backup file";
    }
    
    header('Location: superadmin_dashboard.php');
    exit;
}

// Handle add admin request
if (isset($_POST['add_admin'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash('Admin@123', PASSWORD_DEFAULT); // Default password
    
    // Check if email already exists
    $check = $conn->query("SELECT user_id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists in the system";
    } else {
        $insert = $conn->query("
            INSERT INTO users (first_name, last_name, email, password, role, is_active, created_at, updated_at)
            VALUES ('$firstName', '$lastName', '$email', '$password', '$role', 1, NOW(), NOW())
        ");
        
        if ($insert) {
            $_SESSION['success_message'] = "New admin added successfully! Default password: Admin@123";
            $conn->query("
                INSERT INTO system_logs (user_id, action, details, affected_table, record_id)
                VALUES ($userId, 'Add Admin', 'Added new $role: $email', 'users', $conn->insert_id)
            ");
        } else {
            $_SESSION['error_message'] = "Error adding new admin: " . $conn->error;
        }
    }
    
    header('Location: superadmin_dashboard.php');
    exit;
}

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);

// Log page view
try {
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn->query("
        INSERT INTO system_logs (user_id, action, details, ip_address)
        VALUES ($userId, 'Page View', 'Superadmin dashboard accessed', '$ip')
    ");
} catch (Exception $e) {
    error_log("Error logging page view: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Superadmin Dashboard | LSPU Job Portal</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- AOS (Animate On Scroll) -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/superadmin/superadmin_dashboard.css">
</head>
<body>
  <?php include 'superadmin_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar animate-slide-up">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Superadmin Dashboard <span class="superadmin-badge">SUPERADMIN</span></h1>
        <p>System overview and administration controls</p>
      </div>
      <div class="topbar-actions">
        <div class="dropdown">
          <button class="notification-btn dropdown-toggle position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="notification-badge"><?= htmlspecialchars($unreadCount) ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown" style="width: 350px;">
            <div class="notification-header d-flex justify-content-between align-items-center p-3 border-bottom">
              <h5 class="mb-0">System Notifications</h5>
              <form method="POST" action="">
                <button type="submit" name="mark_all_read" class="btn btn-sm btn-link text-primary p-0">Mark All as Read</button>
              </form>
            </div>
            <?php if (!empty($notifications)): ?>
              <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($notifications as $note): ?>
                  <div class="notification-item <?= $note['unread'] ? 'unread' : '' ?>">
                    <div class="notification-icon">
                      <i class="fas fa-<?= htmlspecialchars($note['icon']) ?>"></i>
                    </div>
                    <div class="notification-content">
                      <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                      <span class="notification-time">
                        <i class="far fa-clock me-1"></i> <?= htmlspecialchars($note['time']) ?>
                      </span>
                    </div>
                    <?php if ($note['unread']): ?>
                      <span class="unread-badge"></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No new notifications</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="dropdown">
          <button class="profile-btn dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($hasProfilePic): ?>
              <img src="<?= htmlspecialchars($picsPath) ?>" class="profile-img rounded-circle" alt="Profile" style="width: 36px; height: 36px; object-fit: cover;">
            <?php else: ?>
              <i class="fas fa-user-circle fs-5"></i>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end profile-menu" aria-labelledby="profileDropdown">
            <a href="superadmin_settings.php" class="dropdown-item">
              <i class="fas fa-cog me-2"></i>
              <span>Settings</span>
            </a>
            <a href="logout.php" class="dropdown-item">
              <i class="fas fa-sign-out-alt me-2"></i>
              <span>Logout</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card animate-slide-up delay-1">
      <img src="<?= htmlspecialchars($picsPath) ?>" class="welcome-img rounded-circle" alt="Profile Picture" style="width: 80px; height: 80px; object-fit: cover;">
      <div class="welcome-content">
        <h2>Hello, <?= htmlspecialchars($user['first_name'] ?? 'Superadmin') ?>!</h2>
        <p>You have full system administration privileges</p>
      </div>
    </div>

    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show alert-fixed" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- System Stats Cards -->
    <div class="stats-grid mb-4">
      <div class="stats-card system-card animate-fade-in delay-2">
        <div class="card-icon primary">
          <i class="fas fa-users"></i>
        </div>
        <div class="card-value"><?= htmlspecialchars($stats['total_users']) ?></div>
        <h5 class="card-title">Total Users</h5>
        <p class="card-text"><?= htmlspecialchars($stats['admins_count']) ?> admins</p>
        <div class="admin-actions">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageAdminsModal">
            <i class="fas fa-user-shield me-1"></i> Manage Admins
          </button>
        </div>
      </div>
      
      <div class="stats-card system-card animate-fade-in delay-3">
        <div class="card-icon success">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="card-value"><?= htmlspecialchars($stats['total_applications']) ?></div>
        <h5 class="card-title">Applications</h5>
        <p class="card-text"><?= htmlspecialchars($stats['hired_applicants']) ?> hired</p>
        <div class="admin-actions">
          <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#viewApplicationsModal">
            <i class="fas fa-list me-1"></i> View All
          </button>
        </div>
      </div>
      
      <div class="stats-card system-card animate-fade-in delay-4">
        <div class="card-icon warning">
          <i class="fas fa-briefcase"></i>
        </div>
        <div class="card-value"><?= htmlspecialchars($stats['total_jobs']) ?></div>
        <h5 class="card-title">Job Positions</h5>
        <p class="card-text"><?= htmlspecialchars($stats['active_jobs']) ?> active</p>
        <div class="admin-actions">
          <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#manageJobsModal">
            <i class="fas fa-briefcase me-1"></i> Manage Jobs
          </button>
        </div>
      </div>
      
      <div class="stats-card system-card animate-fade-in delay-5">
        <div class="card-icon info">
          <i class="fas fa-database"></i>
        </div>
        <div class="card-value"><?= htmlspecialchars($stats['database_size']) ?> MB</div>
        <h5 class="card-title">Database Size</h5>
        <p class="card-text"><?= htmlspecialchars($stats['departments_count']) ?> departments</p>
        <div class="admin-actions">
          <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#databaseModal">
            <i class="fas fa-cog me-1"></i> Manage
          </button>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
      <!-- Applications by Status Chart -->
      <div class="col-lg-6 animate-fade-in delay-6">
        <div class="card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Applications by Status</h5>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="statusChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Jobs by Department Chart -->
      <div class="col-lg-6 animate-fade-in delay-7">
        <div class="card h-100">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Jobs by Department</h5>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="departmentChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row g-4">
      <!-- Recent Applications -->
      <div class="col-lg-6 animate-fade-in delay-8">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewApplicationsModal">View All</button>
          </div>
          <div class="card-body p-0">
            <?php if (!empty($recentApplications)): ?>
              <div class="list-group list-group-flush">
                <?php foreach ($recentApplications as $app): ?>
                  <a href="view_application.php?id=<?= htmlspecialchars($app['application_id']) ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-1"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></h6>
                        <small class="text-muted">Applied for <?= htmlspecialchars($app['job_title']) ?></small>
                      </div>
                      <div class="text-end">
                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>">
                          <?= htmlspecialchars($app['status']) ?>
                        </span>
                        <div class="text-muted small mt-1">
                          <?= date('M j, Y', strtotime($app['submitted_at'])) ?>
                        </div>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No recent applications found</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Recent Activity Logs -->
      <div class="col-lg-6 animate-fade-in delay-9">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>System Activity Logs</h5>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#systemLogsModal">View All</button>
          </div>
          <div class="card-body p-0">
            <?php if (!empty($activityLogs)): ?>
              <div class="list-group list-group-flush">
                <?php foreach ($activityLogs as $log): ?>
                  <div class="log-entry <?= 
                    strpos($log['action'], 'Error') !== false ? 'log-critical' : 
                    (strpos($log['action'], 'Warning') !== false ? 'log-warning' : 'log-info') ?>">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="mb-1"><?= htmlspecialchars($log['action']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($log['details']) ?></small>
                      </div>
                      <div class="text-end">
                        <small class="text-muted"><?= date('M j, H:i', strtotime($log['log_time'])) ?></small>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <p class="text-muted">No recent activity logs</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Job Postings -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Recent Job Postings</h5>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageJobsModal">View All</button>
          </div>
          <div class="card-body">
            <?php if (!empty($recentJobs)): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Position</th>
                      <th>Department</th>
                      <th>Type</th>
                      <th>Category</th>
                      <th>Location</th>
                      <th>Status</th>
                      <th>Posted</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentJobs as $job): ?>
                      <tr>
                        <td><?= htmlspecialchars($job['title']) ?></td>
                        <td><?= htmlspecialchars($job['department_name']) ?></td>
                        <td><?= htmlspecialchars($job['type']) ?></td>
                        <td><?= htmlspecialchars($job['category']) ?></td>
                        <td><?= htmlspecialchars($job['location_name']) ?></td>
                        <td>
                          <span class="status-badge job-status-<?= strtolower($job['status']) ?>">
                            <?= htmlspecialchars($job['status']) ?>
                          </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($job['date_posted'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <p class="text-muted">No recent job postings</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  
  <!-- Manage Admins Modal -->
  <div class="modal fade" id="manageAdminsModal" tabindex="-1" aria-labelledby="manageAdminsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl-custom">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="manageAdminsModalLabel">
            <i class="fas fa-user-shield me-2"></i>System Administrators
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex justify-content-between mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
              <i class="fas fa-plus me-2"></i>Add New Admin
            </button>
            <div class="input-group" style="width: 300px;">
              <input type="text" id="adminSearchInput" class="form-control" placeholder="Search admins...">
              <button class="btn btn-outline-secondary" type="button" id="adminSearchBtn">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allAdmins as $admin): ?>
                  <tr>
                    <td><?= htmlspecialchars($admin['user_id']) ?></td>
                    <td>
                      <img src="uploads/profile_pics/<?= !empty($admin['profile_pic']) ? htmlspecialchars($admin['profile_pic']) : 'default.jpg' ?>" 
                           class="user-avatar" alt="Profile">
                    </td>
                    <td><?= htmlspecialchars($admin['name']) ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                      <span class="badge <?= $admin['role'] == 'superadmin' ? 'badge-superadmin' : 'badge-admin' ?>">
                        <?= htmlspecialchars(ucfirst($admin['role'])) ?>
                      </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Add Admin Modal -->
  <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addAdminModalLabel">
            <i class="fas fa-user-plus me-2"></i>Add New Admin
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <div class="mb-3">
              <label for="first_name" class="form-label">First Name</label>
              <input type="text" class="form-control" id="first_name" name="first_name" required>
            </div>
            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <select class="form-select" id="role" name="role" required>
                <option value="admin">Admin</option>
                <option value="superadmin">Superadmin</option>
              </select>
            </div>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              A default password (Admin@123) will be set which the user can change later.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Database Management Modal -->
  <div class="modal fade" id="databaseModal" tabindex="-1" aria-labelledby="databaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="databaseModalLabel">
            <i class="fas fa-database me-2"></i>Database Management
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> These actions affect the entire system. Proceed with caution.
          </div>
          
          <div class="d-grid gap-2 mb-4">
            <button class="btn btn-success" onclick="backupDatabase()">
              <i class="fas fa-download me-2"></i>Create Backup
              <div class="loading-spinner" id="backupSpinner"></div>
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#restoreDatabaseModal">
              <i class="fas fa-upload me-2"></i>Restore Backup
            </button>
          </div>
          
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Database Information</h6>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <strong>Name:</strong> <?= htmlspecialchars($db) ?>
              </div>
              <div class="mb-2">
                <strong>Size:</strong> <?= htmlspecialchars($stats['database_size']) ?> MB
              </div>
              <div class="mb-2">
                <strong>Tables:</strong> 
                <?php
                $result = $conn->query("SHOW TABLES");
                echo $result ? $result->num_rows : 'N/A';
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Restore Database Modal -->
  <div class="modal fade" id="restoreDatabaseModal" tabindex="-1" aria-labelledby="restoreDatabaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="restoreDatabaseModalLabel">
            <i class="fas fa-database me-2"></i>Restore Database
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="restoreDatabaseForm" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Warning:</strong> This will overwrite all current data with the backup file. Proceed with caution.
            </div>
            
            <div class="mb-3">
              <label for="backupFile" class="form-label">Select Backup File</label>
              <input class="form-control" type="file" id="backupFile" name="backup_file" accept=".sql,.gz" required>
            </div>
            
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="confirmRestore" required>
              <label class="form-check-label" for="confirmRestore">
                I understand this action cannot be undone
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="restore_database" class="btn btn-danger">Restore Database</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- AOS (Animate On Scroll) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/dashboard.js"></script>
  <script>
    // Initialize AOS
    AOS.init({
      duration: 800,
      once: true
    });

    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      // Applications by Status Chart
      const statusCtx = document.getElementById('statusChart').getContext('2d');
      const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
          labels: [
            <?php foreach ($statusStats as $stat): ?>
              '<?= $stat['status'] ?>',
            <?php endforeach; ?>
          ],
          datasets: [{
            data: [
              <?php foreach ($statusStats as $stat): ?>
                <?= $stat['count'] ?>,
              <?php endforeach; ?>
            ],
            backgroundColor: [
              '#f39c12', // Pending
              '#3498db', // Applied
              '#9b59b6', // Under Review
              '#f1c40f', // Interview Scheduled
              '#1abc9c', // Interviewed
              '#2ecc71', // Hired
              '#e74c3c'  // Not Selected
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.label || '';
                  if (label) {
                    label += ': ';
                  }
                  label += context.raw + ' (' + Math.round(context.parsed * 10) / 10 + '%)';
                  return label;
                }
              }
            }
          }
        }
      });

      // Jobs by Department Chart
      const deptCtx = document.getElementById('departmentChart').getContext('2d');
      const deptChart = new Chart(deptCtx, {
        type: 'bar',
        data: {
          labels: [
      <?php foreach ($departmentStats as $dept): ?>
        '<?= htmlspecialchars($dept['name']) ?>',
      <?php endforeach; ?>
    ],
          datasets: [{
            label: 'Job Positions',
            data: [
              <?php foreach ($departmentStats as $dept): ?>
                <?= $dept['job_count'] ?>,
              <?php endforeach; ?>
            ],
            backgroundColor: '#3498db',
            borderColor: '#2980b9',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    });

    // Backup database function
    function backupDatabase() {
      const backupBtn = document.getElementById('backupSpinner');
      backupBtn.classList.remove('d-none');
      
      // Create form data
      const formData = new FormData();
      formData.append('backup_database', 'true');
      
      // Send AJAX request
      fetch('superadmin_dashboard.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        backupBtn.classList.add('d-none');
        // Reload the page to show the success message
        location.reload();
      })
      .catch(error => {
        backupBtn.classList.add('d-none');
        console.error('Error:', error);
        alert('An error occurred during backup');
      });
    }

    // Prevent page caching to avoid back button issues
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };

    // Search functionality for tables
    function setupTableSearch(inputId, btnId, tableSelector) {
      const searchInput = document.getElementById(inputId);
      const searchBtn = document.getElementById(btnId);
      
      if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', function() {
          const searchTerm = searchInput.value.toLowerCase();
          const rows = document.querySelectorAll(`${tableSelector} tbody tr`);
          
          rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchTerm) ? '' : 'none';
          });
        });
        
        searchInput.addEventListener('keyup', function(e) {
          if (e.key === 'Enter') {
            searchBtn.click();
          }
        });
      }
    }
    
    // Filter functionality for applications
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons) {
      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          const filterValue = this.getAttribute('data-filter');
          const rows = document.querySelectorAll('#viewApplicationsModal tbody tr');
          
          // Update active button
          filterButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          
          // Filter rows
          rows.forEach(row => {
            if (filterValue === 'all') {
              row.style.display = '';
            } else {
              const rowStatus = row.getAttribute('data-status');
              row.style.display = rowStatus.includes(filterValue.toLowerCase()) ? '' : 'none';
            }
          });
        });
      });
    }
    
    // Setup search for all tables
    setupTableSearch('adminSearchInput', 'adminSearchBtn', '#manageAdminsModal table');
    setupTableSearch('applicantSearchInput', 'applicantSearchBtn', '#viewApplicantsModal table');
    setupTableSearch('applicationSearchInput', 'applicationSearchBtn', '#viewApplicationsModal table');
    setupTableSearch('logSearchInput', 'logSearchBtn', '#systemLogsModal table');
    setupTableSearch('jobSearchInput', 'jobSearchBtn', '#manageJobsModal table');
    
    // Mobile sidebar toggle
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    if (mobileSidebarToggle) {
      mobileSidebarToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
      });
    }
    
    // Detect mobile view
    function checkMobileView() {
      if (window.innerWidth <= 992) {
        if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'block';
        document.querySelector('.sidebar').classList.remove('active');
      } else {
        if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'none';
        document.querySelector('.sidebar').classList.add('active');
      }
    }
    
    // Initial check
    checkMobileView();
    
    // Add resize listener
    window.addEventListener('resize', checkMobileView);

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert-fixed');
      alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      });
    }, 5000);
  </script>
</body>
</html>