<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
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
$stmt = $conn->prepare("SELECT user_id, profile_pic, first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Function to send status emails
function sendStatusEmail($email, $name, $position, $status, $interviewDate = null, $examDate = null) {
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jadesupremo0@gmail.com';
        $mail->Password = 'lfns yegc vqba ywbq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('jadesupremo0@gmail.com', 'LSPU-LBC Online Job Portal');
        $mail->addAddress($email);
        $mail->addReplyTo('hr@example.com', 'HR Department');

        // Status-specific content
        $statusMessages = [
            'Pending' => [
                'subject' => 'Application Received',
                'body' => "Dear $name,<br><br>We have received your application for the position of $position and it is currently pending review."
            ],
            'Under Review' => [
                'subject' => 'Application Under Review',
                'body' => "Dear $name,<br><br>Your application for $position is currently under review.<br><br>We appreciate your patience during this process."
            ],
            'Interview Scheduled' => [
                'subject' => 'Interview Scheduled',
                'body' => "Dear $name,<br><br>Congratulations! You've been selected for an interview for the $position position.<br><br>Interview Date: " . date('F j, Y \a\t g:i A', strtotime($interviewDate)) . "<br><br>Please arrive 15 minutes early."
            ],
            'Under Interviews' => [
                'subject' => 'Interview Process Started',
                'body' => "Dear $name,<br><br>Your application for $position is now in the interview phase.<br><br>We'll contact you soon with more details."
            ],
            'Interviewed' => [
                'subject' => 'Interview Completed',
                'body' => "Dear $name,<br><br>Thank you for attending the interview for $position.<br><br>We're currently evaluating all candidates and will notify you of our decision soon."
            ],
            'Exam Scheduled' => [
                'subject' => 'Exam Scheduled',
                'body' => "Dear $name,<br><br>Congratulations! You've been scheduled for an exam for the $position position.<br><br>Exam Date: " . date('F j, Y \a\t g:i A', strtotime($examDate)) . "<br><br>Please bring your valid ID and arrive on time."
            ],
            'Exam Completed' => [
                'subject' => 'Exam Completed',
                'body' => "Dear $name,<br><br>Thank you for completing the exam for $position.<br><br>We're currently evaluating the results and will notify you of the next steps soon."
            ],
            'For Requirements' => [
                'subject' => 'Requirements Submission',
                'body' => "Dear $name,<br><br>Congratulations! You've been shortlisted for the $position position.<br><br>Please submit the following requirements:<br>- Resume/CV<br>- Diploma/TOR<br>- Certificate of Employment<br>- Government IDs<br><br>Submit these to our HR department within 3 working days."
            ],
            'Hired' => [
                'subject' => 'Congratulations! You\'re Hired',
                'body' => "Dear $name,<br><br>We're excited to inform you that you've been selected for the $position position!<br><br>Welcome to our team!"
            ],
            'Not Shortlisted' => [
                'subject' => 'Application Update - Not Shortlisted',
                'body' => "Dear $name,<br><br>Thank you for applying for the $position position.<br><br>After careful consideration, we regret to inform you that you were not shortlisted for this position.<br><br>We encourage you to apply for future openings that match your qualifications."
            ],
            'Not Selected' => [
                'subject' => 'Application Update',
                'body' => "Dear $name,<br><br>Thank you for applying for the $position position.<br><br>After careful consideration, we've decided to move forward with other candidates at this time."
            ]
        ];

        if (!isset($statusMessages[$status])) {
            throw new Exception("Invalid status provided for email template");
        }

        // Set email content based on status
        $mail->isHTML(true);
        $mail->Subject = $statusMessages[$status]['subject'];
        $mail->Body = $statusMessages[$status]['body'];
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $statusMessages[$status]['body']));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
    header('Location: applicantschedule.php');
    exit;
}

// Handle status update
if (isset($_POST['update_application'])) {
    $applicationId = $_POST['application_id'];
    $status = $_POST['status'] ?? null;
    $interviewDate = isset($_POST['interview_date']) ? $_POST['interview_date'] : null;
    $examDate = isset($_POST['exam_date']) ? $_POST['exam_date'] : null;
    $scheduleType = isset($_POST['schedule_type']) ? $_POST['schedule_type'] : null;
    $nextStatus = isset($_POST['next_status']) ? $_POST['next_status'] : null;
    $scheduleDate = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
    
    // Validate status
    $validStatuses = [
        'Pending', 'Under Review', 'Interview Scheduled', 'Under Interviews', 
        'Interviewed', 'Exam Scheduled', 'Exam Completed', 'For Requirements', 
        'Hired', 'Not Shortlisted', 'Not Selected'
    ];
    
    if ($status && !in_array($status, $validStatuses)) {
        $_SESSION['error_message'] = 'Invalid status provided';
        header('Location: applicantschedule.php');
        exit;
    }
    
    // Determine the actual status to set
    if ($scheduleType === 'interview') {
        $status = 'Interview Scheduled';
        $interviewDate = $scheduleDate;
    } elseif ($scheduleType === 'exam') {
        $status = 'Exam Scheduled';
        $examDate = $scheduleDate;
    } elseif ($nextStatus) {
        $status = $nextStatus;
    }
    
    // Prepare the update statement
    if ($status == 'Interview Scheduled' && $interviewDate) {
        $stmt = $conn->prepare("UPDATE applications SET status = ?, interview_date = ? WHERE application_id = ?");
        $stmt->bind_param("ssi", $status, $interviewDate, $applicationId);
    } elseif ($status == 'Exam Scheduled' && $examDate) {
        $stmt = $conn->prepare("UPDATE applications SET status = ?, exam_date = ? WHERE application_id = ?");
        $stmt->bind_param("ssi", $status, $examDate, $applicationId);
    } else {
        // Clear dates if status doesn't require them
        if (in_array($status, ['Under Review', 'Under Interviews', 'Interviewed', 'Exam Completed', 'For Requirements', 'Hired', 'Not Shortlisted', 'Not Selected'])) {
            $stmt = $conn->prepare("UPDATE applications SET status = ?, interview_date = NULL, exam_date = NULL WHERE application_id = ?");
            $stmt->bind_param("si", $status, $applicationId);
        } else {
            $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
            $stmt->bind_param("si", $status, $applicationId);
        }
    }
    
    if ($stmt->execute()) {
        // Get applicant details to send email
        $stmt = $conn->prepare("SELECT u.email, u.first_name, jp.title as position 
                               FROM applications a 
                               JOIN users u ON a.user_id = u.user_id
                               JOIN job_positions jp ON a.position_id = jp.position_id
                               WHERE a.application_id = ?");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $applicant = $result->fetch_assoc();
        
        if ($applicant) {
            // Send status email
            $emailSent = sendStatusEmail(
                $applicant['email'], 
                $applicant['first_name'], 
                $applicant['position'], 
                $status, 
                $interviewDate, 
                $examDate
            );
            
            if (!$emailSent) {
                $_SESSION['warning_message'] = 'Status updated but email notification failed to send';
            } else {
                $_SESSION['success_message'] = 'Status updated and notification sent successfully!';
            }
        } else {
            $_SESSION['success_message'] = 'Status updated successfully!';
        }
    } else {
        $_SESSION['error_message'] = 'Error updating status: ' . $conn->error;
    }
    
    header('Location: applicantschedule.php');
    exit;
}

// Handle export to Excel
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="applicants_' . date('Y-m-d') . '.xls"');
    
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
    $output .= '<div class="report-title">Applicant Management Report</div>';
    $output .= '</div>';
    
    $output .= '<div class="generated-date">Generated on: ' . date('F j, Y') . '</div>';
    
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th>Applicant Number</th>';
    $output .= '<th>Applicant Name</th>';
    $output .= '<th>Position</th>';
    $output .= '<th>Date Applied</th>';
    $output .= '<th>Status</th>';
    $output .= '<th>Interview Date</th>';
    $output .= '<th>Exam Date</th>';
    $output .= '</tr>';
    
    // Fetch all applications for export
    $exportQuery = "SELECT a.application_number, CONCAT(u.first_name, ' ', u.last_name) as applicant_name, 
                   jp.title as position, a.submitted_at, a.status, a.interview_date, a.exam_date
                   FROM applications a
                   JOIN users u ON a.user_id = u.user_id
                   JOIN job_positions jp ON a.position_id = jp.position_id
                   ORDER BY a.submitted_at DESC";
    $exportResult = $conn->query($exportQuery);
    
    while ($row = $exportResult->fetch_assoc()) {
        $output .= '<tr>';
        $output .= '<td>' . htmlspecialchars($row['application_number']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['applicant_name']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['position']) . '</td>';
        $output .= '<td>' . htmlspecialchars(date("F j, Y", strtotime($row['submitted_at']))) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['status']) . '</td>';
        $output .= '<td>' . ($row['interview_date'] ? htmlspecialchars(date("F j, Y g:i A", strtotime($row['interview_date']))) : 'N/A') . '</td>';
        $output .= '<td>' . ($row['exam_date'] ? htmlspecialchars(date("F j, Y g:i A", strtotime($row['exam_date']))) : 'N/A') . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</table>';
    $output .= '</body></html>';
    
    echo $output;
    exit;
}

// Handle search and sorting
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build the query with search and sort
$applicationsQuery = "SELECT a.*, u.first_name, u.last_name, u.email, u.profile_pic, jp.title as position 
                     FROM applications a
                     JOIN users u ON a.user_id = u.user_id
                     JOIN job_positions jp ON a.position_id = jp.position_id";

if (!empty($search)) {
    $applicationsQuery .= " WHERE (CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%' OR jp.title LIKE '%$search%' OR a.application_number LIKE '%$search%')";
}

// Add sorting
switch ($sort) {
    case 'date_asc':
        $applicationsQuery .= " ORDER BY a.submitted_at ASC";
        break;
    case 'name_asc':
        $applicationsQuery .= " ORDER BY u.first_name ASC, u.last_name ASC";
        break;
    case 'name_desc':
        $applicationsQuery .= " ORDER BY u.first_name DESC, u.last_name DESC";
        break;
    case 'status_asc':
        $applicationsQuery .= " ORDER BY a.status ASC";
        break;
    case 'status_desc':
        $applicationsQuery .= " ORDER BY a.status DESC";
        break;
    default: // date_desc
        $applicationsQuery .= " ORDER BY a.submitted_at DESC";
        break;
}

$applicationsResult = $conn->query($applicationsQuery);
$applications = $applicationsResult->fetch_all(MYSQLI_ASSOC);

// Notification system
$notifications = [];
$unreadCount = 0;

// Get new pending applications for notifications
$newPendingQuery = "SELECT a.*, u.first_name, u.last_name, jp.title as position 
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
        'message' => 'New pending application from ' . $app['first_name'] . ' ' . $app['last_name'] . ' for ' . $app['position'],
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
  <title>Applicant Management | Job Portal</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/admin/applicantschedule.css">
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
        <h1>Applicant Management</h1>
        <p>Manage all job applications</p>
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
                 value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>
      
      <div class="sort-export-container">
        <div class="dropdown sort-dropdown">
          <button class="btn dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sort me-1"></i>
            <?php 
              switch($sort) {
                case 'date_asc': echo 'Oldest First'; break;
                case 'name_asc': echo 'Name (A-Z)'; break;
                case 'name_desc': echo 'Name (Z-A)'; break;
                case 'status_asc': echo 'Status (A-Z)'; break;
                case 'status_desc': echo 'Status (Z-A)'; break;
                default: echo 'Newest First'; break;
              }
            ?>
          </button>
          <ul class="dropdown-menu" aria-labelledby="sortDropdown">
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=date_desc">Newest First</a></li>
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=date_asc">Oldest First</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=name_asc">Name (A-Z)</a></li>
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=name_desc">Name (Z-A)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=status_asc">Status (A-Z)</a></li>
            <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&sort=status_desc">Status (Z-A)</a></li>
          </ul>
        </div>
        
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
      <p class="table-subtitle">Showing <?= count($applications) ?> applications</p>
      
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
            <?php if (count($applications) > 0): ?>
              <?php foreach ($applications as $app): ?>
              <tr>
                <td>
                  <div class="applicant-info">
                    <?php
                      $profilePicFilename = !empty($app['profile_pic']) ? $app['profile_pic'] : 'default.jpg';
                      $picPath = 'uploads/profile_pics/' . $profilePicFilename;
                      $hasProfilePic = file_exists($picPath);
                    ?>
                    <?php if ($hasProfilePic): ?>
                      <img src="<?= htmlspecialchars($picPath) ?>" class="applicant-img" alt="Applicant Picture">
                    <?php else: ?>
                      <i class="fas fa-user-circle" style="font-size: 40px; color: var(--gray-light);"></i>
                    <?php endif; ?>
                    <div>
                      <div class="applicant-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                      <div class="applicant-email"><?= htmlspecialchars($app['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($app['application_number']) ?></td>
                <td><?= htmlspecialchars($app['position']) ?></td>
                <td><?= htmlspecialchars(date("F j, Y", strtotime($app['submitted_at']))) ?></td>
                <td>
                  <?php
                    $statusClass = 'status-badge-' . strtolower(str_replace(' ', '-', $app['status']));
                  ?>
                  <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span>
                  <?php if ($app['status'] == 'Interview Scheduled' && !empty($app['interview_date'])): ?>
                    <div class="mt-1 small text-muted">
                      <i class="far fa-calendar-alt me-1"></i>
                      <?= date('M j, Y g:i A', strtotime($app['interview_date'])) ?>
                    </div>
                  <?php elseif ($app['status'] == 'Exam Scheduled' && !empty($app['exam_date'])): ?>
                    <div class="mt-1 small text-muted">
                      <i class="far fa-calendar-alt me-1"></i>
                      <?= date('M j, Y g:i A', strtotime($app['exam_date'])) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <!-- Edit Button -->
                    <button type="button" class="action-btn edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" 
                      data-application-id="<?= $app['application_id'] ?>"
                      data-current-status="<?= htmlspecialchars($app['status']) ?>"
                      data-interview-date="<?= !empty($app['interview_date']) ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : '' ?>"
                      data-exam-date="<?= !empty($app['exam_date']) ? date('Y-m-d\TH:i', strtotime($app['exam_date'])) : '' ?>">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    
                    <!-- Schedule Button (only show for certain statuses) -->
                    <?php if (in_array($app['status'], ['Under Review', 'Under Interviews', 'Interviewed', 'Exam Completed'])): ?>
                      <button type="button" class="action-btn schedule-btn" data-bs-toggle="modal" data-bs-target="#scheduleModal" 
                        data-application-id="<?= $app['application_id'] ?>"
                        data-current-status="<?= htmlspecialchars($app['status']) ?>">
                        <i class="fas fa-calendar-plus"></i> Schedule
                      </button>
                    <?php endif; ?>
                    
                    <!-- Done Button (only show for scheduled interviews/exams) -->
                    <?php if (in_array($app['status'], ['Interview Scheduled', 'Exam Scheduled'])): ?>
                      <button type="button" class="action-btn done-btn" data-bs-toggle="modal" data-bs-target="#doneModal" 
                        data-application-id="<?= $app['application_id'] ?>"
                        data-current-status="<?= htmlspecialchars($app['status']) ?>">
                        <i class="fas fa-check-circle"></i> Done
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
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

  <!-- Edit Status Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="application_id" id="editApplicationId">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Update Application Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="editStatus" class="form-label">Status</label>
              <select name="status" id="editStatus" class="form-select" onchange="toggleEditDateFields(this)">
                <option value="Pending">Pending</option>
                <option value="Under Review">Under Review</option>
                <option value="Interview Scheduled">Interview Scheduled</option>
                <option value="Under Interviews">Under Interviews</option>
                <option value="Interviewed">Interviewed</option>
                <option value="Exam Scheduled">Exam Scheduled</option>
                <option value="Exam Completed">Exam Completed</option>
                <option value="For Requirements">For Requirements</option>
                <option value="Hired">Hired</option>
                <option value="Not Shortlisted">Not Shortlisted</option>
                <option value="Not Selected">Not Selected</option>
              </select>
            </div>
            <div class="mb-3" id="editInterviewDateContainer" style="display: none;">
              <label for="editInterviewDate" class="form-label">Interview Date & Time</label>
              <input type="datetime-local" name="interview_date" id="editInterviewDate" class="form-control">
            </div>
            <div class="mb-3" id="editExamDateContainer" style="display: none;">
              <label for="editExamDate" class="form-label">Exam Date & Time</label>
              <input type="datetime-local" name="exam_date" id="editExamDate" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_application" class="btn btn-primary">Update Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Schedule Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="application_id" id="scheduleApplicationId">
          <input type="hidden" name="status" id="scheduleStatus">
          <div class="modal-header">
            <h5 class="modal-title" id="scheduleModalLabel">Schedule Next Step</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="scheduleType" class="form-label">Schedule Type</label>
              <select name="schedule_type" id="scheduleType" class="form-select" onchange="toggleScheduleFields(this)">
                <option value="">Select type</option>
                <option value="interview">Interview</option>
                <option value="exam">Exam</option>
              </select>
            </div>
            <div class="mb-3" id="scheduleDateContainer" style="display: none;">
              <label for="scheduleDate" class="form-label">Date & Time</label>
              <input type="datetime-local" name="schedule_date" id="scheduleDate" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_application" class="btn btn-primary">Schedule</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Done Modal -->
  <div class="modal fade" id="doneModal" tabindex="-1" aria-labelledby="doneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="application_id" id="doneApplicationId">
          <input type="hidden" name="status" id="doneStatus">
          <div class="modal-header">
            <h5 class="modal-title" id="doneModalLabel">Mark as Completed</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to mark this as completed?</p>
            <div class="mb-3">
              <label for="nextStatus" class="form-label">Next Status</label>
              <select name="next_status" id="nextStatus" class="form-select">
                <?php if (isset($app) && $app['status'] === 'Interview Scheduled'): ?>
                  <option value="Under Interviews">Under Interviews (if interview was completed)</option>
                  <option value="Interviewed">Interviewed (all interviews done)</option>
                <?php elseif (isset($app) && $app['status'] === 'Exam Scheduled'): ?>
                  <option value="Exam Completed">Exam Completed</option>
                  <option value="For Requirements">For Requirements</option>
                <?php else: ?>
                  <option value="Under Interviews">Under Interviews (if interview was completed)</option>
                  <option value="Interviewed">Interviewed (all interviews done)</option>
                  <option value="Exam Completed">Exam Completed</option>
                  <option value="For Requirements">For Requirements</option>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_application" class="btn btn-primary">Mark as Done</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/admin/applicantschedule.js"></script>
</body>
</html>