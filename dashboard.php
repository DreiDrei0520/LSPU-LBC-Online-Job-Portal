<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// Enable error reporting for debugging
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

// Function to send status emails
function sendStatusEmail($email, $name, $position, $status, $interviewDate = null) {
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jadesupremo0@gmail.com'; // Your Gmail
        $mail->Password = 'lfns yegc vqba ywbq'; // Your App Password
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
            'Applied' => [
                'subject' => 'Application Received',
                'body' => "Dear $name,<br><br>We have received your application for the position of $position.<br><br>Our team will review your application and get back to you soon."
            ],
            'Under Review' => [
                'subject' => 'Application Under Review',
                'body' => "Dear $name,<br><br>Your application for $position is currently under review.<br><br>We appreciate your patience during this process."
            ],
            'Interview Scheduled' => [
                'subject' => 'Interview Scheduled',
                'body' => "Dear $name,<br><br>Congratulations! You've been selected for an interview for the $position position.<br><br>Interview Date: " . 
                         (!empty($interviewDate) && $interviewDate !== '0000-00-00 00:00:00' ? 
                          date('F j, Y \a\t g:i A', strtotime($interviewDate)) : 'to be determined') . 
                         "<br><br>Please arrive 15 minutes early."
            ],
            'Exam Scheduled' => [
                'subject' => 'Exam Scheduled',
                'body' => "Dear $name,<br><br>You've been scheduled for an exam for the $position position.<br><br>Exam Date: " . 
                         (!empty($interviewDate) && $interviewDate !== '0000-00-00 00:00:00' ? 
                          date('F j, Y \a\t g:i A', strtotime($interviewDate)) : 'to be determined') . 
                         "<br><br>Please bring the required materials."
            ],
            'Under Interviews' => [
                'subject' => 'Interview Process Started',
                'body' => "Dear $name,<br><br>Your application for $position is now in the interview phase.<br><br>We'll contact you soon with more details."
            ],
            'Interviewed' => [
                'subject' => 'Interview Completed',
                'body' => "Dear $name,<br><br>Thank you for attending the interview for $position.<br><br>We're currently evaluating all candidates and will notify you of our decision soon."
            ],
            'Exam Completed' => [
                'subject' => 'Exam Completed',
                'body' => "Dear $name,<br><br>Thank you for completing the exam for $position.<br><br>We're currently evaluating the results and will notify you of our decision soon."
            ],
            'For Requirements' => [
                'subject' => 'Submission of Requirements',
                'body' => "Dear $name,<br><br>Congratulations on passing the initial screening for $position!<br><br>Please submit the following requirements to proceed with your application."
            ],
            'Not Shortlisted' => [
                'subject' => 'Application Update',
                'body' => "Dear $name,<br><br>Thank you for applying for the $position position.<br><br>After careful consideration, we've decided not to shortlist your application at this time."
            ],
            'Not Selected' => [
                'subject' => 'Application Update',
                'body' => "Dear $name,<br><br>Thank you for applying for the $position position.<br><br>After careful consideration, we've decided to move forward with other candidates at this time."
            ],
            'Hired' => [
                'subject' => 'Congratulations! You\'re Hired',
                'body' => "Dear $name,<br><br>We're excited to inform you that you've been selected for the $position position!<br><br>Welcome to our team!"
            ],
            'New Job' => [
                'subject' => 'New Job Opportunities Available',
                'body' => "Dear $name,<br><br>New job opportunities matching your profile are now available.<br><br>Log in to your account to view them."
            ]
        ];

        // Check if status exists in messages, otherwise use a default
        if (!array_key_exists($status, $statusMessages)) {
            $status = 'Pending'; // Fallback to default status
        }

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
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $statusMessages[$status]['body'] ?? ''));

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
    header('Location: dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$user = null;
$applicationCount = 0;
$nextStep = 'Pending';
$currentPosition = '';
$upcomingEvents = [];

if ($userId) {
    // Fetch user details
    $stmt = $conn->prepare("SELECT user_id, profile_pic, first_name, middle_name, last_name, email FROM users WHERE user_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Combine first, middle, and last names
    $user['name'] = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);

    // Count total applications
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_apps FROM applications WHERE user_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $applicationCount = $row['total_apps'] ?? 0;
    $stmt->close();

    // Fetch latest application status and interview/exam dates
    $stmt = $conn->prepare("SELECT a.status, a.interview_date, a.exam_date, a.submitted_at, j.title AS position 
                           FROM applications a
                           JOIN job_positions j ON a.position_id = j.position_id
                           WHERE a.user_id = ? 
                           ORDER BY a.submitted_at DESC LIMIT 1");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nextStep = $row['status'] ?? 'Pending';
        $currentPosition = $row['position'] ?? '';
        
        // Check for upcoming interviews
        if (!empty($row['interview_date']) && $row['interview_date'] !== '0000-00-00 00:00:00') {
            $upcomingEvents[] = [
                'type' => 'Interview',
                'date' => $row['interview_date'],
                'formatted_date' => date('F j, Y \a\t g:i A', strtotime($row['interview_date'])),
                'position' => $row['position']
            ];
        }
        
        // Check for upcoming exams
        if (!empty($row['exam_date']) && $row['exam_date'] !== '0000-00-00 00:00:00') {
            $upcomingEvents[] = [
                'type' => 'Exam',
                'date' => $row['exam_date'],
                'formatted_date' => date('F j, Y \a\t g:i A', strtotime($row['exam_date'])),
                'position' => $row['position']
            ];
        }
    }
    $stmt->close();
    
    // Sort upcoming events by date
    usort($upcomingEvents, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
}

// Notification system
$statusMessages = [
    'Pending' => 'Your application is pending review',
    'Applied' => 'Your application has been received',
    'Under Review' => 'Your application is under review',
    'Interview Scheduled' => 'Interview scheduled on %s',
    'Exam Scheduled' => 'Exam scheduled on %s',
    'Under Interviews' => 'Your application is under interviews',
    'Interviewed' => 'Interview completed - awaiting decision',
    'Exam Completed' => 'Exam completed - awaiting results',
    'For Requirements' => 'Please submit your requirements for %s',
    'Not Shortlisted' => 'Application status: Not shortlisted',
    'Not Selected' => 'Application status: Not selected',
    'Hired' => 'Congratulations! You have been hired!',
    'New Job' => 'New job offers available: %s'
];

$notifications = [];

// Get all application status changes for this user
$stmt = $conn->prepare("SELECT a.status, a.interview_date, a.exam_date, a.submitted_at, j.title AS position 
                       FROM applications a
                       JOIN job_positions j ON a.position_id = j.position_id
                       WHERE a.user_id = ? 
                       ORDER BY a.submitted_at DESC");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $icon = 'file-alt'; // default icon
    $time = date('M j, Y', strtotime($row['submitted_at']));
    $status = $row['status'];
    
    switch ($status) {
        case 'Applied':
            $icon = 'file-import';
            break;
        case 'Under Review':
            $icon = 'search';
            break;
        case 'Interview Scheduled':
            $icon = 'calendar-alt';
            $message = sprintf($statusMessages[$status] ?? 'Interview scheduled', 
                             !empty($row['interview_date']) && $row['interview_date'] !== '0000-00-00 00:00:00' ? 
                             date('F j, Y', strtotime($row['interview_date'])) : 'to be determined');
            break;
        case 'Exam Scheduled':
            $icon = 'edit';
            $message = sprintf($statusMessages[$status] ?? 'Exam scheduled', 
                             !empty($row['exam_date']) && $row['exam_date'] !== '0000-00-00 00:00:00' ? 
                             date('F j, Y', strtotime($row['exam_date'])) : 'to be determined');
            break;
        case 'Under Interviews':
            $icon = 'user-clock';
            break;
        case 'Interviewed':
            $icon = 'user-check';
            break;
        case 'Exam Completed':
            $icon = 'check-circle';
            break;
        case 'For Requirements':
            $icon = 'file-upload';
            $message = sprintf($statusMessages[$status] ?? 'Submit requirements', $row['position']);
            break;
        case 'Not Shortlisted':
            $icon = 'user-times';
            break;
        case 'Not Selected':
            $icon = 'times-circle';
            break;
        case 'Hired':
            $icon = 'trophy';
            break;
        case 'Pending':
            $icon = 'hourglass-half';
            break;
        default:
            $message = "Application status: $status";
    }
    
    // For statuses other than Interview/Exam Scheduled, get the default message
    if (!isset($message)) {
        $message = $statusMessages[$status] ?? "Application status: $status";
    }
    
    $notifications[] = [
        'icon' => $icon,
        'message' => $message,
        'time' => $time,
        'unread' => !isset($_SESSION['notifications_read'])
    ];
    
    // Reset message variable for next iteration
    unset($message);
    
    // If this is the latest status change, send email notification
    if ($status === $nextStep && !isset($_SESSION['status_email_sent_'.$status.'_'.$userId])) {
        $emailSent = sendStatusEmail(
            $user['email'],
            $user['name'],
            $row['position'],
            $status,
            $row['interview_date'] ?? $row['exam_date'] ?? null
        );
        
        if ($emailSent) {
            $_SESSION['status_email_sent_'.$status.'_'.$userId] = true;
        }
    }
}

// Add job offer notifications
$jobStmt = $conn->prepare("SELECT title, date_posted FROM job_positions WHERE date_posted >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date_posted DESC");
$jobStmt->execute();
$jobResult = $jobStmt->get_result();

while ($job = $jobResult->fetch_assoc()) {
    $notifications[] = [
        'icon' => 'briefcase',
        'message' => sprintf($statusMessages['New Job'] ?? 'New job offers available', $job['title']),
        'time' => date('M j, Y', strtotime($job['date_posted'])),
        'unread' => !isset($_SESSION['notifications_read'])
    ];
}
$jobStmt->close();

$stmt->close();

// Count unread notifications
$unreadCount = array_reduce($notifications, function($carry, $item) {
    return $carry + (isset($item['unread']) && $item['unread'] && !isset($_SESSION['notifications_read']) ? 1 : 0);
}, 0);

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);

// Prepare upcoming events text for the stats card
$upcomingText = 'No upcoming events';
$upcomingCount = 0;
$upcomingDetails = [];

if (!empty($upcomingEvents)) {
    $upcomingCount = count($upcomingEvents);
    $upcomingText = $upcomingCount . ' upcoming ' . ($upcomingCount > 1 ? 'events' : 'event');
    
    foreach ($upcomingEvents as $event) {
        $upcomingDetails[] = $event['type'] . ' for ' . $event['position'] . ': ' . $event['formatted_date'];
    }
}

$upcomingDetailsText = !empty($upcomingDetails) ? implode('<br>', $upcomingDetails) : 'No scheduled interviews or exams';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Dashboard | Job Portal</title>
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
  <link rel="stylesheet" href="assets/css/applicants/dashboard.css">
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar animate-slide-up">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Dashboard Overview</h1>
        <p>Welcome back, <?= htmlspecialchars($user['name'] ?? 'User') ?></p>
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
                <div class="notification-item <?= isset($note['unread']) && $note['unread'] && !isset($_SESSION['notifications_read']) ? 'unread' : '' ?>">
                  <div class="notification-icon">
                    <i class="fas fa-<?= $note['icon'] ?>"></i>
                  </div>
                  <div class="notification-content">
                    <p><?= htmlspecialchars($note['message']) ?></p>
                    <span class="notification-time">
                      <i class="far fa-clock me-1"></i> <?= $note['time'] ?>
                    </span>
                  </div>
                  <?php if (isset($note['unread']) && $note['unread'] && !isset($_SESSION['notifications_read'])): ?>
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
            <a href="settings.php" class="dropdown-item">
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
      <img src="<?= htmlspecialchars($picsPath) ?>" class="welcome-img" alt="Profile Picture">
      <div class="welcome-content">
        <h2>Hello, <?= htmlspecialchars($user['name'] ?? 'User') ?>!</h2>
        <p>Track your job applications and stay updated with your progress</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-4 animate-fade-in delay-2">
        <div class="stats-card">
          <div class="card-icon primary">
            <i class="fas fa-file-alt"></i>
          </div>
          <div class="card-value"><?= htmlspecialchars($applicationCount) ?></div>
          <h5 class="card-title">Total Applications</h5>
          <p class="card-text">All your submitted job applications</p>
        </div>
      </div>
      
      <div class="col-md-4 animate-fade-in delay-3">
        <div class="stats-card">
          <div class="card-icon success">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="card-value"><?= htmlspecialchars($nextStep) ?></div>
          <h5 class="card-title">Current Status</h5>
          <p class="card-text">Latest application status</p>
        </div>
      </div>
      
      <div class="col-md-4 animate-fade-in delay-4">
        <div class="stats-card">
          <div class="card-icon warning">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div class="card-value"><?= $upcomingCount > 0 ? $upcomingText : 'None' ?></div>
          <h5 class="card-title">Upcoming Events</h5>
          <p class="card-text"><?= $upcomingDetailsText ?></p>
        </div>
      </div>
    </div>

    <!-- Progress Tracker Card -->
    <div class="progress-tracker-card animate-slide-up delay-3">
      <h3>Application Progress</h3>
      <p class="subtitle">Track your current application status</p>
      
      <div class="progress-tracker">
        <?php
        // Define the steps and their corresponding statuses
        $steps = [
            1 => ['status' => ['Applied', 'Under Review', 'Pending'], 'label' => 'Application Review'],
            2 => ['status' => ['Under Interviews', 'Interview Scheduled', 'Exam Scheduled'], 'label' => 'Evaluation Phase'],
            3 => ['status' => ['Interviewed', 'Exam Completed', 'For Requirements'], 'label' => 'Final Evaluation'],
            4 => ['status' => ['Hired', 'Not Shortlisted', 'Not Selected'], 'label' => 'Final Decision']
        ];
        
        // Determine current step based on application status
        $currentStep = 1;
        $progressWidth = 0;
        
        foreach ($steps as $stepNumber => $step) {
            if (in_array($nextStep, $step['status'])) {
                $currentStep = $stepNumber;
                break;
            }
        }
        
        // Calculate progress width based on current step
        if ($currentStep >= 1) $progressWidth = 0;
        if ($currentStep >= 2) $progressWidth = 33;
        if ($currentStep >= 3) $progressWidth = 66;
        if ($currentStep >= 4) $progressWidth = 100;
        ?>
        
        <div class="progress-bar" style="width: <?= $progressWidth ?>%;"></div>
        
        <?php foreach ($steps as $stepNumber => $step): ?>
          <div class="step <?= $stepNumber < $currentStep ? 'complete' : ($stepNumber == $currentStep ? 'active' : '') ?>">
            <div class="step-number">
              <?php if ($stepNumber < $currentStep): ?>
                <i class="fas fa-check"></i>
              <?php else: ?>
                <?= $stepNumber ?>
              <?php endif; ?>
            </div>
            <span class="step-label"><?= $step['label'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($currentPosition)): ?>
        <div class="status-description">
          <strong><?= htmlspecialchars($currentPosition) ?></strong>: Your application is currently at the "<?= htmlspecialchars($nextStep) ?>" stage.
        </div>
      <?php endif; ?>
    </div>

   

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS (Animate On Scroll) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/applicants/dashboard.js"></script>
  <script>
    // Initialize AOS
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    });
    
    // Mobile sidebar toggle
    document.getElementById('mobileSidebarToggle').addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('show');
    });
  </script>
</body>
</html>