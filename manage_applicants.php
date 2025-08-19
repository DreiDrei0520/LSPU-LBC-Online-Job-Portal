<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user details for the sidebar/profile
$userId = $_SESSION['user_id'];
$user = null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Handle form submissions
$message = '';
$messageType = '';

// Add new applicant
if (isset($_POST['add_applicant'])) {
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $birthdate = $_POST['birthdate'] ?? null;
    $phone = trim($_POST['phone'] ?? null);
    
    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields!";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long!";
        $messageType = "danger";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Email already exists in the system!";
            $messageType = "danger";
        } else {
            try {
                $pdo->beginTransaction();
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, birthdate, phone, role) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 'applicant')");
                $stmt->execute([$firstName, $middleName, $lastName, $email, $hashedPassword, $birthdate, $phone]);
                
                // Log the action
                $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, affected_table, record_id) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $userId,
                    'Applicant Added',
                    "Superadmin created new applicant account for $email",
                    $_SERVER['REMOTE_ADDR'],
                    'users',
                    $pdo->lastInsertId()
                ]);
                
                $pdo->commit();
                
                $message = "Applicant account created successfully!";
                $messageType = "success";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error creating applicant account: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

// Update applicant
if (isset($_POST['update_applicant'])) {
    $applicantId = $_POST['applicant_id'];
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $birthdate = $_POST['birthdate'] ?? null;
    $phone = trim($_POST['phone'] ?? null);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $message = "Please fill in all required fields!";
        $messageType = "danger";
    } elseif (!empty($password) && strlen($password) < 8) {
        $message = "Password must be at least 8 characters long!";
        $messageType = "danger";
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $applicantId]);
        if ($stmt->fetch()) {
            $message = "Email already exists for another user!";
            $messageType = "danger";
        } else {
            try {
                $pdo->beginTransaction();
                
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, 
                                          birthdate = ?, phone = ?, password = ?, updated_at = CURRENT_TIMESTAMP 
                                          WHERE user_id = ?");
                    $stmt->execute([$firstName, $middleName, $lastName, $email, $birthdate, $phone, $hashedPassword, $applicantId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, 
                                          birthdate = ?, phone = ?, updated_at = CURRENT_TIMESTAMP 
                                          WHERE user_id = ?");
                    $stmt->execute([$firstName, $middleName, $lastName, $email, $birthdate, $phone, $applicantId]);
                }
                
                // Log the action
                $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, affected_table, record_id) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $userId,
                    'Applicant Updated',
                    "Superadmin updated applicant account ID $applicantId",
                    $_SERVER['REMOTE_ADDR'],
                    'users',
                    $applicantId
                ]);
                
                $pdo->commit();
                
                $message = "Applicant account updated successfully!";
                $messageType = "success";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error updating applicant account: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

// Toggle applicant status
if (isset($_GET['toggle_status'])) {
    $applicantId = $_GET['toggle_status'];
    
    try {
        $pdo->beginTransaction();
        
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE user_id = ?");
        $stmt->execute([$applicantId]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $applicantId]);
        
        // Log the action
        $statusText = $newStatus ? 'activated' : 'deactivated';
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, affected_table, record_id) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
        $logStmt->execute([
            $userId,
            'Applicant Status Changed',
            "Superadmin $statusText applicant account ID $applicantId",
            $_SERVER['REMOTE_ADDR'],
            'users',
            $applicantId
        ]);
        
        $pdo->commit();
        
        $message = "Applicant account $statusText successfully!";
        $messageType = "success";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Error updating applicant status: " . $e->getMessage();
        $messageType = "danger";
    }
    
    // Redirect to avoid resubmission
    header("Location: manage_applicants.php?message=" . urlencode($message) . "&type=$messageType");
    exit;
}

// Delete applicant
if (isset($_GET['delete_applicant'])) {
    $applicantId = $_GET['delete_applicant'];
    
    try {
        $pdo->beginTransaction();
        
        // First, delete related records to maintain referential integrity
        $pdo->prepare("DELETE FROM applications WHERE user_id = ?")->execute([$applicantId]);
        $pdo->prepare("DELETE FROM application_education WHERE application_id IN (SELECT application_id FROM applications WHERE user_id = ?)")->execute([$applicantId]);
        $pdo->prepare("DELETE FROM application_work_experience WHERE application_id IN (SELECT application_id FROM applications WHERE user_id = ?)")->execute([$applicantId]);
        
        // Then delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'applicant'");
        $stmt->execute([$applicantId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the action
            $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, affected_table, record_id) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
            $logStmt->execute([
                $userId,
                'Applicant Deleted',
                "Superadmin deleted applicant account ID $applicantId",
                $_SERVER['REMOTE_ADDR'],
                'users',
                $applicantId
            ]);
            
            $pdo->commit();
            
            $message = "Applicant account deleted successfully!";
            $messageType = "success";
        } else {
            $pdo->rollBack();
            $message = "Error deleting applicant account or account not found";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Error deleting applicant account: " . $e->getMessage();
        $messageType = "danger";
    }
    
    // Redirect to avoid resubmission
    header("Location: manage_applicants.php?message=" . urlencode($message) . "&type=$messageType");
    exit;
}

// Get all applicants with their application count
$applicants = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT u.*, 
       (SELECT COUNT(*) FROM applications a WHERE a.user_id = u.user_id) as application_count
       FROM users u 
       WHERE u.role = 'applicant'";

$params = [];

// Add search condition if search term exists
if (!empty($searchTerm)) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Add status filter if not 'all'
if ($statusFilter !== 'all') {
    $statusValue = $statusFilter === 'active' ? 1 : 0;
    $sql .= " AND u.is_active = ?";
    $params[] = $statusValue;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applicants = $stmt->fetchAll();

// Get notification count
$unreadCount = 0;
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
if ($stmt) {
    $unreadCount = $stmt->fetch()['count'];
}

// Mark notifications as read if requested
if (isset($_POST['mark_all_read'])) {
    $pdo->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    $_SESSION['notifications_read'] = true;
    header('Location: manage_applicants.php');
    exit;
}

// Handle messages from redirects
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicPath = !empty($user['profile_pic']) ? 'uploads/profile_pics/' . $user['profile_pic'] : $defaultProfilePic;
$hasProfilePic = file_exists($profilePicPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Applicants | LSPU Job Portal</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/superadmin/manage_applicants.css">
</head>
<body>
  <?php include 'superadmin_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Manage Applicants <span class="superadmin-badge">SUPERADMIN</span></h1>
        <p>Create, update, and delete applicant accounts</p>
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
            <?php if ($unreadCount > 0): ?>
              <div style="max-height: 400px; overflow-y: auto;">
                <?php 
                $notifications = $pdo->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
                while ($note = $notifications->fetch()): ?>
                  <div class="notification-item <?= $note['is_read'] == 0 ? 'unread' : '' ?>">
                    <div class="notification-icon">
                      <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="notification-content">
                      <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                      <span class="notification-time">
                        <i class="far fa-clock me-1"></i> <?= date('M j, Y', strtotime($note['created_at'])) ?>
                      </span>
                    </div>
                    <?php if ($note['is_read'] == 0): ?>
                      <span class="unread-badge"></span>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
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
              <img src="<?= htmlspecialchars($profilePicPath) ?>" class="profile-img rounded-circle" alt="Profile" style="width: 36px; height: 36px; object-fit: cover;">
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

    <!-- Display success/error messages -->
    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= $messageType ?> alert-dismissible fade show alert-fixed" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Main Content Area -->
    <div class="container-fluid mt-4">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-users me-2"></i>Applicant Accounts</h5>
          <div class="d-flex">
            <form method="GET" action="manage_applicants.php" class="d-flex align-items-center me-3">
              <div class="input-group search-box">
                <input type="text" name="search" class="form-control" placeholder="Search applicants..." 
                       value="<?= htmlspecialchars($searchTerm) ?>">
                <button class="btn btn-outline-secondary" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($searchTerm)): ?>
                  <a href="manage_applicants.php" class="btn btn-outline-secondary ms-1">
                    <i class="fas fa-times"></i>
                  </a>
                <?php endif; ?>
              </div>
            </form>
            <div class="dropdown filter-dropdown me-3">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" 
                      data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-filter me-1"></i>
                <?= ucfirst($statusFilter) ?>
              </button>
              <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
                <li><a class="dropdown-item <?= $statusFilter === 'all' ? 'active' : '' ?>" 
                       href="manage_applicants.php?status=all<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">All</a></li>
                <li><a class="dropdown-item <?= $statusFilter === 'active' ? 'active' : '' ?>" 
                       href="manage_applicants.php?status=active<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">Active</a></li>
                <li><a class="dropdown-item <?= $statusFilter === 'inactive' ? 'active' : '' ?>" 
                       href="manage_applicants.php?status=inactive<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">Inactive</a></li>
              </ul>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApplicantModal">
              <i class="fas fa-plus-circle me-1"></i> Add Applicant
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover" id="applicantsTable">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Applications</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($applicants as $applicant): 
                  $fullName = htmlspecialchars($applicant['first_name'] . ' ' . ($applicant['middle_name'] ? $applicant['middle_name'] . ' ' : '') . $applicant['last_name']);
                  $profilePic = !empty($applicant['profile_pic']) ? 'uploads/profile_pics/' . $applicant['profile_pic'] : 'uploads/profile_pics/default.jpg';
                  $hasPic = file_exists($profilePic);
                ?>
                  <tr>
                    <td><?= htmlspecialchars($applicant['user_id']) ?></td>
                    <td>
                      <img src="<?= $hasPic ? $profilePic : 'uploads/profile_pics/default.jpg' ?>" 
                           class="user-avatar" alt="Profile">
                    </td>
                    <td>
                      <div><?= $fullName ?></div>
                      <div class="applicant-details">
                        <?= !empty($applicant['phone']) ? htmlspecialchars($applicant['phone']) : 'No phone' ?>
                        <?php if (!empty($applicant['birthdate'])): ?>
                          <div><?= date('M j, Y', strtotime($applicant['birthdate'])) ?> (<?= date_diff(date_create($applicant['birthdate']), date_create('today'))->y ?> years)</div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($applicant['email']) ?></td>
                    <td>
                      <span class="badge applicant-badge">
                        Applicant
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $applicant['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $applicant['is_active'] ? 'Active' : 'Inactive' ?>
                      </span>
                    </td>
                    <td>
                      <span class="applications-count">
                        <?= $applicant['application_count'] ?> application(s)
                      </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($applicant['created_at'])) ?></td>
                    <td class="action-btns">
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editApplicantModal"
                                data-id="<?= $applicant['user_id'] ?>"
                                data-first-name="<?= htmlspecialchars($applicant['first_name']) ?>"
                                data-middle-name="<?= htmlspecialchars($applicant['middle_name'] ?? '') ?>"
                                data-last-name="<?= htmlspecialchars($applicant['last_name']) ?>"
                                data-email="<?= htmlspecialchars($applicant['email']) ?>"
                                data-birthdate="<?= htmlspecialchars($applicant['birthdate'] ?? '') ?>"
                                data-phone="<?= htmlspecialchars($applicant['phone'] ?? '') ?>"
                                data-is-active="<?= $applicant['is_active'] ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <a href="manage_applicants.php?toggle_status=<?= $applicant['user_id'] ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?><?= $statusFilter !== 'all' ? '&status=' . $statusFilter : '' ?>" 
                           class="btn btn-sm <?= $applicant['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                           title="<?= $applicant['is_active'] ? 'Deactivate' : 'Activate' ?>"
                           onclick="return confirm('Are you sure you want to <?= $applicant['is_active'] ? 'deactivate' : 'activate' ?> this applicant account?')">
                          <i class="fas <?= $applicant['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </a>
                        <a href="manage_applicants.php?delete_applicant=<?= $applicant['user_id'] ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?><?= $statusFilter !== 'all' ? '&status=' . $statusFilter : '' ?>" 
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Are you sure you want to delete this applicant account and all associated data?')">
                          <i class="fas fa-trash-alt"></i>
                        </a>
                        <a href="view_applicant.php?id=<?= $applicant['user_id'] ?>" 
                           class="btn btn-sm btn-outline-info"
                           title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($applicants)): ?>
                  <tr>
                    <td colspan="9" class="text-center py-4">
                      <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                      <p class="text-muted">No applicant accounts found</p>
                      <?php if (!empty($searchTerm) || $statusFilter !== 'all'): ?>
                        <a href="manage_applicants.php" class="btn btn-sm btn-outline-primary">
                          Clear filters
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination would go here if implemented -->
          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="pagination-info">
              Showing <?= count($applicants) ?> of <?= count($applicants) ?> applicants
            </div>
            <!-- Pagination links would go here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Applicant Modal -->
  <div class="modal fade" id="addApplicantModal" tabindex="-1" aria-labelledby="addApplicantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addApplicantModalLabel">
            <i class="fas fa-user-plus me-2"></i>Add New Applicant
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="manage_applicants.php" id="addApplicantForm">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name">
              </div>
            </div>
            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="birthdate" class="form-label">Birthdate</label>
                <input type="date" class="form-control datepicker" id="birthdate" name="birthdate" max="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10,15}" title="Phone number should be 10-15 digits">
              </div>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password" name="password" required minlength="8">
              <div class="form-text">Minimum 8 characters</div>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_applicant" class="btn btn-primary" id="addApplicantBtn">
              <span id="addApplicantBtnText">Add Applicant</span>
              <div id="addApplicantSpinner" class="loading-spinner d-none"></div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Applicant Modal -->
  <div class="modal fade" id="editApplicantModal" tabindex="-1" aria-labelledby="editApplicantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editApplicantModalLabel">
            <i class="fas fa-user-edit me-2"></i>Edit Applicant
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="manage_applicants.php" id="editApplicantForm">
          <input type="hidden" id="edit_applicant_id" name="applicant_id">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
              </div>
            </div>
            <div class="mb-3">
              <label for="edit_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_birthdate" class="form-label">Birthdate</label>
                <input type="date" class="form-control datepicker" id="edit_birthdate" name="birthdate" max="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_phone" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="edit_phone" name="phone" pattern="[0-9]{10,15}" title="Phone number should be 10-15 digits">
              </div>
            </div>
            <div class="mb-3">
              <label for="edit_password" class="form-label">New Password</label>
              <input type="password" class="form-control" id="edit_password" name="password" minlength="8">
              <div class="form-text">Leave blank to keep current password (minimum 8 characters if changing)</div>
            </div>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                <label class="form-check-label" for="edit_is_active">Account Active</label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_applicant" class="btn btn-primary" id="updateApplicantBtn">
              <span id="updateApplicantBtnText">Update Applicant</span>
              <div id="updateApplicantSpinner" class="loading-spinner d-none"></div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/superadmin/manage_applicants.js"></script>
</body>
</html>