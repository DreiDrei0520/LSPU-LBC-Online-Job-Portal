<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

// Handle export to Excel
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="hired_not_selected_applicants_' . date('Y-m-d') . '.xls"');
    
    $output = '<html>';
    $output .= '<head>';
    $output .= '<style>';
    $output .= 'body { font-family: Arial, sans-serif; }';
    $output .= 'table { border-collapse: collapse; width: 100%; }';
    $output .= 'th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }';
    $output .= 'th { background-color: #f2f2f2; font-weight: bold; }';
    $output .= '.logo { text-align: center; margin-bottom: 10px; }';
    $output .= '.header { text-align: center; margin-bottom: 20px; }';
    $output .= '.university { font-weight: bold; font-size: 16px; }';
    $output .= '.campus { font-weight: bold; }';
    $output .= '.report-title { font-weight: bold; font-size: 14px; margin-top: 10px; }';
    $output .= '.generated-date { text-align: right; font-style: italic; margin-bottom: 10px; }';
    $output .= '</style>';
    $output .= '</head>';
    $output .= '<body>';
    
    // Add header with LSPU logo and information
    $output .= '<div class="logo">';
    $output .= '<img src="lspulogo.png" alt="LSPU Logo" width="80" height="80">';
    $output .= '</div>';
    
    $output .= '<div class="header">';
    $output .= '<h2>Republic of the Philippines</h2>';
    $output .= '<div class="university">Laguna State Polytechnic University</div>';
    $output .= '<div>Province of Laguna</div>';
    $output .= '<div class="campus">Los Baños Campus</div>';
    $output .= '<div style="margin-top: 15px; border-top: 1px solid #000; width: 100%;"></div>';
    $output .= '<div class="report-title">Hired & Not Selected Applicants Report</div>';
    $output .= '</div>';
    
    $output .= '<div class="generated-date">Generated on: ' . date('F j, Y') . '</div>';
    
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th>Application Number</th>';
    $output .= '<th>Applicant Name</th>';
    $output .= '<th>Position</th>';
    $output .= '<th>Date Applied</th>';
    $output .= '<th>Status</th>';
    $output .= '</tr>';
    
    // Fetch all applications for export
    $exportQuery = "
        SELECT a.application_number, 
               CONCAT(u.first_name, ' ', u.last_name) as applicant_name, 
               jp.title as position, 
               a.submitted_at, 
               a.status
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN job_positions jp ON a.position_id = jp.position_id
        WHERE a.status IN ('Hired', 'Not Selected')
        ORDER BY a.submitted_at DESC";
    $exportResult = $conn->query($exportQuery);
    
    while ($row = $exportResult->fetch_assoc()) {
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($row['application_number']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['applicant_name']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['position']) . '</td>';
        $output .= '<td>' . htmlspecialchars(date("F j, Y", strtotime($row['submitted_at']))) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['status']) . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</table>';
    $output .= '</body></html>';
    
    echo $output;
    exit;
}

// Get filter values
$positionFilter = isset($_GET['position']) ? (int)$_GET['position'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build base query
$query = "
    SELECT a.*, 
           u.first_name, u.last_name, u.email, u.birthdate, u.profile_pic,
           jp.title AS position_title, 
           d.name AS department_name,
           l.name AS location_name
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions jp ON a.position_id = jp.position_id
    JOIN departments d ON jp.department_id = d.department_id
    JOIN locations l ON jp.location_id = l.location_id
    WHERE a.status IN ('Hired', 'Not Selected')
";

// Add filters to query
if (!empty($positionFilter)) {
    $query .= " AND a.position_id = " . $positionFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND a.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$query .= " ORDER BY a.submitted_at DESC";

// Fetch applicants with filters
$applicants = $conn->query($query);

// Get distinct positions for filter dropdown
$positionsQuery = "
    SELECT jp.position_id, jp.title 
    FROM applications a
    JOIN job_positions jp ON a.position_id = jp.position_id
    WHERE a.status IN ('Hired', 'Not Selected')
    GROUP BY jp.position_id, jp.title
    ORDER BY jp.title";
$positionsResult = $conn->query($positionsQuery);
$positions = [];
while ($row = $positionsResult->fetch_assoc()) {
    $positions[] = $row;
}

// Status badge styling
function statusBadge($status) {
    $statusClass = strtolower(str_replace(' ', '-', $status));
    return "status-badge status-$statusClass";
}

// Get application details for modal if requested
$applicationDetails = null;
$educationDetails = [];
$workExperienceDetails = [];
$positionRequirements = [];
if (isset($_GET['view_id'])) {
    $viewId = (int)$_GET['view_id'];
    
    // Main application details
    $stmt = $conn->prepare("
        SELECT a.*, 
               u.first_name, u.middle_name, u.last_name, u.email, u.phone, u.birthdate, u.profile_pic,
               jp.title AS position_title, jp.type, jp.category, jp.salary_range, jp.description AS position_description,
               d.name AS department_name,
               l.name AS location_name, l.address AS location_address
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN job_positions jp ON a.position_id = jp.position_id
        JOIN departments d ON jp.department_id = d.department_id
        JOIN locations l ON jp.location_id = l.location_id
        WHERE a.application_id = ?
    ");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicationDetails = $result->fetch_assoc();
    $stmt->close();
    
    // Education details
    $eduStmt = $conn->prepare("SELECT * FROM application_education WHERE application_id = ?");
    $eduStmt->bind_param("i", $viewId);
    $eduStmt->execute();
    $eduResult = $eduStmt->get_result();
    $educationDetails = $eduResult->fetch_all(MYSQLI_ASSOC);
    $eduStmt->close();
    
    // Work experience details
    $expStmt = $conn->prepare("SELECT * FROM application_work_experience WHERE application_id = ?");
    $expStmt->bind_param("i", $viewId);
    $expStmt->execute();
    $expResult = $expStmt->get_result();
    $workExperienceDetails = $expResult->fetch_all(MYSQLI_ASSOC);
    $expStmt->close();
    
    // Position requirements
    $reqStmt = $conn->prepare("
        SELECT * FROM position_requirements 
        WHERE position_id = ?
        ORDER BY FIELD(requirement_type, 'eligibility', 'qualification', 'experience', 'training')
    ");
    $reqStmt->bind_param("i", $applicationDetails['position_id']);
    $reqStmt->execute();
    $reqResult = $reqStmt->get_result();
    $positionRequirements = $reqResult->fetch_all(MYSQLI_ASSOC);
    $reqStmt->close();
}

// Notification system
$notifications = [];
$unreadCount = 0;

// Get new pending applications for notifications
$newPendingQuery = "SELECT a.*, u.first_name, u.last_name, jp.title AS position_title 
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
$profilePicPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($profilePicPath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hired & Not Selected Applicants | Job Portal</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/admin/list.css">
</head>
<body>
  <!-- Include Sidebar -->
  <?php include 'admin_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Hired & Not Selected Applicants</h1>
        <p>Manage all finalized applications</p>
      </div>
      <div class="topbar-actions">
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
        <div class="dropdown">
          <button class="profile-btn dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($hasProfilePic): ?>
              <img src="<?= htmlspecialchars($profilePicPath) ?>" class="profile-img" alt="Profile">
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
      <div class="alert alert-success">
        <?= $_SESSION['success_message'] ?>
        <?php unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-error">
        <?= $_SESSION['error_message'] ?>
        <?php unset($_SESSION['error_message']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning_message'])): ?>
      <div class="alert alert-warning">
        <?= $_SESSION['warning_message'] ?>
        <?php unset($_SESSION['warning_message']); ?>
      </div>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <div class="search-filter-container">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <form method="GET" action="">
          <input type="text" name="search" class="form-control" placeholder="Search by name, position or application number..." 
                 value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </form>
      </div>
      
      <div class="sort-export-container">
        <form method="GET" action="" class="d-flex gap-2">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="Hired" <?= ($statusFilter ?? '') === 'Hired' ? 'selected' : '' ?>>Hired</option>
            <option value="Not Selected" <?= ($statusFilter ?? '') === 'Not Selected' ? 'selected' : '' ?>>Not Selected</option>
          </select>
          
          <select name="position" class="form-select">
            <option value="">All Positions</option>
            <?php foreach ($positions as $position): ?>
              <option value="<?= $position['position_id'] ?>" <?= ($positionFilter ?? '') == $position['position_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($position['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
          </button>
        </form>
        
        <form method="POST" action="">
          <button type="submit" name="export_excel" class="export-btn">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
          </button>
        </form>
      </div>
    </div>

    <!-- Applications Table -->
    <div class="table-card">
      <h3>All Applications</h3>
      <p class="table-subtitle">Showing <?= $applicants->num_rows ?> applications</p>
      
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Application No.</th>    
              <th>Position</th>
              <th>Date Applied</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($applicants->num_rows > 0): ?>
              <?php while($row = $applicants->fetch_assoc()): 
                $fullName = htmlspecialchars(trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']));
                $email = htmlspecialchars($row['email']);
                $application_number = htmlspecialchars($row['application_number']);
                $position = htmlspecialchars($row['position_title']);
                $appliedDate = date("M d, Y", strtotime($row['submitted_at']));
                $badgeClass = statusBadge($row['status']);

                $profilePic = !empty($row['profile_pic']) ? 'uploads/profile_pics/' . $row['profile_pic'] : 'uploads/profile_pics/default.jpg';
              ?>
              <tr>
                <td>
                  <div class="applicant-info">
                    <img src="<?= htmlspecialchars($profilePic) ?>" class="applicant-img" alt="Applicant">
                    <div>
                      <div class="applicant-name"><?= $fullName ?></div>
                      <div class="applicant-email"><?= $email ?></div>
                    </div>
                  </div>
                </td>
                <td><?= $application_number ?></td>
                <td><?= $position ?></td>
                <td><?= $appliedDate ?></td>
                <td>
                  <span class="<?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="?view_id=<?= $row['application_id'] ?>" class="action-btn view-btn">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4">No applications found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Application Details Modal -->
  <?php if ($applicationDetails): ?>
  <div class="modal" id="applicationModal" style="display: block;">
    <div class="modal-content">
      <div class="modal-header">
        <div class="applicant-header">
          <?php 
            $profilePic = !empty($applicationDetails['profile_pic']) ? 
              'uploads/profile_pics/' . $applicationDetails['profile_pic'] : 
              'uploads/profile_pics/default.jpg';
            $fullName = htmlspecialchars($applicationDetails['first_name'] . ' ' . 
              ($applicationDetails['middle_name'] ? $applicationDetails['middle_name'] . ' ' : '') . 
              $applicationDetails['last_name']);
          ?>
          <img src="<?= htmlspecialchars($profilePic) ?>" class="applicant-avatar" alt="Applicant">
          <div class="applicant-titles">
            <h2 class="modal-title"><?= $fullName ?></h2>
            <p class="applicant-position"><?= htmlspecialchars($applicationDetails['position_title']) ?></p>
            <div class="applicant-meta">
              <span class="applicant-id">ID: <?= htmlspecialchars($applicationDetails['application_number']) ?></span>
              <span class="<?= statusBadge($applicationDetails['status']) ?>">
                <?= htmlspecialchars($applicationDetails['status']) ?>
              </span>
            </div>
          </div>
        </div>
        <button class="close-modal" onclick="closeModal()">&times;</button>
      </div>
      
      <div class="modal-body">
        <!-- Tab Navigation -->
        <div class="modal-tabs">
          <button class="tab-btn active" data-tab="personal">Personal Info</button>
          <button class="tab-btn" data-tab="job">Job Details</button>
          <button class="tab-btn" data-tab="qualifications">Qualifications</button>
          <button class="tab-btn" data-tab="education">Education</button>
          <button class="tab-btn" data-tab="experience">Experience</button>
          <button class="tab-btn" data-tab="documents">Documents</button>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content active" id="personal-tab">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Email</span>
              <span class="info-value"><?= htmlspecialchars($applicationDetails['email']) ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Phone</span>
              <span class="info-value"><?= htmlspecialchars($applicationDetails['phone']) ?></span>
            </div>
            <?php if (!empty($applicationDetails['birthdate'])): ?>
              <div class="info-item">
                <span class="info-label">Birthdate</span>
                <span class="info-value">
                  <?= date('F j, Y', strtotime($applicationDetails['birthdate'])) ?>
                </span>
              </div>
            <?php endif; ?>
            <div class="info-item">
              <span class="info-label">Date Applied</span>
              <span class="info-value">
                <?= date('F j, Y h:i A', strtotime($applicationDetails['submitted_at'])) ?>
              </span>
            </div>
          </div>
        </div>
        
        <div class="tab-content" id="job-tab">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Position</span>
              <span class="info-value"><?= htmlspecialchars($applicationDetails['position_title']) ?></span>
            </div>
            <?php if (!empty($applicationDetails['department_name'])): ?>
              <div class="info-item">
                <span class="info-label">Department</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['department_name']) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($applicationDetails['type'])): ?>
              <div class="info-item">
                <span class="info-label">Job Type</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['type']) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($applicationDetails['category'])): ?>
              <div class="info-item">
                <span class="info-label">Category</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['category']) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($applicationDetails['location_name'])): ?>
              <div class="info-item">
                <span class="info-label">Location</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['location_name']) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($applicationDetails['salary_range'])): ?>
              <div class="info-item">
                <span class="info-label">Salary Range</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['salary_range']) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($applicationDetails['position_description'])): ?>
              <div class="info-item" style="grid-column: 1 / -1">
                <span class="info-label">Job Description</span>
                <span class="info-value"><?= htmlspecialchars($applicationDetails['position_description']) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="tab-content" id="qualifications-tab">
          <?php if (!empty($positionRequirements)): ?>
            <div class="qualifications-grid">
              <?php 
              $currentType = '';
              foreach ($positionRequirements as $requirement): 
                if ($requirement['requirement_type'] != $currentType): 
                  $currentType = $requirement['requirement_type'];
              ?>
                <div class="requirement-type"><?= ucfirst($currentType) ?> Requirements</div>
              <?php endif; ?>
              <div class="requirement-item">
                <?= htmlspecialchars($requirement['description']) ?>
              </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No specific requirements found for this position.</div>
          <?php endif; ?>
        </div>
        
        <div class="tab-content" id="education-tab">
          <?php if (!empty($educationDetails)): ?>
            <div class="qualifications-grid">
              <?php foreach ($educationDetails as $education): ?>
                <div class="education-item">
                  <div class="education-title"><?= htmlspecialchars($education['level']) ?></div>
                  <div class="education-details">
                    <div class="education-detail">
                      <strong>Name of School:</strong> <?= htmlspecialchars($education['school']) ?>
                    </div>
                    <div class="education-detail">
                      <strong>Basic Education/Degree/Course:</strong> <?= htmlspecialchars($education['degree']) ?>
                    </div>
                    <div class="education-detail">
                      <strong>Period of Attendance F - T:</strong> <?= date('M Y', strtotime($education['start_date'])) ?> - <?= date('M Y', strtotime($education['end_date'])) ?>
                    </div>
                    <div class="education-detail">
                      <strong>Highest Level/Units Earned (if not graduated):</strong> <?= htmlspecialchars($education['highest_level'] ?? 'N/A') ?>
                    </div>
                    <div class="education-detail">
                      <strong>Year Graduated:</strong> <?= htmlspecialchars($education['year_graduated']) ?>
                    </div>
                    <?php if (!empty($education['honors'])): ?>
                      <div class="education-detail">
                        <strong>Scholarhip/Academic Honors Received:</strong> <?= htmlspecialchars($education['honors']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No education information provided.</div>
          <?php endif; ?>
        </div>
        
        <div class="tab-content" id="experience-tab">
          <?php if (!empty($workExperienceDetails)): ?>
            <div class="qualifications-grid">
              <?php foreach ($workExperienceDetails as $experience): ?>
                <div class="experience-item">
                  <div class="experience-title"><?= date('M Y', strtotime($experience['start_date'])) ?> - <?= date('M Y', strtotime($experience['end_date'])) ?></div>
                  <div class="experience-details">
                    <div class="experience-detail">
                      <strong>Position Title:</strong> <?= htmlspecialchars($experience['position']) ?>
                    </div>
                    <div class="experience-detail">
                      <strong>Department / Agency / Office / Company:</strong> <?= htmlspecialchars($experience['company']) ?>
                    </div>
                    <div class="experience-detail">
                      <strong>Monthly Salary:</strong> <?= htmlspecialchars($experience['salary']) ?>
                    </div>
                    <div class="experience-detail">
                      <strong>Salary / Job / Pay Grade:</strong> <?= htmlspecialchars($experience['salary_grade']) ?>
                    </div>
                    <div class="experience-detail">
                      <strong>Appointment Status:</strong> <?= htmlspecialchars($experience['status_of_appointment']) ?>
                    </div>
                    <div class="experience-detail">
                      <strong>Government Service:</strong> <?= $experience['govt_service'] == 'Y' ? 'Yes' : 'No' ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No work experience information provided.</div>
          <?php endif; ?>
        </div>
        
        <div class="tab-content" id="documents-tab">
          <div class="documents-list">
            <!-- Resume -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="document-info">
                <h4>Resume</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['resume'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['resume']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['resume']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Application Letter -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="document-info">
                <h4>Application Letter</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['application_letter'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['application_letter']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['application_letter']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <!-- Personal Data Sheet -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="document-info">
                <h4>Personal Data Sheet</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['personal_data_sheet'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['personal_data_sheet']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['personal_data_sheet']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <!-- Transcript of Records -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="document-info">
                <h4>Transcript of Records</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['transcript_of_records'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['transcript_of_records']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['transcript_of_records']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <!-- Proof of Eligibility -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-signature"></i>
              </div>
              <div class="document-info">
                <h4>Proof of Eligibility</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['proof_of_eligibility'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['proof_of_eligibility']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['proof_of_eligibility']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <!-- Other Documents -->
            <div class="document-card">
              <div class="document-icon">
                <i class="fas fa-file-archive"></i>
              </div>
              <div class="document-info">
                <h4>Other Documents</h4>
                <div class="document-actions">
                  <?php if (!empty($applicationDetails['other_documents'])): ?>
                    <a href="<?= htmlspecialchars($applicationDetails['other_documents']) ?>" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye me-1"></i> View
                    </a>
                    <a href="<?= htmlspecialchars($applicationDetails['other_documents']) ?>" download class="btn btn-sm btn-success">
                      <i class="fas fa-download me-1"></i> Download
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not provided</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" onclick="closeModal()">
          <i class="fas fa-times me-1"></i> Close
        </button>
        <a href="?generate_pdf=<?= $applicationDetails['application_id'] ?>" class="btn btn-primary">
          <i class="fas fa-file-pdf me-1"></i> Generate PDF
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/admin/list.js"></script>
</body>
</html>

<?php $conn->close(); ?>