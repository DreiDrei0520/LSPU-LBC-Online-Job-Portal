<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user details
$userId = $_SESSION['user_id'];
$user = null;
$stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, email, phone, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Count total applications
$stmt = $conn->prepare("SELECT COUNT(*) AS total_apps FROM applications WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$applicationCount = $row['total_apps'] ?? 0;
$stmt->close();

// Fetch latest application status and interview date
$nextStep = 'Pending';
$interviewNote = 'No upcoming interview.';
$interviewDate = null;
$currentPosition = '';

$stmt = $conn->prepare("SELECT a.status, a.interview_date, j.title AS position 
                       FROM applications a
                       JOIN job_positions j ON a.position_id = j.position_id
                       WHERE a.user_id = ? 
                       ORDER BY a.submitted_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nextStep = $row['status'] ?? 'Pending';
    $currentPosition = $row['position'] ?? '';
    if (!empty($row['interview_date'])) {
        $interviewDate = $row['interview_date'];
        $date = date('F j, Y \a\t g:i A', strtotime($interviewDate));
        $interviewNote = "Interview scheduled: $date";
    }
}
$stmt->close();

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
    'Exam Completed' => 'Exam completed - awaiting results',
    'New Job' => 'New job offers available: %s'
];

$notifications = [];

// Get all application status changes for this user
$stmt = $conn->prepare("SELECT a.status, a.interview_date, a.submitted_at, j.title AS position 
                       FROM applications a
                       JOIN job_positions j ON a.position_id = j.position_id
                       WHERE a.user_id = ? 
                       ORDER BY a.submitted_at DESC");
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
                             !empty($row['interview_date']) ? 
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
        case 'Hired':
            $icon = 'trophy';
            break;
        case 'Pending':
            $icon = 'hourglass-half';
            break;
        default:
            $message = "Application status: $status";
    }
    
    if (!isset($message)) {
        $message = $statusMessages[$status] ?? "Application status: $status";
    }
    
    $notifications[] = [
        'icon' => $icon,
        'message' => $message,
        'time' => $time,
        'unread' => !isset($_SESSION['notifications_read'])
    ];
    
    unset($message);
}

// Add job offer notifications
$jobStmt = $conn->prepare("SELECT title, date_posted FROM job_positions 
                          WHERE date_posted >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                          AND status = 'Open'
                          ORDER BY date_posted DESC");
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

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
    header('Location: applicationhistory.php');
    exit;
}

// Fetch application history
$stmt = $conn->prepare("SELECT a.*, j.title AS position_name, j.department_id, d.name AS department_name
                       FROM applications a
                       JOIN job_positions j ON a.position_id = j.position_id
                       JOIN departments d ON j.department_id = d.department_id
                       WHERE a.user_id = ?
                       ORDER BY a.submitted_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();

// Handle view application details request
$applicationDetails = null;
$workExperience = [];
$education = [];
if (isset($_GET['view_id'])) {
    $viewId = $_GET['view_id'];
    $stmt = $conn->prepare("SELECT a.*, j.title AS position_name, j.department_id, d.name AS department_name
                           FROM applications a
                           JOIN job_positions j ON a.position_id = j.position_id
                           JOIN departments d ON j.department_id = d.department_id
                           WHERE a.application_id = ? AND a.user_id = ?");
    $stmt->bind_param("ii", $viewId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicationDetails = $result->fetch_assoc();
    $stmt->close();
    
    if ($applicationDetails) {
        // Fetch work experience
        $stmt = $conn->prepare("SELECT * FROM application_work_experience WHERE application_id = ?");
        $stmt->bind_param("i", $viewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $workExperience = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Fetch education
        $stmt = $conn->prepare("SELECT * FROM application_education WHERE application_id = ?");
        $stmt->bind_param("i", $viewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $education = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        header('Location: applicationhistory.php');
        exit;
    }
}

// Profile picture handling
$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath) && $picsPath !== $defaultProfilePic;

function statusBadge($status) {
    $classes = [
        'Under Review' => 'bg-blue-100 text-blue-800',
        'Under Interviews' => 'bg-cyan-100 text-cyan-800',
        'Interview Scheduled' => 'bg-teal-100 text-teal-800',
        'Interviewed' => 'bg-yellow-100 text-yellow-800',
        'Hired' => 'bg-green-100 text-green-800',
        'Not Selected' => 'bg-red-100 text-red-800',
        'Exam Completed' => 'bg-purple-100 text-purple-800',
        'default' => 'bg-gray-100 text-gray-800'
    ];
    
    return $classes[$status] ?? $classes['default'];
}

function formatDateRange($start, $end) {
    if (empty($start)) return 'Present';
    $startFormatted = date('M Y', strtotime($start));
    $endFormatted = empty($end) ? 'Present' : date('M Y', strtotime($end));
    return "$startFormatted - $endFormatted";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application History | Job Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/applicants/applicationhistory.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar animate-slide-up">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Application History</h1>
        <p>View your past job applications</p>
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
                    <p><?= htmlspecialchars($note['message'] ?? '') ?></p>
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
            <a href="profile.php" class="dropdown-item">
              <i class="fas fa-user me-2"></i>
              <span>Profile</span>
            </a>
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

    <div class="history-container animate-slide-up delay-1">
      <div class="history-header">
        <h2 class="history-title">My Applications</h2>
        <div class="search-container">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search applications..." onkeyup="filterTable(this.value)">
        </div>
      </div>

      <?php if ($applications->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="applications-table" id="appTable">
            <thead>
              <tr>
                <th>Application NO.</th>
                <th>Position</th>
                <th>Department</th>
                <th>Date Applied</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $applications->fetch_assoc()): 
                $badge = statusBadge($row['status']);
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['application_number'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($row['position_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['department_name'] ?? '') ?></td>
                <td><?= date("M d, Y", strtotime($row['submitted_at'])) ?></td>
                <td><span class="status-badge <?= $badge ?>"><?= htmlspecialchars($row['status'] ?? '') ?></span></td>
                <td>
                  <button onclick="showApplicationDetails(<?= $row['application_id'] ?>)" class="action-btn" title="View Details">
                    <i class="fas fa-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="no-applications text-center py-5">
          <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
          <h4 class="text-muted">No applications found</h4>
          <p class="text-muted">You haven't submitted any job applications yet.</p>
          <a href="jobs.php" class="btn btn-primary mt-3">Browse Jobs</a>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Application Details Modal -->
    <?php if ($applicationDetails): ?>
    <div id="applicationModal" class="modal" style="display: block;">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Application Details</h2>
          <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="section-title">Personal Information</div>
          <div class="detail-grid">
            <div class="detail-label">Full Name:</div>
            <div class="detail-value">
              <?= htmlspecialchars($user['first_name'] . ' ' . 
                ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . 
                $user['last_name']) ?>
            </div>
            
            <div class="detail-label">Email:</div>
            <div class="detail-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            
            <div class="detail-label">Phone:</div>
            <div class="detail-value"><?= htmlspecialchars($user['phone'] ?? '') ?></div>
          </div>
          
          <div class="section-title">Application Details</div>
          <div class="detail-grid">
            <div class="detail-label">Position Applied:</div>
            <div class="detail-value"><?= htmlspecialchars($applicationDetails['position_name'] ?? '') ?></div>
            
            <div class="detail-label">Department:</div>
            <div class="detail-value"><?= htmlspecialchars($applicationDetails['department_name'] ?? '') ?></div>
            
            <div class="detail-label">Date Applied:</div>
            <div class="detail-value"><?= date("F j, Y, g:i a", strtotime($applicationDetails['submitted_at'])) ?></div>
            
            <div class="detail-label">Current Status:</div>
            <div class="detail-value">
              <span class="status-badge <?= statusBadge($applicationDetails['status']) ?>">
                <?= htmlspecialchars($applicationDetails['status'] ?? '') ?>
              </span>
            </div>
            
            <?php if (!empty($applicationDetails['interview_date'])): ?>
              <div class="detail-label">Interview Date:</div>
              <div class="detail-value">
                <?= date("F j, Y, g:i a", strtotime($applicationDetails['interview_date'])) ?>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Work Experience Section -->
          <div class="section-title">
            Work Experience
            <button class="btn btn-sm btn-outline-primary float-end" onclick="showWorkExperienceModal()">
              <i class="fas fa-expand"></i> View All
            </button>
          </div>
          <?php if (!empty($workExperience)): ?>
            <div class="table-container">
              <table class="table table-sm modal-table">
                <thead>
                  <tr>
                    <th class="text-left">Inclusive Dates</th>
                    <th class="text-left">Position Title</th>
                    <th class="text-left">Company</th>
                    <th class="text-left">Monthly Salary</th>
                    <th class="text-left">Salary Grade</th>
                    <th class="text-left">Appointment Status</th>
                    <th class="text-center">Govt Service</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($workExperience, 0, 3) as $exp): ?>
                     <tr>
                      <td class="text-left"><?= formatDateRange($exp['start_date'] ?? '', $exp['end_date'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['position'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['company'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['salary'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['salary_grade'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['status_of_appointment'] ?? '') ?></td>
                      <td class="text-center"><?= ($exp['govt_service'] ?? '') === 'Y' ? 'Yes' : 'No' ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (count($workExperience) > 3): ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted">
                        +<?= count($workExperience) - 3 ?> more experiences
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No work experience recorded for this application.</div>
          <?php endif; ?>

          <!-- Education Section -->
          <div class="section-title">
            Education
            <button class="btn btn-sm btn-outline-primary float-end" onclick="showEducationModal()">
              <i class="fas fa-expand"></i> View All
            </button>
          </div>
          <?php if (!empty($education)): ?>
            <div class="table-container">
              <table class="table table-sm modal-table">
                <thead>
                  <tr>
                    <th class="text-left">Level</th>
                    <th class="text-left">School</th>
                    <th class="text-left">Degree/Course</th>
                    <th class="text-left">Attendance Period</th>
                    <th class="text-left">Highest Level</th>
                    <th class="text-left">Year Graduated</th>
                    <th class="text-left">Honors</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($education, 0, 3) as $edu): ?>
                    <tr>
                      <td class="text-left"><?= htmlspecialchars($edu['level'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['school'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['degree'] ?? '') ?></td>
                      <td class="text-left"><?= formatDateRange($edu['start_date'] ?? '', $edu['end_date'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['highest_level'] ?? 'N/A') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['year_graduated'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['honors'] ?? 'N/A') ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (count($education) > 3): ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted">
                        +<?= count($education) - 3 ?> more education entries
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No education recorded for this application.</div>
          <?php endif; ?>
          
          <!-- Submitted Documents Section -->
          <div class="section-title">Submitted Documents</div>
<div class="documents-grid">
  <?php if (!empty($applicationDetails['resume'])): ?>
    <div class="document-item">
      <div class="document-icon">
        <i class="fas fa-file-pdf"></i>
      </div>
      <div class="document-info">
        <div class="document-name">Resume</div>
        <div class="document-filename"><?= basename($applicationDetails['resume']) ?></div>
      </div>
      <div class="document-actions">
        <a href="<?= htmlspecialchars($applicationDetails['resume']) ?>" class="document-btn view-btn" target="_blank">
          <i class="fas fa-eye"></i> View
        </a>
        <a href="<?= htmlspecialchars($applicationDetails['resume']) ?>" class="document-btn download-btn" download>
          <i class="fas fa-download"></i> Download
        </a>
      </div>
    </div>
  <?php endif; ?>
            <?php if (!empty($applicationDetails['application_letter'])): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <div class="document-name">Application Letter</div>
                  <div class="document-filename"><?= basename($applicationDetails['application_letter']) ?></div>
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($applicationDetails['application_letter']) ?>" class="document-btn view-btn" target="_blank">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="<?= htmlspecialchars($applicationDetails['application_letter']) ?>" class="document-btn download-btn" download>
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($applicationDetails['personal_data_sheet'])): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <div class="document-name">Personal Data Sheet</div>
                  <div class="document-filename"><?= basename($applicationDetails['personal_data_sheet']) ?></div>
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($applicationDetails['personal_data_sheet']) ?>" class="document-btn view-btn" target="_blank">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="<?= htmlspecialchars($applicationDetails['personal_data_sheet']) ?>" class="document-btn download-btn" download>
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($applicationDetails['transcript_of_records'])): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <div class="document-name">Transcript of Records</div>
                  <div class="document-filename"><?= basename($applicationDetails['transcript_of_records']) ?></div>
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($applicationDetails['transcript_of_records']) ?>" class="document-btn view-btn" target="_blank">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="<?= htmlspecialchars($applicationDetails['transcript_of_records']) ?>" class="document-btn download-btn" download>
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($applicationDetails['proof_of_eligibility'])): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <div class="document-name">Proof of Eligibility</div>
                  <div class="document-filename"><?= basename($applicationDetails['proof_of_eligibility']) ?></div>
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($applicationDetails['proof_of_eligibility']) ?>" class="document-btn view-btn" target="_blank">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="<?= htmlspecialchars($applicationDetails['proof_of_eligibility']) ?>" class="document-btn download-btn" download>
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($applicationDetails['other_documents'])): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <div class="document-name">Other Documents</div>
                  <div class="document-filename"><?= basename($applicationDetails['other_documents']) ?></div>
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($applicationDetails['other_documents']) ?>" class="document-btn view-btn" target="_blank">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="<?= htmlspecialchars($applicationDetails['other_documents']) ?>" class="document-btn download-btn" download>
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn btn-outline" onclick="closeModal()">
            <i class="fas fa-times me-2"></i> Close
          </button>
        </div>
      </div>
    </div>

    <!-- Work Experience Modal -->
    <div id="workExperienceModal" class="modal">
      <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
          <h2>Work Experience</h2>
          <button class="close-modal" onclick="closeWorkExperienceModal()">&times;</button>
        </div>
        <div class="modal-body">
          <?php if (!empty($workExperience)): ?>
            <div class="table-container">
              <table class="table modal-table">
                <thead>
                  <tr>
                    <th class="text-left">Inclusive Dates</th>
                    <th class="text-left">Position Title</th>
                    <th class="text-left">Company</th>
                    <th class="text-left">Monthly Salary</th>
                    <th class="text-left">Salary Grade</th>
                    <th class="text-left">Appointment Status</th>
                    <th class="text-center">Govt Service</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($workExperience as $exp): ?>
                    <tr>
                      <td class="text-left"><?= formatDateRange($exp['start_date'] ?? '', $exp['end_date'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['position'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['company'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['salary'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['salary_grade'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($exp['status_of_appointment'] ?? '') ?></td>
                      <td class="text-center"><?= ($exp['govt_service'] ?? '') === 'Y' ? 'Yes' : 'No' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No work experience recorded for this application.</div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" onclick="closeWorkExperienceModal()">
            <i class="fas fa-times me-2"></i> Close
          </button>
        </div>
      </div>
    </div>

    <!-- Education Modal -->
    <div id="educationModal" class="modal">
      <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
          <h2>Education</h2>
          <button class="close-modal" onclick="closeEducationModal()">&times;</button>
        </div>
        <div class="modal-body">
          <?php if (!empty($education)): ?>
            <div class="table-container">
              <table class="table modal-table">
                <thead>
                  <tr>
                    <th class="text-left">Level</th>
                    <th class="text-left">School</th>
                    <th class="text-left">Degree/Course</th>
                    <th class="text-left">Attendance Period</th>
                    <th class="text-left">Highest Level</th>
                    <th class="text-left">Year Graduated</th>
                    <th class="text-left">Honors</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($education as $edu): ?>
                    <tr>
                      <td class="text-left"><?= htmlspecialchars($edu['level'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['school'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['degree'] ?? '') ?></td>
                      <td class="text-left"><?= formatDateRange($edu['start_date'] ?? '', $edu['end_date'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['highest_level'] ?? 'N/A') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['year_graduated'] ?? '') ?></td>
                      <td class="text-left"><?= htmlspecialchars($edu['honors'] ?? 'N/A') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No education recorded for this application.</div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" onclick="closeEducationModal()">
            <i class="fas fa-times me-2"></i> Close
          </button>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="assets/js/applicants/applicationhistory.js"></script>
</body>
</html>