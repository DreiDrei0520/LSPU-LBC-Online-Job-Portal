<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin')) {
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

// Handle status update for applicants
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    $allowedStatuses = ['Hired', 'Not Selected'];
    if (in_array($_POST['status'], $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
        $stmt->bind_param("si", $_POST['status'], $_POST['app_id']);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = "Applicant status updated successfully!";
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle PDF generation request
if (isset($_GET['generate_pdf'])) {
    $applicationId = (int)$_GET['generate_pdf'];
    
    // Fetch application details
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
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicationDetails = $result->fetch_assoc();
    $stmt->close();
    
    // Education details
    $eduStmt = $conn->prepare("SELECT * FROM application_education WHERE application_id = ?");
    $eduStmt->bind_param("i", $applicationId);
    $eduStmt->execute();
    $eduResult = $eduStmt->get_result();
    $educationDetails = $eduResult->fetch_all(MYSQLI_ASSOC);
    $eduStmt->close();
    
    // Work experience details
    $expStmt = $conn->prepare("SELECT * FROM application_work_experience WHERE application_id = ?");
    $expStmt->bind_param("i", $applicationId);
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
    
    // Generate PDF
    require_once 'vendor/autoload.php';
    
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->getOptions()->setIsRemoteEnabled(true);

    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resume - ' . htmlspecialchars($applicationDetails['first_name'] . ' ' . $applicationDetails['last_name']) . '</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #333; margin: 0; padding: 20px; }
        .resume-container { max-width: 900px; margin: 0 auto; background: #fdfdfd; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0077b6; padding-bottom: 15px; margin-bottom: 25px; }
        .header-info { max-width: 75%; }
        .header-info h1 { margin: 0; font-size: 28px; color: #0077b6; }
        .header-info p { margin: 5px 0; font-size: 14px; }
        .profile-pic { width: 100px; height: 100px; border-radius: 50%; border: 2px solid #ccc; object-fit: cover; }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 18px; color: #0077b6; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
        .info-row { display: flex; flex-wrap: wrap; }
        .info-block { width: 50%; margin-bottom: 10px; }
        .info-block strong { display: block; color: #444; }
        .info-block span { color: #000; }

        .list-item { margin-bottom: 10px; }
        .label { font-weight: bold; }

        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="resume-container">
        <div class="header">
            <div class="header-info">
                <h1>' . htmlspecialchars($applicationDetails['first_name'] . ' ' .
                    (!empty($applicationDetails['middle_name']) ? htmlspecialchars($applicationDetails['middle_name']) . ' ' : '') .
                    htmlspecialchars($applicationDetails['last_name']) . '</h1>
                <p>' . htmlspecialchars($applicationDetails['email']) . ' | ' . htmlspecialchars($applicationDetails['phone']) . '</p>
                <p>Applied for: ' . htmlspecialchars($applicationDetails['position_title']) . '</p>
            </div>
        </div>

        <div class="section">
            <h2>Personal Information</h2>
            <div class="info-row">
                <div class="info-block"><strong>Application ID:</strong> <span>' . htmlspecialchars($applicationDetails['application_number'])) . '</span></div>';
                if (!empty($applicationDetails['birthdate'])) {
                    $html .= '<div class="info-block"><strong>Birthdate:</strong> <span>' . date('F j, Y', strtotime($applicationDetails['birthdate'])) . '</span></div>';
                }
    $html .= '
                <div class="info-block"><strong>Date Applied:</strong> <span>' . date('F j, Y h:i A', strtotime($applicationDetails['submitted_at'])) . '</span></div>
                <div class="info-block"><strong>Status:</strong> <span>' . htmlspecialchars($applicationDetails['status']) . '</span></div>
            </div>
        </div>

        <div class="section">
            <h2>Job Information</h2>
            <div class="info-row">';
                if (!empty($applicationDetails['department_name'])) {
                    $html .= '<div class="info-block"><strong>Department:</strong> <span>' . htmlspecialchars($applicationDetails['department_name']) . '</span></div>';
                }
                if (!empty($applicationDetails['type'])) {
                    $html .= '<div class="info-block"><strong>Job Type:</strong> <span>' . htmlspecialchars($applicationDetails['type']) . '</span></div>';
                }
                if (!empty($applicationDetails['category'])) {
                    $html .= '<div class="info-block"><strong>Category:</strong> <span>' . htmlspecialchars($applicationDetails['category']) . '</span></div>';
                }
                if (!empty($applicationDetails['location_name'])) {
                    $html .= '<div class="info-block"><strong>Location:</strong> <span>' . htmlspecialchars($applicationDetails['location_name']) . '</span></div>';
                }
                if (!empty($applicationDetails['salary_range'])) {
                    $html .= '<div class="info-block"><strong>Salary Range:</strong> <span>' . htmlspecialchars($applicationDetails['salary_range']) . '</span></div>';
                }
    $html .= '
            </div>';
            if (!empty($applicationDetails['position_description'])) {
                $html .= '<p><strong>Job Description:</strong> ' . htmlspecialchars($applicationDetails['position_description']) . '</p>';
            }
    $html .= '
        </div>';

        // Requirements
        if (!empty($positionRequirements)) {
            $html .= '
            <div class="section">
                <h2>Position Requirements</h2>';
            $currentType = '';
            foreach ($positionRequirements as $requirement) {
                if ($requirement['requirement_type'] != $currentType) {
                    $currentType = $requirement['requirement_type'];
                    $html .= '<strong>' . ucfirst($currentType) . ' Requirements:</strong><ul>';
                }
                $html .= '<li>' . htmlspecialchars($requirement['description']) . '</li>';
            }
            $html .= '</ul></div>';
        }

        // Education
        if (!empty($educationDetails)) {
            $html .= '
            <div class="section">
                <h2>Education</h2>';
            foreach ($educationDetails as $edu) {
                $html .= '
                <div class="list-item">
                    <strong>' . htmlspecialchars($edu['level']) . ':</strong> ' . htmlspecialchars($edu['degree']) . ' at ' . htmlspecialchars($edu['school']) . '<br>
                    <span>' . date('M Y', strtotime($edu['start_date'])) . ' - ' . date('M Y', strtotime($edu['end_date'])) . '</span><br>';
                if (!empty($edu['honors'])) {
                    $html .= '<em>Honors: ' . htmlspecialchars($edu['honors']) . '</em><br>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Experience
        if (!empty($workExperienceDetails)) {
            $html .= '
            <div class="section">
                <h2>Work Experience</h2>';
            foreach ($workExperienceDetails as $exp) {
                $html .= '
                <div class="list-item">
                    <strong>' . htmlspecialchars($exp['position']) . '</strong> at ' . htmlspecialchars($exp['company']) . '<br>
                    <span>' . date('M Y', strtotime($exp['start_date'])) . ' - ' . date('M Y', strtotime($exp['end_date'])) . '</span><br>
                    <span>Salary: ' . htmlspecialchars($exp['salary']) . ', Status: ' . htmlspecialchars($exp['status_of_appointment']) . ', Govt Service: ' . ($exp['govt_service'] == 'Y' ? 'Yes' : 'No') . '</span>
                </div>';
            }
            $html .= '</div>';
        }

        // Documents
        $html .= '
        <div class="section">
            <h2>Submitted Documents</h2>
            <ul>';
        $docs = [
            'Resume' => $applicationDetails['resume'],
            'Application Letter' => $applicationDetails['application_letter'],
            'Personal Data Sheet' => $applicationDetails['personal_data_sheet'],
            'Transcript of Records' => $applicationDetails['transcript_of_records'],
            'Proof of Eligibility' => $applicationDetails['proof_of_eligibility'],
            'Other Documents' => $applicationDetails['other_documents'],
        ];
        foreach ($docs as $label => $doc) {
            $html .= '<li><strong>' . $label . ':</strong> ' . (!empty($doc) ? 'Submitted' : 'Not Provided') . '</li>';
        }
        $html .= '</ul></div>';

        $html .= '
        <div class="footer">
            Resume generated on ' . date('F j, Y h:i A') . ' by ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '
        </div>
    </div>
</body>
</html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $dompdf->stream('application_' . $applicationDetails['application_number'] . '.pdf', [
        'Attachment' => true
    ]);
    
    exit;
}

// Fetch applicants with Interviewed status with position details
$applicants = $conn->query("
    SELECT a.*, 
           u.first_name, u.last_name, u.email, u.phone, u.birthdate, u.profile_pic,
           jp.title AS position_title, 
           d.name AS department_name,
           l.name AS location_name
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions jp ON a.position_id = jp.position_id
    JOIN departments d ON jp.department_id = d.department_id
    JOIN locations l ON jp.location_id = l.location_id
    WHERE a.status IN ('Interviewed', 'Exam Completed')
    ORDER BY a.submitted_at DESC
");

// Status badge styling
function statusBadge($s) {
    return match($s) {
        'Pending'             => 'bg-secondary text-white',
        'Applied'             => 'bg-primary text-white',
        'Under Review'        => 'bg-warning text-dark',
        'Interview Scheduled' => 'bg-info text-white',
        'Under Interviews'    => 'bg-info text-white',
        'Interviewed'         => 'bg-primary text-white',
        'Hired'               => 'bg-success text-white',
        'Not Selected'        => 'bg-danger text-white',
        'Exam Completed'      => 'bg-purple text-white',
        default               => 'bg-secondary text-white',
    };
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Status | Job Portal</title>
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
  <link rel="stylesheet" href="assets/css/admin/applicantstatus.css">
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
                <h1>Applicant Status Management</h1>
                <p>Welcome back, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
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
                            <img src="<?= htmlspecialchars($profilePicPath) ?>" class="profile-img" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle fs-5"></i>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end profile-menu" aria-labelledby="profileDropdown">
                        <a href="admin_settings.php" class="dropdown-item">
                            <i class="fas fa-cog me-2"></i> <span>Settings</span>
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
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

        <!-- Applicants Table -->
        <div class="table-card animate-slide-up delay-2">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Interviewed Applicants</h3>
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" class="form-control" placeholder="Search applicants..." onkeyup="filterTable(this.value)">
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Applicant</th>
                  <th>Applicant NO.</th>
                  <th>Position</th>
                  <th>Date Applied</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($applicants->num_rows > 0): ?>
                  <?php while($row = $applicants->fetch_assoc()): 
                    $fullName = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
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
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                      <div class="action-buttons">
                        <a href="?view_id=<?= $row['application_id'] ?>" class="btn-icon btn-primary" title="View Application">
                          <i class="fas fa-eye"></i>
                        </a>
                        <form method="post">
                          <input type="hidden" name="app_id" value="<?= $row['application_id'] ?>">
                          <input type="hidden" name="status" value="Hired">
                          <button type="submit" class="btn-icon btn-success" title="Hire Applicant">
                            <i class="fas fa-check"></i>
                          </button>
                        </form>
                        <form method="post">
                          <input type="hidden" name="app_id" value="<?= $row['application_id'] ?>">
                          <input type="hidden" name="status" value="Not Selected">
                          <button type="submit" class="btn-icon btn-danger" title="Not Selected">
                            <i class="fas fa-times"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center py-4">No interviewed applicants found</td>
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
                <span class="badge <?= statusBadge($applicationDetails['status']) ?>">
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
    <i class="fas fa-file-alt"></i> <!-- Using a user-file icon for resume -->
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
          <div class="status-actions">
            <form method="post">
              <input type="hidden" name="app_id" value="<?= $applicationDetails['application_id'] ?>">
              <input type="hidden" name="status" value="Hired">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-check me-1"></i> Hire Applicant
              </button>
            </form>
            <form method="post">
              <input type="hidden" name="app_id" value="<?= $applicationDetails['application_id'] ?>">
              <input type="hidden" name="status" value="Not Selected">
              <button type="submit" class="btn btn-danger">
                <i class="fas fa-times me-1"></i> Not Selected
              </button>
            </form>
          </div>
          <div class="utility-actions">
            <button class="btn btn-outline-secondary" onclick="closeModal()">
              <i class="fas fa-times me-1"></i> Close
            </button>
            <a href="?generate_pdf=<?= $applicationDetails['application_id'] ?>" class="btn btn-primary">
              <i class="fas fa-file-pdf me-1"></i> Generate PDF
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS (Animate On Scroll) -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/admin/applicantstatus.js"></script>
</body>
</html>

<?php $conn->close(); ?>