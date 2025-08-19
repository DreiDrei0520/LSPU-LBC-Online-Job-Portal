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
$stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Combine first, middle, and last names
$user['name'] = $user['first_name'] . 
                (!empty($user['middle_name']) ? ' ' . $user['middle_name'] . ' ' : ' ') . 
                $user['last_name'];

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

        // Set email content based on status
        $mail->isHTML(true);
        $mail->Subject = $statusMessages[$status]['subject'];
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #22809d; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9em; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>{$statusMessages[$status]['subject']}</h1>
                    </div>
                    <div class='content'>
                        {$statusMessages[$status]['body']}
                    </div>
                    <div class='footer'>
                        <p>Best regards,</p>
                        <p><strong>HR Team</strong><br>
                        Job Portal System</p>
                    </div>
                </div>
            </body>
            </html>
        ";
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
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch all applications for stats
$allApplicationsQuery = "SELECT status FROM applications";
$allApplicationsResult = $conn->query($allApplicationsQuery);
$allApplications = $allApplicationsResult->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$totalApplications = count($allApplications);

$statusCounts = [
    'Pending' => 0,
    'Under Review' => 0,
    'Interview Scheduled' => 0,
    'Under Interviews' => 0,
    'Interviewed' => 0,
    'Exam Scheduled' => 0,
    'Exam Completed' => 0,
    'For Requirements' => 0,
    'Hired' => 0,
    'Not Shortlisted' => 0,
    'Not Selected' => 0
];

foreach ($allApplications as $app) {
    if (isset($statusCounts[$app['status']])) {
        $statusCounts[$app['status']]++;
    }
}

// Prepare data for charts
$chartLabels = [];
$chartData = [];
$chartColors = [];
$chartHoverColors = [];

foreach ($statusCounts as $status => $count) {
    if ($count > 0) {
        $chartLabels[] = $status;
        $chartData[] = $count;
        
        // Assign colors based on status
        switch($status) {
            case 'Pending':
                $chartColors[] = '#FFC107';
                $chartHoverColors[] = '#FFA000';
                break;
            case 'Under Review':
                $chartColors[] = '#17A2B8';
                $chartHoverColors[] = '#138496';
                break;
            case 'Interview Scheduled':
                $chartColors[] = '#007BFF';
                $chartHoverColors[] = '#0069D9';
                break;
            case 'Under Interviews':
                $chartColors[] = '#6C757D';
                $chartHoverColors[] = '#5A6268';
                break;
            case 'Interviewed':
                $chartColors[] = '#28A745';
                $chartHoverColors[] = '#218838';
                break;
            case 'Exam Scheduled':
                $chartColors[] = '#FFC107';
                $chartHoverColors[] = '#FFA000';
                break;
            case 'Exam Completed':
                $chartColors[] = '#28A745';
                $chartHoverColors[] = '#218838';
                break;
            case 'For Requirements':
                $chartColors[] = '#6610F2';
                $chartHoverColors[] = '#560AC8';
                break;
            case 'Hired':
                $chartColors[] = '#20C997';
                $chartHoverColors[] = '#17A589';
                break;
            case 'Not Shortlisted':
                $chartColors[] = '#FD7E14';
                $chartHoverColors[] = '#E36209';
                break;
            case 'Not Selected':
                $chartColors[] = '#DC3545';
                $chartHoverColors[] = '#C82333';
                break;
            default:
                $chartColors[] = '#6C757D';
                $chartHoverColors[] = '#5A6268';
        }
    }
}

// Notification system
$notifications = [];
$unreadCount = 0;

// Get new pending applications for notifications
$newPendingQuery = "SELECT a.*, u.first_name, u.last_name, p.title as position 
                   FROM applications a
                   JOIN users u ON a.user_id = u.user_id
                   JOIN job_positions p ON a.position_id = p.position_id
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
  <title>Admin Dashboard | Job Portal</title>
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
  <link rel="stylesheet" href="assets/css/admin/admin_dashboard.css">
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
        <h1>Admin Dashboard Overview</h1>
        <p>Welcome back, <?= htmlspecialchars($user['name'] ?? 'Admin') ?></p>
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

    <!-- Welcome Card -->
    <div class="welcome-card animate-slide-up delay-1">
      <img src="<?= htmlspecialchars($picsPath) ?>" class="welcome-img" alt="Profile Picture">
      <div class="welcome-content">
        <h2>Hello, <?= htmlspecialchars($user['name'] ?? 'Admin') ?>!</h2>
        <p>Manage job applications and track applicant progress</p>
      </div>
    </div>

    <!-- Stats Cards - First Row -->
    <div class="stats-grid mb-4">
      <div class="stats-card animate-fade-in delay-2">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($totalApplications) ?></div>
            <h5 class="stats-card-title">Total Applications</h5>
          </div>
          <div class="stats-card-icon">
            <i class="fas fa-file-alt"></i>
          </div>
        </div>
        <p class="stats-card-text">All submitted job applications</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>12% increase</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-3">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Under Review']) ?></div>
            <h5 class="stats-card-title">Under Review</h5>
          </div>
          <div class="stats-card-icon success">
            <i class="fas fa-search"></i>
          </div>
        </div>
        <p class="stats-card-text">Applications being reviewed</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>8% increase</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-4">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Interview Scheduled'] + $statusCounts['Under Interviews'] + $statusCounts['Interviewed']) ?></div>
            <h5 class="stats-card-title">Interviews</h5>
          </div>
          <div class="stats-card-icon warning">
            <i class="fas fa-calendar-check"></i>
          </div>
        </div>
        <p class="stats-card-text">Scheduled interviews</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>5% increase</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-5">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Exam Scheduled'] + $statusCounts['Exam Completed']) ?></div>
            <h5 class="stats-card-title">Exams</h5>
          </div>
          <div class="stats-card-icon teal">
            <i class="fas fa-clipboard-check"></i>
          </div>
        </div>
        <p class="stats-card-text">Scheduled/completed exams</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>3% increase</span>
        </div>
      </div>
    </div>

    <!-- Stats Cards - Second Row -->
    <div class="stats-grid mb-4">
      <div class="stats-card animate-fade-in delay-6">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['For Requirements']) ?></div>
            <h5 class="stats-card-title">For Requirements</h5>
          </div>
          <div class="stats-card-icon purple">
            <i class="fas fa-clipboard-list"></i>
          </div>
        </div>
        <p class="stats-card-text">Applicants submitting docs</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>7% increase</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-7">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Hired']) ?></div>
            <h5 class="stats-card-title">Hired</h5>
          </div>
          <div class="stats-card-icon green">
            <i class="fas fa-user-check"></i>
          </div>
        </div>
        <p class="stats-card-text">Successful applicants</p>
        <div class="stats-card-trend">
          <i class="fas fa-chart-line text-success"></i>
          <span>15% increase</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-8">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Not Shortlisted']) ?></div>
            <h5 class="stats-card-title">Not Shortlisted</h5>
          </div>
          <div class="stats-card-icon orange">
            <i class="fas fa-user-minus"></i>
          </div>
        </div>
        <p class="stats-card-text">Initial screening</p>
        <div class="stats-card-trend down">
          <i class="fas fa-chart-line text-danger"></i>
          <span>10% decrease</span>
        </div>
      </div>
      
      <div class="stats-card animate-fade-in delay-9">
        <div class="stats-card-header">
          <div>
            <div class="stats-card-value"><?= htmlspecialchars($statusCounts['Not Selected']) ?></div>
            <h5 class="stats-card-title">Not Selected</h5>
          </div>
          <div class="stats-card-icon red">
            <i class="fas fa-user-times"></i>
          </div>
        </div>
        <p class="stats-card-text">Final decision</p>
        <div class="stats-card-trend down">
          <i class="fas fa-chart-line text-danger"></i>
          <span>5% decrease</span>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
      <!-- Pie Chart Card -->
      <div class="col-lg-6 mb-4">
        <div class="chart-card animate-slide-up delay-3">
          <h3>Application Status Distribution</h3>
          <p class="mb-3">Breakdown of applications by current status</p>
          <div class="chart-container">
            <canvas id="statusPieChart"></canvas>
          </div>
        </div>
      </div>
      
      <!-- Bar Chart Card -->
      <div class="col-lg-6 mb-4">
        <div class="chart-card animate-slide-up delay-4">
          <h3>Application Status Comparison</h3>
          <p class="mb-3">Number of applications in each status</p>
          <div class="chart-container">
            <canvas id="statusBarChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS (Animate On Scroll) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Custom JS -->
  <script>
         // Bar Chart Data
      const barChartData = {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Number of Applications',
          data: <?= json_encode($chartData) ?>,
          backgroundColor: <?= json_encode($chartColors) ?>,
          borderColor: <?= json_encode($chartHoverColors) ?>,
          borderWidth: 1
        }]
      };
            // Pie Chart Data
      const pieChartData = {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          data: <?= json_encode($chartData) ?>,
          backgroundColor: <?= json_encode($chartColors) ?>,
          hoverBackgroundColor: <?= json_encode($chartHoverColors) ?>,
          borderWidth: 1
        }]
      };
  </script>
  <script src="assets/js/admin/admin_dashboard.js"></script>
</body>
</html>