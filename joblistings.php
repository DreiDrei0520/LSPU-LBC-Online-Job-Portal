<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user is admin or superadmin
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header('Location: home.php');
    exit;
}

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$userDb = 'root';
$pass = '';
$conn = new mysqli($host, $userDb, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin profile data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle job deletion
if (isset($_GET['delete'])) {
    $jobId = $_GET['delete'];
    
    // First delete requirements to maintain referential integrity
    $stmt = $conn->prepare("DELETE FROM position_requirements WHERE position_id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $stmt->close();
    
    // Then delete the job
    $stmt = $conn->prepare("DELETE FROM job_positions WHERE position_id = ?");
    $stmt->bind_param("i", $jobId);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Job deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Error deleting job: ' . $stmt->error;
    }
    $stmt->close();
    header('Location: joblistings.php');
    exit;
}

// Handle job creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_job'])) {
    // Sanitize and validate input
    $title = $conn->real_escape_string(trim($_POST['title']));
    $department_name = $conn->real_escape_string(trim($_POST['department_name']));
    $type = $conn->real_escape_string($_POST['type']);
    $category = $conn->real_escape_string($_POST['category']);
    $location_name = $conn->real_escape_string(trim($_POST['location_name']));
    $place_of_assignment = $conn->real_escape_string(trim($_POST['place_of_assignment']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $salary_range = $conn->real_escape_string(trim($_POST['salary_range']));
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate required fields
    if (empty($title) || empty($department_name) || empty($location_name) || empty($place_of_assignment) || empty($description)) {
        $_SESSION['error_message'] = 'Please fill in all required fields';
        header('Location: joblistings.php');
        exit;
    }

    // First, check if the department exists or create it
    $deptStmt = $conn->prepare("SELECT department_id FROM departments WHERE name = ?");
    $deptStmt->bind_param("s", $department_name);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    
    if ($deptResult->num_rows > 0) {
        $department = $deptResult->fetch_assoc();
        $departmentId = $department['department_id'];
    } else {
        // Create new department
        $insertDept = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $insertDept->bind_param("s", $department_name);
        $insertDept->execute();
        $departmentId = $conn->insert_id;
        $insertDept->close();
    }
    $deptStmt->close();

    // Check if location exists or create it
    $locStmt = $conn->prepare("SELECT location_id FROM locations WHERE name = ?");
    $locStmt->bind_param("s", $location_name);
    $locStmt->execute();
    $locResult = $locStmt->get_result();
    
    if ($locResult->num_rows > 0) {
        $location = $locResult->fetch_assoc();
        $locationId = $location['location_id'];
    } else {
        // Create new location
        $insertLoc = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
        $insertLoc->bind_param("s", $location_name);
        $insertLoc->execute();
        $locationId = $conn->insert_id;
        $insertLoc->close();
    }
    $locStmt->close();

    // Create new job
    $stmt = $conn->prepare("INSERT INTO job_positions (title, department_id, type, category, location_id, date_posted, place_of_assignment, description, salary_range, status) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)");
    $stmt->bind_param("sississss", $title, $departmentId, $type, $category, $locationId, $place_of_assignment, $description, $salary_range, $status);
    
    if ($stmt->execute()) {
        $positionId = $stmt->insert_id;
        
        // Handle requirements
        $requirementTypes = ['eligibility', 'qualification', 'experience', 'training'];
        $stmtReq = $conn->prepare("INSERT INTO position_requirements (position_id, requirement_type, description) VALUES (?, ?, ?)");
        
        foreach ($requirementTypes as $type) {
            if (!empty($_POST[$type])) {
                $description = $conn->real_escape_string(trim($_POST[$type]));
                $stmtReq->bind_param("iss", $positionId, $type, $description);
                $stmtReq->execute();
            }
        }
        $stmtReq->close();
        
        $_SESSION['success_message'] = 'Job created successfully!';
    } else {
        $_SESSION['error_message'] = 'Error creating job: ' . $stmt->error;
    }
    $stmt->close();
    header('Location: joblistings.php');
    exit;
}

// Handle job editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    // Sanitize and validate input
    $jobId = intval($_POST['job_id']);
    $title = $conn->real_escape_string(trim($_POST['title']));
    $department_name = $conn->real_escape_string(trim($_POST['department_name']));
    $type = $conn->real_escape_string($_POST['type']);
    $category = $conn->real_escape_string($_POST['category']);
    $location_name = $conn->real_escape_string(trim($_POST['location_name']));
    $place_of_assignment = $conn->real_escape_string(trim($_POST['place_of_assignment']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $salary_range = $conn->real_escape_string(trim($_POST['salary_range']));
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate required fields
    if (empty($title) || empty($department_name) || empty($location_name) || empty($place_of_assignment) || empty($description)) {
        $_SESSION['error_message'] = 'Please fill in all required fields';
        header('Location: joblistings.php');
        exit;
    }

    // First, check if the department exists or create it
    $deptStmt = $conn->prepare("SELECT department_id FROM departments WHERE name = ?");
    $deptStmt->bind_param("s", $department_name);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    
    if ($deptResult->num_rows > 0) {
        $department = $deptResult->fetch_assoc();
        $departmentId = $department['department_id'];
    } else {
        // Create new department
        $insertDept = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $insertDept->bind_param("s", $department_name);
        $insertDept->execute();
        $departmentId = $conn->insert_id;
        $insertDept->close();
    }
    $deptStmt->close();

    // Check if location exists or create it
    $locStmt = $conn->prepare("SELECT location_id FROM locations WHERE name = ?");
    $locStmt->bind_param("s", $location_name);
    $locStmt->execute();
    $locResult = $locStmt->get_result();
    
    if ($locResult->num_rows > 0) {
        $location = $locResult->fetch_assoc();
        $locationId = $location['location_id'];
    } else {
        // Create new location
        $insertLoc = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
        $insertLoc->bind_param("s", $location_name);
        $insertLoc->execute();
        $locationId = $conn->insert_id;
        $insertLoc->close();
    }
    $locStmt->close();

    // Update existing job
    $stmt = $conn->prepare("UPDATE job_positions SET title=?, department_id=?, type=?, category=?, location_id=?, place_of_assignment=?, description=?, salary_range=?, status=? WHERE position_id=?");
    $stmt->bind_param("sississssi", $title, $departmentId, $type, $category, $locationId, $place_of_assignment, $description, $salary_range, $status, $jobId);
    
    if ($stmt->execute()) {
        // Handle requirements - first delete existing ones
        $conn->query("DELETE FROM position_requirements WHERE position_id = $jobId");
        
        // Then insert new ones
        $requirementTypes = ['eligibility', 'qualification', 'experience', 'training'];
        $stmtReq = $conn->prepare("INSERT INTO position_requirements (position_id, requirement_type, description) VALUES (?, ?, ?)");
        
        foreach ($requirementTypes as $type) {
            if (!empty($_POST[$type])) {
                $description = $conn->real_escape_string(trim($_POST[$type]));
                $stmtReq->bind_param("iss", $jobId, $type, $description);
                $stmtReq->execute();
            }
        }
        $stmtReq->close();
        
        $_SESSION['success_message'] = 'Job updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Error updating job: ' . $stmt->error;
    }
    $stmt->close();
    header('Location: joblistings.php');
    exit;
}

// Fetch job for editing
$editJob = null;
$editRequirements = [];
if (isset($_GET['edit'])) {
    $jobId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT jp.*, d.name AS department_name, l.name AS location_name 
                          FROM job_positions jp 
                          JOIN departments d ON jp.department_id = d.department_id
                          JOIN locations l ON jp.location_id = l.location_id
                          WHERE jp.position_id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editJob = $result->fetch_assoc();
    $stmt->close();
    
    if ($editJob) {
        // Get requirements for this job
        $reqStmt = $conn->prepare("SELECT requirement_type, description FROM position_requirements WHERE position_id = ?");
        $reqStmt->bind_param("i", $jobId);
        $reqStmt->execute();
        $reqResult = $reqStmt->get_result();
        while ($req = $reqResult->fetch_assoc()) {
            $editRequirements[$req['requirement_type']] = $req['description'];
        }
        $reqStmt->close();
    }
}

// Fetch job listings with department and location names
$sql = "SELECT jp.*, d.name AS department_name, l.name AS location_name 
        FROM job_positions jp
        JOIN departments d ON jp.department_id = d.department_id
        JOIN locations l ON jp.location_id = l.location_id
        ORDER BY jp.date_posted DESC";
$result = $conn->query($sql);

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
    header('Location: joblistings.php');
    exit;
}

// Notification system
$notifications = [];
$unreadCount = 0;

// Get new pending applications for notifications
$newPendingQuery = "SELECT a.*, u.first_name, u.last_name, jp.title as position_title 
                   FROM applications a
                   JOIN users u ON a.user_id = u.user_id
                   JOIN job_positions jp ON a.position_id = jp.position_id
                   WHERE a.status = 'Pending' AND a.submitted_at >= NOW() - INTERVAL 1 DAY
                   ORDER BY a.submitted_at DESC";
$newPendingResult = $conn->query($newPendingQuery);
$newPendingApps = $newPendingResult->fetch_all(MYSQLI_ASSOC);

foreach ($newPendingApps as $app) {
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
        'message' => 'New pending application from ' . $app['first_name'] . ' ' . $app['last_name'] . ' for ' . $app['position_title'],
        'time' => $timeAgo,
        'unread' => !isset($_SESSION['notifications_read'])
    ];
}

// Count unread notifications
$unreadCount = array_reduce($notifications, function($carry, $item) {
    return $carry + ($item['unread'] ? 1 : 0);
}, 0);

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);

// Check if sidebar should be collapsed from session
$sidebarCollapsed = $_SESSION['sidebar_collapsed'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Listings | Admin Dashboard</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- AOS (Animate On Scroll) -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/admin/joblistings.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar animate-slide-up">
            <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar-title">
                <h1>Job Listings Management</h1>
                <p>Manage and post new job opportunities</p>
            </div>

            <div class="topbar-actions">
                <!-- Notification Dropdown -->
                <div class="dropdown">
                    <button class="notification-btn dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown">
                        <div class="notification-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Notifications</h3>
                            <form method="POST" action="">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-link text-primary p-0">Mark All as Read</button>
                            </form>
                        </div>

                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $note): ?>
                                <div class="notification-item <?= $note['unread'] ? 'unread' : '' ?>">
                                    <div class="notification-icon">
                                        <i class="fas fa-<?= $note['icon'] ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p><?= htmlspecialchars($note['message']) ?></p>
                                        <span class="notification-time">
                                            <i class="far fa-clock me-1"></i> <?= $note['time'] ?>
                                        </span>
                                    </div>
                                    <?php if ($note['unread']): ?>
                                        <span class="unread-badge"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notification-item text-center py-3">
                                <p class="text-muted mb-0">No new notifications</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <button class="profile-btn dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($hasProfilePic): ?>
                            <img src="<?= htmlspecialchars($picsPath) ?>" class="profile-img" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle fs-5"></i>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end profile-menu" aria-labelledby="profileDropdown">
                        <a href="admin_settings.php" class="dropdown-item">
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
<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success animate-slide-up delay-1">
    <?= $_SESSION['success_message'] ?>
    <?php unset($_SESSION['success_message']); ?>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-error animate-slide-up delay-1">
    <?= $_SESSION['error_message'] ?>
    <?php unset($_SESSION['error_message']); ?>
  </div>
<?php endif; ?>

<!-- Job Listings Card -->
<div class="job-listings-card animate-slide-up delay-2">
  <div class="job-listings-header">
    <h2 class="job-listings-title">Current Job Openings</h2>
    <button type="button" class="add-job-btn animate-slide-up delay-3" data-bs-toggle="modal" data-bs-target="#createJobModal">
      <i class="fas fa-plus"></i>
      <span>Post New Job</span>
    </button>
  </div>
  
  <?php if ($result->num_rows > 0): ?>
    <?php while ($job = $result->fetch_assoc()): ?>
      <div class="job-card animate-fade-in">
        <h3 class="job-title"><?= htmlspecialchars($job['title']) ?></h3>
        <div class="job-meta">
          <span class="job-meta-item">
            <i class="fas fa-building"></i>
            <?= htmlspecialchars($job['department_name']) ?>
          </span>
          <span class="job-meta-item">
            <i class="fas fa-clock"></i>
            <?= htmlspecialchars($job['type']) ?>
          </span>
          <span class="job-meta-item">
            <i class="fas fa-map-marker-alt"></i>
            <?= htmlspecialchars($job['location_name']) ?>
          </span>
          <span class="job-meta-item">
            <i class="fas fa-calendar-alt"></i>
            Posted on <?= htmlspecialchars(date("M j, Y", strtotime($job['date_posted']))) ?>
          </span>
          <span class="job-meta-item">
            <i class="fas fa-money-bill-wave"></i>
            <?= htmlspecialchars($job['salary_range'] ?? 'Not specified') ?>
          </span>
        </div>
        <div class="job-actions">
          <button class="job-btn edit-btn" onclick="openEditModal(<?= $job['position_id'] ?>)">
            <i class="fas fa-edit"></i>
            Edit
          </button>
          <a href="?delete=<?= $job['position_id'] ?>" class="job-btn delete-btn" onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
            <i class="fas fa-trash-alt"></i>
            Delete
          </a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="no-jobs animate-fade-in">
      <div class="no-jobs-icon">
        <i class="fas fa-briefcase"></i>
      </div>
      <h3>No Job Listings Available</h3>
      <p>You haven't posted any jobs yet. Click the button above to create your first job listing.</p>
    </div>
  <?php endif; ?>
</div>
</div>

<!-- Create Job Modal -->
<div class="modal fade job-modal" id="createJobModal" tabindex="-1" aria-labelledby="createJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="createJobModalLabel">Create New Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_title" class="form-label">
                                Job Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="create_title" name="title" required>
                        </div>

                        <div class="col-md-6">
                            <label for="create_department_name" class="form-label">
                                Department Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="create_department_name" name="department_name" required>
                        </div>
                    </div>
 <div class="row mb-3">
          <div class="col-md-6">
            <label for="create_type" class="form-label">Job Type <span class="text-danger">*</span></label>
            <select class="form-select" id="create_type" name="type" required>
              <option value="Full-Time">Full-Time</option>
              <option value="Part-Time">Part-Time</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="create_category" class="form-label">Category <span class="text-danger">*</span></label>
            <select class="form-select" id="create_category" name="category" required>
              <option value="Teaching">Teaching</option>
              <option value="Non-Teaching">Non-Teaching</option>
            </select>
          </div>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="create_location_name" class="form-label">Location <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="create_location_name" name="location_name" required>
          </div>
          <div class="col-md-6">
            <label for="create_place_of_assignment" class="form-label">Place of Assignment <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="create_place_of_assignment" name="place_of_assignment" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="create_description" class="form-label">Job Description <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="create_description" name="description" rows="5" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="create_salary_range" class="form-label">Salary Range</label>
          <input type="text" class="form-control" id="create_salary_range" name="salary_range" placeholder="Example: PHP 30,000 - 45,000">
        </div>
        
        <div class="mb-3">
          <label for="create_eligibility" class="form-label">Eligibility Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="create_eligibility" name="eligibility" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="create_qualification" class="form-label">Qualifications <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="create_qualification" name="qualification" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="create_experience" class="form-label">Experience Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="create_experience" name="experience" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="create_training" class="form-label">Training Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="create_training" name="training" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="create_status" class="form-label">Status <span class="text-danger">*</span></label>
          <select class="form-select" id="create_status" name="status" required>
            <option value="Open" selected>Open</option>
            <option value="Closed">Closed</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="create_job" class="btn btn-primary btn-save">Save Job</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- Edit Job Modal -->
<div class="modal fade job-modal" id="editJobModal" tabindex="-1" aria-labelledby="editJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editJobModalLabel">Edit Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="job_id" id="edit_job_id">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="edit_title" class="form-label">Job Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_title" name="title" required>
          </div>
          <div class="col-md-6">
            <label for="edit_department_name" class="form-label">Department Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_department_name" name="department_name" required>
          </div>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="edit_type" class="form-label">Job Type <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_type" name="type" required>
              <option value="Full-Time">Full-Time</option>
              <option value="Part-Time">Part-Time</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="edit_category" class="form-label">Category <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_category" name="category" required>
              <option value="Teaching">Teaching</option>
              <option value="Non-Teaching">Non-Teaching</option>
            </select>
          </div>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="edit_location_name" class="form-label">Location <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_location_name" name="location_name" required>
          </div>
          <div class="col-md-6">
            <label for="edit_place_of_assignment" class="form-label">Place of Assignment <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_place_of_assignment" name="place_of_assignment" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="edit_description" class="form-label">Job Description <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="edit_description" name="description" rows="5" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="edit_salary_range" class="form-label">Salary Range</label>
          <input type="text" class="form-control" id="edit_salary_range" name="salary_range" placeholder="Example: PHP 30,000 - 45,000">
        </div>
        
        <div class="mb-3">
          <label for="edit_eligibility" class="form-label">Eligibility Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="edit_eligibility" name="eligibility" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="edit_qualification" class="form-label">Qualifications <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="edit_qualification" name="qualification" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="edit_experience" class="form-label">Experience Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="edit_experience" name="experience" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="edit_training" class="form-label">Training Requirements <span class="text-danger">*</span></label>
          <textarea class="form-control form-textarea" id="edit_training" name="training" rows="3" required></textarea>
        </div>
        
        <div class="mb-3">
          <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
          <select class="form-select" id="edit_status" name="status" required>
            <option value="Open">Open</option>
            <option value="Closed">Closed</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="update_job" class="btn btn-primary btn-save">Update Job</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS (Animate On Scroll) -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Custom JS -->
<script src="assets/js/admin/joblistings.js"></script>

</body>
</html>
