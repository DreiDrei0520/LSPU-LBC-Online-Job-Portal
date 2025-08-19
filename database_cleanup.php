<?php
session_start();

// Database configuration - Update these with your actual credentials
$host = 'localhost';
$db = 'appjobsystem';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Create MySQLi connection with error handling
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset($charset);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Check if user is logged in and is superadmin
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
if ($_SESSION['role'] !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$tables_to_clean = [
    'login_attempts' => 'Login Attempts',
    'system_logs' => 'System Logs',
    'applications' => 'Applications',
    'users' => 'Inactive Users'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->autocommit(FALSE);
        
        $days = (int)$_POST['days_old'];
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        $force_delete = isset($_POST['force_delete']) ? true : false;
        
        // Initialize counters
        $login_attempts_deleted = 0;
        $system_logs_deleted = 0;
        $applications_deleted = 0;
        $users_deleted = 0;
        $files_deleted = 0;
        
        // Clean login attempts
        if (isset($_POST['clean_login_attempts'])) {
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < ?");
            $stmt->bind_param("s", $date_threshold);
            $stmt->execute();
            $login_attempts_deleted = $conn->affected_rows;
            $stmt->close();
        }
        
        
        // Clean system logs with advanced preservation rules
        if (isset($_POST['clean_system_logs'])) {
            if ($force_delete) {
                // Force delete all logs older than threshold
                $stmt = $conn->prepare("DELETE FROM system_logs WHERE log_time < ?");
                $stmt->bind_param("s", $date_threshold);
                $stmt->execute();
                $system_logs_deleted = $conn->affected_rows;
                $stmt->close();
            } else {
                // Get total count of logs
                $result = $conn->query("SELECT COUNT(*) FROM system_logs");
                $total_logs = $result ? $result->fetch_row()[0] : 0;
                
                // Calculate how many to preserve (1000 or 10% of total, whichever is larger)
                $preserve_count = max(1000, ceil($total_logs * 0.1));
                
                // Get IDs of logs to keep (most recent $preserve_count records)
                $keep_ids = [];
                $result = $conn->query("SELECT log_id FROM system_logs ORDER BY log_time DESC LIMIT $preserve_count");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $keep_ids[] = $row['log_id'];
                    }
                }
                
                // Delete logs older than threshold and not in keep list
                if (!empty($keep_ids)) {
                    $placeholders = implode(',', array_fill(0, count($keep_ids), '?'));
                    $types = str_repeat('i', count($keep_ids));
                    $stmt = $conn->prepare("DELETE FROM system_logs WHERE log_time < ? AND log_id NOT IN ($placeholders)");
                    $params = array_merge([$date_threshold], $keep_ids);
                    $stmt->bind_param(str_repeat('s', 1) . $types, ...$params);
                    $stmt->execute();
                    $system_logs_deleted = $conn->affected_rows;
                    $stmt->close();
                } else {
                    // If no logs to keep, just delete all older than threshold
                    $stmt = $conn->prepare("DELETE FROM system_logs WHERE log_time < ?");
                    $stmt->bind_param("s", $date_threshold);
                    $stmt->execute();
                    $system_logs_deleted = $conn->affected_rows;
                    $stmt->close();
                }
            }
        }
        
        // Clean old applications
        if (isset($_POST['clean_applications'])) {
            // First get applications to delete for file cleanup
            $stmt = $conn->prepare("SELECT application_letter, personal_data_sheet, transcript_of_records, proof_of_eligibility, other_documents FROM applications WHERE submitted_at < ?");
            $stmt->bind_param("s", $date_threshold);
            $stmt->execute();
            $result = $stmt->get_result();
            $files_to_delete = [];
            while ($row = $result->fetch_assoc()) {
                $files_to_delete[] = $row;
            }
            $stmt->close();
            
            // Delete the applications
            $stmt = $conn->prepare("DELETE FROM applications WHERE submitted_at < ?");
            $stmt->bind_param("s", $date_threshold);
            $stmt->execute();
            $applications_deleted = $conn->affected_rows;
            $stmt->close();
            
            // Delete associated files
            foreach ($files_to_delete as $files) {
                foreach ($files as $file) {
                    if (!empty($file) && file_exists($file)) {
                        if (@unlink($file)) {
                            $files_deleted++;
                        }
                    }
                }
            }
            
            // Also delete related records if force delete is enabled
            if ($force_delete && $applications_deleted > 0) {
                // Delete related education records
                $conn->query("DELETE ae FROM application_education ae LEFT JOIN applications a ON ae.application_id = a.application_id WHERE a.application_id IS NULL");
                
                // Delete related work experience records
                $conn->query("DELETE awe FROM application_work_experience awe LEFT JOIN applications a ON awe.application_id = a.application_id WHERE a.application_id IS NULL");
            }
        }
        
        // Clean inactive users (applicants only)
        if (isset($_POST['clean_users'])) {
            // Get users to delete for profile pic cleanup
            $stmt = $conn->prepare("SELECT user_id, profile_pic FROM users WHERE role = 'applicant' AND (last_login < ? OR last_login IS NULL)");
            $stmt->bind_param("s", $date_threshold);
            $stmt->execute();
            $result = $stmt->get_result();
            $users_to_delete = [];
            while ($row = $result->fetch_assoc()) {
                $users_to_delete[] = $row;
            }
            $stmt->close();
            
            // Delete the users
            $stmt = $conn->prepare("DELETE FROM users WHERE role = 'applicant' AND (last_login < ? OR last_login IS NULL)");
            $stmt->bind_param("s", $date_threshold);
            $stmt->execute();
            $users_deleted = $conn->affected_rows;
            $stmt->close();
            
            // Delete profile pictures (only if not default)
            foreach ($users_to_delete as $user) {
                if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.jpg' && file_exists('uploads/profile_pics/' . $user['profile_pic'])) {
                    if (@unlink('uploads/profile_pics/' . $user['profile_pic'])) {
                        $files_deleted++;
                    }
                }
            }
            
            // Also delete orphaned applications if force delete is enabled
            if ($force_delete && $users_deleted > 0) {
                $conn->query("DELETE FROM applications WHERE user_id NOT IN (SELECT user_id FROM users)");
            }
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        
        // Build success message
        $message_parts = [];
        if ($login_attempts_deleted > 0) {
            $message_parts[] = "$login_attempts_deleted login attempts";
        }
        if ($system_logs_deleted > 0) {
            $message_parts[] = "$system_logs_deleted system logs";
        }
        if ($applications_deleted > 0) {
            $message_parts[] = "$applications_deleted applications";
        }
        if ($users_deleted > 0) {
            $message_parts[] = "$users_deleted inactive users";
        }
        if ($files_deleted > 0) {
            $message_parts[] = "$files_deleted associated files";
        }
        
        if (!empty($message_parts)) {
            $success_message = "Database cleanup successful. Removed: " . implode(', ', $message_parts) . " older than $days days.";
            if ($force_delete) {
                $success_message .= " (Force delete enabled - related records also cleaned)";
            }
            
            // Log the cleanup action
            $action = "Database Cleanup: Removed " . implode(', ', $message_parts);
            $details = "Cleanup performed by superadmin user ID {$_SESSION['user_id']}. Days threshold: $days. Force delete: " . ($force_delete ? 'Yes' : 'No');
            $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
            $stmt->execute();
            $stmt->close();
        } else {
            $success_message = "No records were removed. All selected data types were either empty or newer than $days days.";
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        $error_message = "Database cleanup failed: " . $e->getMessage();
        
        // Log the error
        $action = "Database Cleanup Failed";
        $details = "Error: " . $e->getMessage();
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_SESSION['user_id'], $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

// Get current record counts for display
$record_counts = [];
foreach ($tables_to_clean as $table => $name) {
    if ($table === 'users') {
        $result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'applicant'");
    } elseif ($table === 'system_logs') {
        $result = $conn->query("SELECT COUNT(*) FROM system_logs");
    } else {
        $result = $conn->query("SELECT COUNT(*) FROM $table");
    }
    $record_counts[$table] = $result ? $result->fetch_row()[0] : 0;
    if ($result) $result->free();
}

// Get oldest records for each table
$oldest_records = [];
foreach ($tables_to_clean as $table => $name) {
    if ($table === 'login_attempts') {
        $result = $conn->query("SELECT MIN(attempt_time) FROM login_attempts");
    } elseif ($table === 'system_logs') {
        $result = $conn->query("SELECT MIN(log_time) FROM system_logs");
    } elseif ($table === 'applications') {
        $result = $conn->query("SELECT MIN(submitted_at) FROM applications");
    } elseif ($table === 'users') {
        $result = $conn->query("SELECT MIN(last_login) FROM users WHERE role = 'applicant' AND last_login IS NOT NULL");
    }
    
    $oldest_date = $result ? $result->fetch_row()[0] : null;
    $oldest_records[$table] = $oldest_date ? date('M j, Y', strtotime($oldest_date)) : 'No records';
    if ($result) $result->free();
}

// Get disk usage of uploads directory
function getDirectorySize($path) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

$uploads_size = 0;
if (file_exists('uploads')) {
    $uploads_size = getDirectorySize('uploads');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Cleanup | LSPU Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/superadmin/database_cleanup.css">
</head>
<body>
    <?php include 'superadmin_sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1><i class="fas fa-database me-2"></i>Database Cleanup Tool</h1>
                <p>Manage and clean up old system data</p>
            </div>
            <div class="topbar-actions">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i> Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                        <li><a class="dropdown-item" href="superadmin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="system_logs.php"><i class="fas fa-clipboard-list me-2"></i>View System Logs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show animate-fade-in">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show animate-fade-in">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card disk-usage-card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-hdd me-2"></i>Storage Usage</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="cleanup-summary">
                                        <h5><i class="fas fa-database me-2"></i>Database Statistics</h5>
                                        <div class="cleanup-summary-item">
                                            <span class="label">Login Attempts:</span>
                                            <span class="value"><?= number_format($record_counts['login_attempts']) ?></span>
                                        </div>
                                        <div class="cleanup-summary-item">
                                            <span class="label">System Logs:</span>
                                            <span class="value"><?= number_format($record_counts['system_logs']) ?></span>
                                        </div>
                                        <div class="cleanup-summary-item">
                                            <span class="label">Applications:</span>
                                            <span class="value"><?= number_format($record_counts['applications']) ?></span>
                                        </div>
                                        <div class="cleanup-summary-item">
                                            <span class="label">Applicant Users:</span>
                                            <span class="value"><?= number_format($record_counts['users']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="cleanup-summary">
                                        <h5><i class="fas fa-folder me-2"></i>Uploads Directory</h5>
                                        <div class="cleanup-summary-item">
                                            <span class="label">Total Size:</span>
                                            <span class="value"><?= round($uploads_size / (1024 * 1024), 2) ?> MB</span>
                                        </div>
                                        <div class="progress mt-2">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= min(100, $uploads_size / (50 * 1024 * 1024) * 100) ?>%" 
                                                 aria-valuenow="<?= $uploads_size ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="52428800"> <!-- 50MB max for progress bar -->
                                            </div>
                                        </div>
                                        <small class="text-muted">50MB storage limit</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="number"><?= number_format($record_counts['login_attempts']) ?></div>
                        <div class="label">Login Attempts</div>
                        <small class="text-muted">Oldest: <?= $oldest_records['login_attempts'] ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="number"><?= number_format($record_counts['system_logs']) ?></div>
                        <div class="label">System Logs</div>
                        <small class="text-muted">Oldest: <?= $oldest_records['system_logs'] ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="number"><?= number_format($record_counts['applications']) ?></div>
                        <div class="label">Applications</div>
                        <small class="text-muted">Oldest: <?= $oldest_records['applications'] ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="number"><?= number_format($record_counts['users']) ?></div>
                        <div class="label">Applicant Users</div>
                        <small class="text-muted">Oldest login: <?= $oldest_records['users'] ?></small>
                    </div>
                </div>
            </div>

            <div class="cleanup-section mt-4">
                <h3><i class="fas fa-broom me-2"></i>Database Cleanup</h3>
                <p class="text-muted">Select which data to clean up and how old the records should be.</p>
                
                <form method="POST" action="">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="days_old" class="form-label">Delete records older than (days):</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="days_old" name="days_old" min="1" value="365" required>
                                <span class="input-group-text">days</span>
                            </div>
                            <small class="text-muted">Enter the minimum age (in days) for records to be deleted</small>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3"><i class="fas fa-tasks me-2"></i>Cleanup Options</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Login Attempts -->
                            <div class="cleanup-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clean_login_attempts" name="clean_login_attempts">
                                    <label class="form-check-label" for="clean_login_attempts">
                                        <i class="fas fa-sign-in-alt me-2"></i>Clean Login Attempts
                                        <span class="impact-level impact-low">Low Impact</span>
                                    </label>
                                    <div class="description">
                                        Removes failed login attempts older than specified days. This helps maintain security logs.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- System Logs -->
                            <div class="cleanup-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clean_system_logs" name="clean_system_logs">
                                    <label class="form-check-label" for="clean_system_logs">
                                        <i class="fas fa-clipboard-list me-2"></i>Clean System Logs
                                        <span class="impact-level impact-medium">Medium Impact</span>
                                    </label>
                                    <div class="description">
                                        Removes system activity logs older than specified days (preserves most recent 1000 logs or 10% of total, whichever is larger).
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Applications -->
                            <div class="cleanup-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clean_applications" name="clean_applications">
                                    <label class="form-check-label" for="clean_applications">
                                        <i class="fas fa-file-alt me-2"></i>Clean Old Applications
                                        <span class="impact-level impact-high">High Impact</span>
                                    </label>
                                    <div class="description">
                                        Removes applications and associated files older than specified days. This will delete application documents.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inactive Users -->
                            <div class="cleanup-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clean_users" name="clean_users">
                                    <label class="form-check-label" for="clean_users">
                                        <i class="fas fa-user-times me-2"></i>Clean Inactive Users
                                        <span class="impact-level impact-high">High Impact</span>
                                    </label>
                                    <div class="description">
                                        Removes applicant accounts inactive for specified days. This cannot be undone.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Force Delete Section -->
                    <div class="force-delete-section">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Force Delete Options</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="force_delete" name="force_delete">
                            <label class="form-check-label" for="force_delete">
                                <strong>Enable Force Delete</strong>
                            </label>
                            <div class="description">
                                When enabled, this will also delete all related records (orphaned application data, etc.) 
                                without preserving any records. Use with extreme caution as this cannot be undone.
                            </div>
                        </div>
                    </div>
                    
                    <div class="danger-zone">
                        <h4><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h4>
                        <p class="text-muted">These actions are irreversible. Please double-check your selections before proceeding.</p>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm_cleanup" required>
                            <label class="form-check-label" for="confirm_cleanup">
                                I understand this action cannot be undone and I have verified my selections
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i>Perform Cleanup
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Cleanup Guidelines
                </div>
                <div class="card-body">
                    <h5>Recommended Cleanup Schedule:</h5>
                    <ul class="mb-4">
                        <li><strong>Login Attempts:</strong> Clean every 30-60 days (security logs)</li>
                        <li><strong>System Logs:</strong> Clean every 90-180 days (preserves recent logs for debugging)</li>
                        <li><strong>Applications:</strong> Clean yearly (after hiring process is complete)</li>
                        <li><strong>Inactive Users:</strong> Clean accounts inactive for 1+ years</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Always backup your database before performing major cleanup operations.
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>System Logs Preservation:</strong> The system will automatically preserve the most recent 1000 logs or 10% of total logs (whichever is larger) during cleanup, unless Force Delete is enabled.
                    </div>
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-radiation me-2"></i>
                        <strong>Force Delete Warning:</strong> Enabling Force Delete will bypass all safety checks and delete ALL matching records and their relationships. This is extremely destructive and should only be used when absolutely necessary.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/superadmin/database_cleanup.js"></script>
</body>
</html>