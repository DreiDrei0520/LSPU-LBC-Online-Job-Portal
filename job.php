<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
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

// Function to send status emails
function sendStatusEmail($email, $name, $position, $status, $interviewDate = null) {
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
        $mail->setFrom('jadesupremo0@gmail.com', 'Job Portal System');
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
            'Hired' => [
                'subject' => 'Congratulations! You\'re Hired',
                'body' => "Dear $name,<br><br>We're excited to inform you that you've been selected for the $position position!<br><br>Welcome to our team!"
            ],
            'Not Selected' => [
                'subject' => 'Application Update',
                'body' => "Dear $name,<br><br>Thank you for applying for the $position position.<br><br>After careful consideration, we've decided to move forward with other candidates at this time."
            ],
            'New Job' => [
                'subject' => 'New Job Opportunities Available',
                'body' => "Dear $name,<br><br>New job opportunities matching your profile are now available.<br><br>Log in to your account to view them."
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
    header('Location: job.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$user = null;
$applicationCount = 0;
$nextStep = 'Pending';
$interviewNote = 'No upcoming interview.';
$interviewDate = null;
$currentPosition = '';

if ($userId) {
    // Fetch user details
    $stmt = $conn->prepare("SELECT user_id, profile_pic, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS name, email FROM users WHERE user_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

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

    // Fetch latest application status and interview date
    $stmt = $conn->prepare("SELECT a.status, a.interview_date, j.title AS position 
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
        if (!empty($row['interview_date']) && $row['interview_date'] !== '0000-00-00 00:00:00') {
            $interviewDate = $row['interview_date'];
            $date = date('F j, Y \a\t g:i A', strtotime($interviewDate));
            $interviewNote = "Interview scheduled: $date";
        }
    }
    $stmt->close();
}

// Notification system
$statusMessages = [
    'Pending' => 'Your application is pending review',
    'Applied' => 'Your application has been received',
    'Under Review' => 'Your application is under review',
    'Interview Scheduled' => 'Interview scheduled on %s',
    'Under Interviews' => 'Your application is under interviews',
    'Interviewed' => 'Interview completed - awaiting decision',
    'Hired' => 'Congratulations! You have been hired!',
    'Not Selected' => 'Application status: Not selected',
    'New Job' => 'New job offers available: %s'
];

$notifications = [];

// Get all application status changes for this user
$stmt = $conn->prepare("SELECT a.status, a.interview_date, a.submitted_at, j.title AS position 
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
                             !empty($row['interview_date']) && $row['interview_date'] !== '0000-00-00 00:00:00' ? 
                             date('F j, Y', strtotime($row['interview_date'])) : 'to be determined');
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
            $row['interview_date']
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
        'message' => sprintf($statusMessages['New Job'], $job['title']),
        'time' => date('M j, Y', strtotime($job['date_posted'])),
        'unread' => !isset($_SESSION['notifications_read'])
    ];
}
$jobStmt->close();

// Calculate unread notifications count
$unreadCount = 0;
if (!isset($_SESSION['notifications_read'])) {
    foreach ($notifications as $notification) {
        if (isset($notification['unread']) && $notification['unread']) {
            $unreadCount++;
        }
    }
}

// Fetch job offers with their requirements
$teachingJobs = [];
$nonTeachingJobs = [];

$sql = "SELECT j.position_id, j.title, d.name AS department, j.type, j.category, l.name AS location, 
               j.date_posted, j.place_of_assignment, j.status, j.description, j.salary_range,
               GROUP_CONCAT(CASE WHEN pr.requirement_type = 'eligibility' THEN pr.description END SEPARATOR '|') AS eligibility,
               GROUP_CONCAT(CASE WHEN pr.requirement_type = 'qualification' THEN pr.description END SEPARATOR '|') AS qualification,
               GROUP_CONCAT(CASE WHEN pr.requirement_type = 'experience' THEN pr.description END SEPARATOR '|') AS experience,
               GROUP_CONCAT(CASE WHEN pr.requirement_type = 'training' THEN pr.description END SEPARATOR '|') AS training
        FROM job_positions j
        JOIN departments d ON j.department_id = d.department_id
        JOIN locations l ON j.location_id = l.location_id
        LEFT JOIN position_requirements pr ON j.position_id = pr.position_id
        WHERE j.status = 'Open'
        GROUP BY j.position_id";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Process the concatenated requirements with NULL checks
        $row['eligibility'] = !empty($row['eligibility']) ? implode("\n", array_filter(explode('|', $row['eligibility']))) : 'Not specified';
        $row['qualification'] = !empty($row['qualification']) ? implode("\n", array_filter(explode('|', $row['qualification']))) : 'Not specified';
        $row['experience'] = !empty($row['experience']) ? implode("\n", array_filter(explode('|', $row['experience']))) : 'Not specified';
        $row['training'] = !empty($row['training']) ? implode("\n", array_filter(explode('|', $row['training']))) : 'Not specified';
        
        if (strtolower($row['category']) === 'teaching') {
            $teachingJobs[] = $row;
        } elseif (strtolower($row['category']) === 'non-teaching') {
            $nonTeachingJobs[] = $row;
        }
    }
}

$conn->close();

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
  <title>Job Listings | Job Portal</title>
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
  <link rel="stylesheet" href="assets/css/applicants/job.css">
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
        <h1>Job Listings</h1>
        <p>Browse and apply for available positions</p>
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

    <!-- Job Listings Content -->
    <div class="jobs-header">
      <h2 class="jobs-title">Available Positions</h2>
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">All Positions</button>
        <button class="filter-btn" data-filter="teaching">Teaching</button>
        <button class="filter-btn" data-filter="non-teaching">Non-Teaching</button>
      </div>
    </div>

    <div class="jobs-container">
      <!-- Teaching Positions -->
      <h3 class="section-title teaching-section">Teaching Positions</h3>
      <div class="jobs-grid teaching-jobs">
        <?php if (!empty($teachingJobs)): ?>
          <?php foreach ($teachingJobs as $job): ?>
            <div class="job-card animate-slide-up delay-1">
              <div class="job-card-header">
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <div class="department"><?= htmlspecialchars($job['department']) ?></div>
              </div>
              <div class="job-card-body">
                <div class="job-meta">
                  <div class="job-meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?= htmlspecialchars($job['type']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($job['location']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-building"></i>
                    <span><?= htmlspecialchars($job['place_of_assignment']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span><?= htmlspecialchars($job['salary_range'] ?? 'Salary not specified') ?></span>
                  </div>
                </div>
                <div class="job-date">
                  Posted: <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                </div>
                <div class="job-actions">
                  <button class="view-details-btn" data-job-id="<?= $job['position_id'] ?>">
                    <i class="fas fa-info-circle"></i> Details
                  </button>
                  <a href="application.php?job_id=<?= $job['position_id'] ?>" class="apply-btn">
                    <i class="fas fa-paper-plane"></i> Apply
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-jobs animate-slide-up delay-1">
            <i class="fas fa-chalkboard-teacher"></i>
            <p>No teaching positions available at the moment</p>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Non-Teaching Positions -->
      <h3 class="section-title non-teaching-section">Non-Teaching Positions</h3>
      <div class="jobs-grid non-teaching-jobs">
        <?php if (!empty($nonTeachingJobs)): ?>
          <?php foreach ($nonTeachingJobs as $job): ?>
            <div class="job-card animate-slide-up delay-2">
              <div class="job-card-header">
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <div class="department"><?= htmlspecialchars($job['department']) ?></div>
              </div>
              <div class="job-card-body">
                <div class="job-meta">
                  <div class="job-meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?= htmlspecialchars($job['type']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($job['location']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-building"></i>
                    <span><?= htmlspecialchars($job['place_of_assignment']) ?></span>
                  </div>
                  <div class="job-meta-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span><?= htmlspecialchars($job['salary_range'] ?? 'Salary not specified') ?></span>
                  </div>
                </div>
                <div class="job-date">
                  Posted: <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                </div>
                <div class="job-actions">
                  <button class="view-details-btn" data-job-id="<?= $job['position_id'] ?>">
                    <i class="fas fa-info-circle"></i> Details
                  </button>
                  <a href="application.php?job_id=<?= $job['position_id'] ?>" class="apply-btn">
                    <i class="fas fa-paper-plane"></i> Apply
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-jobs animate-slide-up delay-2">
            <i class="fas fa-briefcase"></i>
            <p>No non-teaching positions available at the moment</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Job Details Modal -->
    <div class="modal" id="jobDetailsModal">
      <div class="modal-content">
        <div class="modal-header">
          <button class="close-modal">&times;</button>
          <h2 id="modalJobTitle"></h2>
          <div class="department" id="modalJobDepartment"></div>
        </div>
        <div class="modal-body">
          <div class="job-details-grid">
            <div class="detail-group">
              <div class="detail-label">Position Type</div>
              <div class="detail-value" id="modalJobType"></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Category</div>
              <div class="detail-value" id="modalJobCategory"></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Location</div>
              <div class="detail-value" id="modalJobLocation"></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Place of Assignment</div>
              <div class="detail-value" id="modalJobAssignment"></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Salary Range</div>
              <div class="detail-value" id="modalJobSalary"></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Date Posted</div>
              <div class="detail-value" id="modalJobDatePosted"></div>
            </div>
          </div>
          
          <div class="job-description">
            <div class="detail-label">Job Description</div>
            <div class="detail-value" id="modalJobDescription"></div>
          </div>
          
          <div class="detail-group">
            <div class="detail-label">Eligibility</div>
            <div class="detail-value" id="modalJobEligibility"></div>
          </div>
          
          <div class="detail-group">
            <div class="detail-label">Qualification</div>
            <div class="detail-value" id="modalJobQualification"></div>
          </div>
          
          <div class="detail-group">
            <div class="detail-label">Experience</div>
            <div class="detail-value" id="modalJobExperience"></div>
          </div>
          
          <div class="detail-group">
            <div class="detail-label">Training</div>
            <div class="detail-value" id="modalJobTraining"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="view-details-btn close-modal-btn">
            <i class="fas fa-times"></i> Close
          </button>
          <a href="#" class="apply-btn" id="modalApplyBtn">
            <i class="fas fa-paper-plane"></i> Apply Now
          </a>
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
    const allJobs = <?php echo json_encode([...$teachingJobs, ...$nonTeachingJobs]); ?>;
  </script>
  <script src="assets/js/applicants/job.js"></script>
</body>
</html>