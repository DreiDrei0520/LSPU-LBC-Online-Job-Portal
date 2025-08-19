<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'appjobsystem');

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

// Notification system
$notifications = [];
$unreadCount = 0;

// Get new pending applications for notifications
$newPendingQuery = "SELECT a.application_id, a.application_number, u.first_name, u.last_name, jp.title as position, a.submitted_at 
                  FROM applications a
                  JOIN users u ON a.user_id = u.user_id
                  JOIN job_positions jp ON a.position_id = jp.position_id
                  WHERE a.status IN ('Pending', 'Under Review')
                  AND a.submitted_at >= NOW() - INTERVAL 1 DAY
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

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
    header('Location: matrix.php');
    exit;
}

// Handle saving evaluation data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evaluation'])) {
    // Get all form data and sanitize
    $applicationId = (int)$_POST['application_id'];
    $personality = (int)$_POST['personality'];
    $communication = (int)$_POST['communication'];
    $analytical = (int)$_POST['analytical'];
    $achievement = (int)$_POST['achievement'];
    $leadership = (int)$_POST['leadership'];
    $relationship = (int)$_POST['relationship'];
    $jobfit = (int)$_POST['jobfit'];
    $aptitude = (int)$_POST['aptitude'];
    $educationRating = (int)$_POST['education_rating'];
    $educationUnits = (int)$_POST['education_units'];
    $experienceRating = (int)$_POST['experience_rating'];
    $additionalExperience = (int)$_POST['additional_experience'];
    $trainingRating = (int)$_POST['training_rating'];
    $eligibilityRating = (int)$_POST['eligibility_rating'];
    $accomplishmentRating = (int)$_POST['accomplishment_rating'];
    $category = $_POST['category'];
    $evaluatorId = (int)$userId;

    // Calculate scores based on category
    $interviewTotal = $personality + $communication + $analytical + $achievement + 
                      $leadership + $relationship + $jobfit;

    if ($category === 'Teaching') {
        // Teaching position scoring
        // Interview: 70/70 = 10%
        $interviewPercent = ($interviewTotal / 70) * 10;

        // Aptitude: 5/5 = 5%
        $aptitudePercent = ($aptitude / 5) * 5;

        // Potential score = 15% max
        $potentialScore = $interviewPercent + $aptitudePercent;

        // Education score: (35-40 base + 0-5 units) = 40% max
        $educationScore = $educationRating + $educationUnits;
    } else {
        // Non-teaching position scoring
        $interviewPercent = ($interviewTotal / 70) * 10;
        $aptitudePercent = ($aptitude / 5) * 5;
        $potentialScore = $interviewPercent + $aptitudePercent;

        // Education score: (30-40 base + 0-10 units) = 40% max
        $educationScore = $educationRating + $educationUnits;
    }

    $experienceScore = $experienceRating + $additionalExperience;
    $trainingScore = $trainingRating;
    $eligibilityScore = $eligibilityRating;
    $accomplishmentScore = $accomplishmentRating;

    $totalScore = $potentialScore + $educationScore + $experienceScore + 
                  $trainingScore + $eligibilityScore + $accomplishmentScore;

    // Check if evaluation exists
    $checkStmt = $conn->prepare("SELECT evaluation_id FROM evaluations WHERE application_id = ?");
    $checkStmt->bind_param("i", $applicationId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing evaluation
        $stmt = $conn->prepare("UPDATE evaluations SET 
            personality = ?, communication = ?, analytical = ?, achievement = ?,
            leadership = ?, relationship = ?, jobfit = ?, aptitude = ?,
            education_rating = ?, education_units = ?, experience_rating = ?,
            additional_experience = ?, training_rating = ?, eligibility_rating = ?,
            accomplishment_rating = ?, total_score = ?, evaluator_id = ?
            WHERE application_id = ?");

        $stmt->bind_param(
            "iiiiiiiiiiiiiiidii",
            $personality, $communication, $analytical, $achievement,
            $leadership, $relationship, $jobfit, $aptitude,
            $educationRating, $educationUnits, $experienceRating,
            $additionalExperience, $trainingRating, $eligibilityRating,
            $accomplishmentRating, $totalScore, $evaluatorId,
            $applicationId
        );
    } else {
        // Insert new evaluation
        $stmt = $conn->prepare("INSERT INTO evaluations (
            application_id, personality, communication, analytical, achievement,
            leadership, relationship, jobfit, aptitude, education_rating,
            education_units, experience_rating, additional_experience,
            training_rating, eligibility_rating, accomplishment_rating,
            total_score, evaluator_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "iiiiiiiiiiiiiiiiid",
            $applicationId, $personality, $communication, $analytical, $achievement,
            $leadership, $relationship, $jobfit, $aptitude, $educationRating,
            $educationUnits, $experienceRating, $additionalExperience,
            $trainingRating, $eligibilityRating, $accomplishmentRating,
            $totalScore, $evaluatorId
        );
    }

    // Execute and update status
    if ($stmt->execute()) {
        $updateStmt = $conn->prepare("UPDATE applications SET status = 'Under Review', evaluation_status = 'Completed' 
                                      WHERE application_id = ?");
        $updateStmt->bind_param("i", $applicationId);
        $updateStmt->execute();
        $updateStmt->close();

        $_SESSION['success_message'] = "Evaluation saved successfully! Total Score: " . number_format($totalScore, 2) . "%";
        $_SESSION['last_evaluated'] = $applicationId;
    } else {
        $_SESSION['error_message'] = "Error saving evaluation: " . $stmt->error;
    }

    $stmt->close();
    header('Location: matrix.php');
    exit;
}

// Handle delete evaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_evaluation'])) {
    $applicationId = (int)$_POST['application_id'];
    
    $stmt = $conn->prepare("DELETE FROM evaluations WHERE application_id = ?");
    $stmt->bind_param("i", $applicationId);
    
    if ($stmt->execute()) {
        $updateStmt = $conn->prepare("UPDATE applications SET status = 'Pending', evaluation_status = 'Pending' 
                                    WHERE application_id = ?");
        $updateStmt->bind_param("i", $applicationId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $_SESSION['success_message'] = "Evaluation deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting evaluation: " . $stmt->error;
    }
    
    $stmt->close();
    header('Location: matrix.php');
    exit;
}

// Fetch applicants with their details and evaluation data
$applicantsQuery = $conn->prepare("
    SELECT 
        a.application_id, 
        a.application_number, 
        u.first_name, 
        u.middle_name, 
        u.last_name, 
        u.email, 
        jp.title as position,
        jp.category,
        a.status, 
        a.submitted_at,
        u.profile_pic,
        e.total_score,
        e.evaluation_id,
        e.personality,
        e.communication,
        e.analytical,
        e.achievement,
        e.leadership,
        e.relationship,
        e.jobfit,
        e.aptitude,
        e.education_rating,
        e.education_units,
        e.experience_rating,
        e.additional_experience,
        e.training_rating,
        e.eligibility_rating,
        e.accomplishment_rating
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions jp ON a.position_id = jp.position_id
    LEFT JOIN evaluations e ON a.application_id = e.application_id
    WHERE a.status IN ('Pending', 'Under Review', 'Interviewed', 'Interview Scheduled', 'Exam Scheduled', 'Exam Completed')
    ORDER BY a.submitted_at DESC
");
$applicantsQuery->execute();
$applicants = $applicantsQuery->get_result();

// Fetch unevaluated applicants for the select dropdown
$unevaluatedQuery = $conn->prepare("
    SELECT 
        a.application_id, 
        a.application_number, 
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        jp.title as position,
        jp.category
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN job_positions jp ON a.position_id = jp.position_id
    LEFT JOIN evaluations e ON a.application_id = e.application_id
    WHERE e.evaluation_id IS NULL AND a.status IN ('Pending', 'Under Review', 'Interviewed', 'Interview Scheduled', 'Exam Scheduled', 'Exam Completed')
    ORDER BY a.submitted_at DESC
");
$unevaluatedQuery->execute();
$unevaluatedApplicants = $unevaluatedQuery->get_result();

// Status badge styling
function statusBadge($status) {
    $statusMap = [
        'Under Review' => 'bg-warning text-dark',
        'Interview Scheduled' => 'bg-info text-white',
        'Hired' => 'bg-success text-white',
        'Not Selected' => 'bg-danger text-white',
        'Pending' => 'bg-secondary text-white',
        'Applied' => 'bg-primary text-white',
        'Under Interviews' => 'bg-info text-white',
        'Interviewed' => 'bg-info text-white',
        'Exam Completed' => 'bg-success text-white',
        'Exam Scheduled' => 'bg-info text-white',
        'Hired Not Shortlisted' => 'bg-success text-white',
        'For Requirements' => 'bg-warning text-dark',
        'Not Shortlisted' => 'bg-danger text-white'
    ];
    
    return $statusMap[$status] ?? 'bg-secondary text-white';
}

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);

// Check if we should show the print modal after evaluation
$showPrintModal = false;
$lastEvaluated = null;
if (isset($_SESSION['last_evaluated'])) {
    $showPrintModal = true;
    $lastEvaluated = $_SESSION['last_evaluated'];
    unset($_SESSION['last_evaluated']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Evaluation Matrix | Job Portal</title>
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
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/admin/matrix.css">
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
        <h1>Applicant Evaluation Matrix</h1>
        <p>Welcome back, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
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

    <!-- Applicants Table -->
    <div class="table-card animate-slide-up delay-2">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Applicants for Evaluation</h3>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-primary" onclick="showAddEvaluationModal()">
            <i class="fas fa-plus me-1"></i> Add Evaluation
          </button>
        </div>
      </div>
      
      <div class="table-responsive">
        <table id="applicantsTable" class="table table-striped" style="width:100%">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Position</th>
              <th>Category</th>
              <th>Status</th>
              <th>TPS (100%)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $applicants->fetch_assoc()): 
              $fullName = htmlspecialchars(trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']));
              $email = htmlspecialchars($row['email']);
              $position = htmlspecialchars($row['position']);
              $category = htmlspecialchars($row['category'] ?? 'Not specified');
              $appliedDate = date("M d, Y", strtotime($row['submitted_at']));
              $badgeClass = statusBadge($row['status']);
              $totalScore = isset($row['total_score']) ? number_format($row['total_score'], 2) : 'Not evaluated';
              $hasEvaluation = !empty($row['evaluation_id']);
              
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
              <td><?= $position ?></td>
              <td><?= $category ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= $row['status'] ?></span></td>
              <td><?= $totalScore ?></td>
              <td>
                <div class="action-buttons">
                  <?php if ($hasEvaluation): ?>
                    <button class="btn btn-sm btn-primary" onclick="editApplicant(<?= $row['application_id'] ?>, '<?= $fullName ?>', '<?= $position ?>', '<?= $category ?>', '<?= $appliedDate ?>', '<?= $totalScore ?>')">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['application_id'] ?>, '<?= $fullName ?>')">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-sm btn-info" onclick="viewEvaluation(<?= $row['application_id'] ?>, '<?= $fullName ?>', '<?= $position ?>', '<?= $category ?>')">
                      <i class="fas fa-eye"></i> View
                    </button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-success" onclick="startNewEvaluation(<?= $row['application_id'] ?>, '<?= $fullName ?>', '<?= $position ?>', '<?= $category ?>')">
                      <i class="fas fa-plus"></i> Evaluate
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add Evaluation Modal -->
  <div id="add-evaluation-modal" class="add-evaluation-modal">
    <div class="add-evaluation-content">
      <span class="close" onclick="closeAddEvaluationModal()">&times;</span>
      <h2>Add New Evaluation</h2>
      
      <form id="select-applicant-form">
        <div class="form-group">
          <label for="applicant-select">Select Applicant to Evaluate:</label>
          <select id="applicant-select" class="form-control" required>
            <option value="">-- Select Applicant --</option>
            <?php while($applicant = $unevaluatedApplicants->fetch_assoc()): ?>
              <option value="<?= $applicant['application_id'] ?>" data-category="<?= $applicant['category'] ?>">
                <?= htmlspecialchars($applicant['full_name']) ?> - <?= htmlspecialchars($applicant['position']) ?> (<?= $applicant['application_number'] ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeAddEvaluationModal()">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="startEvaluation()">Continue</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Evaluation Modal -->
  <div id="evaluation-modal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2 id="modal-title">Applicant Evaluation</h2>
      
      <form id="evaluation-form" method="POST">
        <input type="hidden" name="save_evaluation" value="1">
        <input type="hidden" id="application-id" name="application_id">
        <input type="hidden" id="applicant-category" name="category">
        
        <div class="form-group">
          <label for="applicant-name">Name of Applicant:</label>
          <input type="text" id="applicant-name" class="form-control" readonly>
        </div>
        
        <div class="form-group">
          <label for="applicant-position">Position:</label>
          <input type="text" id="applicant-position" class="form-control" readonly>
        </div>
        
        <div class="form-group">
          <label for="application-date">Date Applied:</label>
          <input type="text" id="application-date" class="form-control" readonly>
        </div>
        
        <hr>
        
        <div class="form-group">
          <label for="interview-personality">Personality Rating (1-10):</label>
          <input type="number" id="interview-personality" name="personality" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-communication">Communication Skills Rating (1-10):</label>
          <input type="number" id="interview-communication" name="communication" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-analytical">Analytical Skills Rating (1-10):</label>
          <input type="number" id="interview-analytical" name="analytical" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-achievement">Achievement Orientation Rating (1-10):</label>
          <input type="number" id="interview-achievement" name="achievement" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-leadership">Leadership/Management Rating (1-10):</label>
          <input type="number" id="interview-leadership" name="leadership" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-relationship">Relationship Management Rating (1-10):</label>
          <input type="number" id="interview-relationship" name="relationship" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="interview-jobfit">Job Fit Rating (1-10):</label>
          <input type="number" id="interview-jobfit" name="jobfit" min="1" max="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="aptitude-test">Aptitude Test Rating:</label>
          <select id="aptitude-test" name="aptitude" class="form-control" required>
            <option value="5">Superior (5)</option>
            <option value="4">Above Average (4)</option>
            <option value="3">Average (3)</option>
            <option value="2">Below Average (2)</option>
            <option value="1">Lowest (1)</option>
          </select>
        </div>
        
        <!-- Teaching Position Specific Fields -->
        <div id="teaching-fields" class="position-type-fields">
          <div class="form-group">
            <label for="education-rating">Education Rating (35-40):</label>
            <input type="number" id="education-rating" name="education_rating" min="35" max="40" value="35" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="education-units">Additional Education Units Completed:</label>
            <select id="education-units" name="education_units" class="form-control" required>
              <option value="0">None</option>
              <option value="1">Doctoral Completed 25% of units</option>
              <option value="2">Doctoral Completed 50% of units</option>
              <option value="3">Doctoral Completed 75% of units</option>
              <option value="4">Doctoral Completed Academic Requirements (CAR)</option>
              <option value="5">Doctoral Completed 100% of units</option>
            </select>
          </div>
        </div>
        
        <!-- Non-Teaching Position Specific Fields -->
        <div id="non-teaching-fields" class="position-type-fields">
          <div class="form-group">
            <label for="nt-education-rating">Education Rating (30-40):</label>
            <input type="number" id="nt-education-rating" name="education_rating" min="30" max="40" value="30" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="nt-education-units">Additional Education Units Completed:</label>
            <select id="nt-education-units" name="education_units" class="form-control" required>
              <option value="0">None</option>
              <option value="1">Masteral Completed 25% Total number of units required</option>
              <option value="2">Masteral Completed 50% Total number of units required</option>
              <option value="3">Masteral Completed 75% Total number of units required</option>
              <option value="4">Masteral Completed Academic Requirements (CAR)</option>
              <option value="5">Masteral Completed 100% Total number of units required</option>
              <option value="6">Doctoral Completed 25% Total number of units required</option>
              <option value="7">Doctoral Completed 50% Total number of units required</option>
              <option value="8">Doctoral Completed 75% Total number of units required</option>
              <option value="9">Doctoral Completed Academic Requirements (CAR)</option>
              <option value="10">Doctoral Completed 100% Total number of units required</option>
            </select>
          </div>
        </div>
        
        <!-- Common Experience Fields -->
        <div class="form-group">
          <label for="experience-rating">Experience Rating:</label>
          <select id="experience-rating" name="experience_rating" class="form-control" required>
            <option value="15">5-10 years (15)</option>
            <option value="10">3-4 years (10)</option>
            <option value="5">1-2 years (5)</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="additional-experience">Additional Years of Experience (for more than 10 years):</label>
          <input type="number" id="additional-experience" name="additional_experience" min="0" value="0" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="training-rating">Training Rating (5 + additional):</label>
          <input type="number" id="training-rating" name="training_rating" min="5" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="eligibility-rating">Eligibility Rating (10):</label>
          <input type="number" id="eligibility-rating" name="eligibility_rating" min="0" max="10" value="10" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="accomplishment-rating">Outstanding Accomplishments Rating (0-5):</label>
          <input type="number" id="accomplishment-rating" name="accomplishment_rating" min="0" max="5" value="0" class="form-control" required>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Evaluation</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="delete-confirmation-modal" class="confirmation-modal">
    <div class="confirmation-content">
      <span class="close" onclick="closeDeleteModal()">&times;</span>
      <h3>Confirm Deletion</h3>
      <p id="delete-confirmation-message">Are you sure you want to delete this evaluation?</p>
      <form id="delete-form" method="POST">
        <input type="hidden" name="delete_evaluation" value="1">
        <input type="hidden" id="delete-application-id" name="application_id">
        <div class="confirmation-buttons">
          <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Evaluation Modal -->
  <div id="view-modal" class="view-modal">
    <div class="view-modal-content">
      <span class="close" onclick="closeViewModal()">&times;</span>
      <h2>View Evaluation Details</h2>
      
      <div class="view-content" id="view-content">
        <!-- Content will be loaded here -->
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
        <button type="button" class="btn btn-primary" id="print-from-view" onclick="printFromView()">
          <i class="fas fa-print"></i> Print
        </button>
      </div>
    </div>
  </div>

  <!-- Print Preview Modal -->
  <div id="print-modal" class="print-modal">
    <div class="print-modal-content">
      <span class="close" onclick="closePrintModal()">&times;</span>
      <h2>Print Evaluation Form</h2>
      
      <div class="print-preview" id="print-preview">
        <!-- Preview content will be loaded here -->
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closePrintModal()">Close</button>
        <button type="button" class="btn btn-success" onclick="printEvaluationForm()">
          <i class="fas fa-print"></i> Print Form
        </button>
      </div>
    </div>
  </div>

  <!-- Teaching Position Print Form (Hidden) -->
  <div id="print-teaching-form" class="print-only" style="display: none;">
    <div class="print-form">
      <div class="print-header">
        <div class="logo-container">
          <img src="images/lspulogo.png" alt="LSPU Logo" class="print-logo">
          <div>
            <div class="university-name">Republic of the Philippines</div>
            <div class="university-name">Laguna State Polytechnic University</div>
            <div class="university-name">Province of Laguna</div>
          </div>
        </div>
        
        <div class="print-title">PERSONNEL SELECTION BOARD<br>ASSESSMENT CRITERIA<br>NEW APPLICANTS FOR TEACHING POSITION</div>
      </div>

      <div class="print-applicant-info">
        <div><strong>Name of Applicant:</strong> <span id="print-name"></span></div>
        <div><strong>Position:</strong> <span id="print-position"></span></div>
      </div>

      <table class="print-table">
        <tr>
          <th colspan="4">CRITERIA</th>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">POTENTIAL (15%)</td>
        </tr>
        <tr>
          <td colspan="2"><strong>Interview (10%)</strong></td>
          <td style="width: 40px;">RATING</td>
          <td style="width: 40px;">SCORE</td>
        </tr>
        <tr>
          <td colspan="2">Personality (Pleasing personal appearance)</td>
          <td>10</td>
          <td id="print-personality"></td>
        </tr>
        <tr>
          <td colspan="2">Communication Skills (Smart, good communication skills and speaks with courtesy and refined manner)</td>
          <td>10</td>
          <td id="print-communication"></td>
        </tr>
        <tr>
          <td colspan="2">Analytical Skills (Shows insight when expressing ideas, intelligence, emotional stability and self controlled)</td>
          <td>10</td>
          <td id="print-analytical"></td>
        </tr>
        <tr>
          <td colspan="2">Achievement Orientation (Result-Oriented, Shows creativity and innovation)</td>
          <td>10</td>
          <td id="print-achievement"></td>
        </tr>
        <tr>
          <td colspan="2">Leadership/Management (Planning and organizing skills)</td>
          <td>10</td>
          <td id="print-leadership"></td>
        </tr>
        <tr>
          <td colspan="2">Relationship Management (Does the candidate seek contacts or networks and pursue friendly relationships with people?, Builds report through formal or informal /casual contacts with people who may be valuable to the organization)</td>
          <td>10</td>
          <td id="print-relationship"></td>
        </tr>
        <tr>
          <td colspan="2">Job Fit (Flexibility, creativity and resourcefulness, Does the candidate show commitment/seem to stay long?)</td>
          <td>10</td>
          <td id="print-jobfit"></td>
        </tr>
        <tr class="print-total-row">
          <td colspan="2">Total Score - 70 points</td>
          <td>70</td>
          <td id="print-interview-total"></td>
        </tr>
        <tr>
          <td colspan="2"><strong>Aptitude Test (5%)</strong></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">Superior</td>
          <td>5</td>
          <td id="print-aptitude-superior"></td>
        </tr>
        <tr>
          <td colspan="2">Above Average</td>
          <td>4</td>
          <td id="print-aptitude-above"></td>
        </tr>
        <tr>
          <td colspan="2">Average</td>
          <td>3</td>
          <td id="print-aptitude-average"></td>
        </tr>
        <tr>
          <td colspan="2">Below Average</td>
          <td>2</td>
          <td id="print-aptitude-below"></td>
        </tr>
        <tr>
          <td colspan="2">Lowest</td>
          <td>1</td>
          <td id="print-aptitude-lowest"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">EDUCATION (40%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Education</td>
          <td>40</td>
          <td id="print-education-main"></td>
        </tr>
        <tr>
          <td colspan="2">Basic Minimum Requirement per QS (Masteral)</td>
          <td>35</td>
          <td id="print-education-basic"></td>
        </tr>
        <tr>
          <td colspan="2">Additional points: With Doctoral Degree or with Doctoral Units earned</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">Completed 20% of Total number of units required</td>
          <td>1</td>
          <td id="print-education-20"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 40% of Total number of units required</td>
          <td>2</td>
          <td id="print-education-40"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 60% of Total number of units required</td>
          <td>3</td>
          <td id="print-education-60"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 80% of Total number of units required (CAR)</td>
          <td>4</td>
          <td id="print-education-80"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 100% of Total number of units required</td>
          <td>5</td>
          <td id="print-education-100"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">EXPERIENCE (20%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Experience</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">5 yrs. To 10 yrs. Experience</td>
          <td>15</td>
          <td id="print-experience-5-10"></td>
        </tr>
        <tr>
          <td colspan="2">3 yrs. To 4 yrs. Experience</td>
          <td>10</td>
          <td id="print-experience-3-4"></td>
        </tr>
        <tr>
          <td colspan="2">1 yr to 2 yrs. Experience</td>
          <td>5</td>
          <td id="print-experience-1-2"></td>
        </tr>
        <tr>
          <td colspan="2">Additional one point for every year of service (more than 10 yrs. Experience)</td>
          <td>1</td>
          <td id="print-experience-additional"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">TRAINING (10%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Training (40hrs)</td>
          <td>5</td>
          <td id="print-training-basic"></td>
        </tr>
        <tr>
          <td colspan="2">Additional one point for every 8 hrs of training</td>
          <td>1</td>
          <td id="print-training-additional"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">ELIGIBILITY (10%)</td>
        </tr>
        <tr>
          <td colspan="2">RA 1080, CSC Exam, BAR/BOARD Exam</td>
          <td>10</td>
          <td id="print-eligibility"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">OUTSTANDING ACCOMPLISHMENTS (5%)</td>
        </tr>
        <tr>
          <td colspan="2">Citations, Recognitions, Honor Graduates, Board/Bar Topnotcher, CSC Topnotcher</td>
          <td>5</td>
          <td id="print-accomplishments"></td>
        </tr>
      </table>

      <table class="print-table">
        <tr>
          <th colspan="3">SUMMARY OF SCORES</th>
        </tr>
        <tr>
          <th>CRITERIA</th>
          <th style="width: 40px;">%</th>
          <th style="width: 40px;">SCORE</th>
        </tr>
        <tr>
          <td>Potential</td>
          <td>15%</td>
          <td id="print-summary-potential"></td>
        </tr>
        <tr>
          <td>Education</td>
          <td>40%</td>
          <td id="print-summary-education"></td>
        </tr>
        <tr>
          <td>Experience</td>
          <td>20%</td>
          <td id="print-summary-experience"></td>
        </tr>
        <tr>
          <td>Training</td>
          <td>10%</td>
          <td id="print-summary-training"></td>
        </tr>
        <tr>
          <td>Eligibility</td>
          <td>10%</td>
          <td id="print-summary-eligibility"></td>
        </tr>
        <tr>
          <td>Outstanding Accomplishment</td>
          <td>5%</td>
          <td id="print-summary-accomplishment"></td>
        </tr>
        <tr class="print-total-row">
          <td></td>
          <td>100%</td>
          <td id="print-summary-total"></td>
        </tr>
      </table>

      <div class="print-signature">
        <div>(Signature above Printed Name)</div>
        <div>EVALUATOR</div>
        <div>Date: _______________</div>
      </div>

      <div class="print-footer">
        LSPU-HRO-SF-030<br>
        Rev.1<br>
        10 September 2018
      </div>
    </div>
  </div>

  <!-- Non-Teaching Position Print Form (Hidden) -->
  <div id="print-non-teaching-form" class="print-only" style="display: none;">
    <div class="print-form">
      <div class="print-header">
        <div class="logo-container">
          <img src="images/lspulogo.png" alt="LSPU Logo" class="print-logo">
          <div>
            <div class="university-name">Republic of the Philippines</div>
            <div class="university-name">Laguna State Polytechnic University</div>
            <div class="university-name">Province of Laguna</div>
          </div>
        </div>
        
        <div class="print-title">PERSONNEL SELECTION BOARD<br>ASSESSMENT CRITERIA<br>NEW APPLICANTS FOR NON-TEACHING POSITION</div>
      </div>

      <div class="print-applicant-info">
        <div><strong>Name of Applicant:</strong> <span id="nt-print-name"></span></div>
        <div><strong>Position:</strong> <span id="nt-print-position"></span></div>
        <div><strong>Date:</strong> _______________</div>
      </div>

      <table class="print-table">
        <tr>
          <th colspan="4">CRITERIA</th>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">I. POTENTIAL (15%)</td>
        </tr>
        <tr>
          <td colspan="2"><strong>Interview (10%)</strong></td>
          <td style="width: 15mm;">RATING</td>
          <td style="width: 15mm;">SCORE</td>
        </tr>
        <tr>
          <td colspan="2">Personality (Pleasing personal appearance)</td>
          <td>10</td>
          <td id="nt-print-personality"></td>
        </tr>
        <tr>
          <td colspan="2">Communication Skills (Smart, good communication skills and speaks with courtesy and refined manner)</td>
          <td>10</td>
          <td id="nt-print-communication"></td>
        </tr>
        <tr>
          <td colspan="2">Analytical Skills (Shows insight when expressing ideas, intelligence, emotional stability and self controlled)</td>
          <td>10</td>
          <td id="nt-print-analytical"></td>
        </tr>
        <tr>
          <td colspan="2">Achievement Orientation (Result-Oriented, Shows creativity and innovation)</td>
          <td>10</td>
          <td id="nt-print-achievement"></td>
        </tr>
        <tr>
          <td colspan="2">Leadership/Management (Planning and organizing skills)</td>
          <td>10</td>
          <td id="nt-print-leadership"></td>
        </tr>
        <tr>
          <td colspan="2">Relationship Management (Does the candidate seek contacts or networks and pursue friendly relationships with people?, Builds report through formal or informal /casual contacts with people who may be valuable to the organization)</td>
          <td>10</td>
          <td id="nt-print-relationship"></td>
        </tr>
        <tr>
          <td colspan="2">Job Fit (Flexibility, creativity and resourcefulness, Does the candidate show commitment/seem to stay long?)</td>
          <td>10</td>
          <td id="nt-print-jobfit"></td>
        </tr>
        <tr class="print-total-row">
          <td colspan="2">Total Score - 70 points</td>
          <td>70</td>
          <td id="nt-print-interview-total"></td>
        </tr>
        <tr>
          <td colspan="2"><strong>Aptitude Test (5%)</strong></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">Superior</td>
          <td>5</td>
          <td id="nt-print-aptitude-superior"></td>
        </tr>
        <tr>
          <td colspan="2">Above Average</td>
          <td>4</td>
          <td id="nt-print-aptitude-above"></td>
        </tr>
        <tr>
          <td colspan="2">Average</td>
          <td>3</td>
          <td id="nt-print-aptitude-average"></td>
        </tr>
        <tr>
          <td colspan="2">Below Average</td>
          <td>2</td>
          <td id="nt-print-aptitude-below"></td>
            </tr>
             <tr>
          <td colspan="2">Lowest</td>
          <td>1</td>
          <td id="nt-print-aptitude-lowest"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">II. EDUCATION (40%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Education</td>
          <td>40</td>
          <td id="nt-print-education-main"></td>
        </tr>
        <tr>
          <td colspan="2">Basic Minimum Requirement per QS</td>
          <td>30</td>
          <td id="nt-print-education-basic"></td>
        </tr>
        <tr>
          <td colspan="2">Additional points: With Masteral Degree or with Masteral Units earned</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">Completed 25% of Total number of units required</td>
          <td>1</td>
          <td id="nt-print-education-25"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 50% of Total number of units required</td>
          <td>2</td>
          <td id="nt-print-education-50"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 75% of Total number of units required</td>
          <td>3</td>
          <td id="nt-print-education-75"></td>
        </tr>
        <tr>
          <td colspan="2">Completed Academic Requirements (CAR)</td>
          <td>4</td>
          <td id="nt-print-education-car"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 100% of Total number of units required</td>
          <td>5</td>
          <td id="nt-print-education-100"></td>
        </tr>
        <tr>
          <td colspan="2">Additional points: With Doctoral Degree or with Doctoral Units earned</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">Completed 25% of Total number of units required</td>
          <td>6</td>
          <td id="nt-print-education-d25"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 50% of Total number of units required</td>
          <td>7</td>
          <td id="nt-print-education-d50"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 75% of Total number of units required</td>
          <td>8</td>
          <td id="nt-print-education-d75"></td>
        </tr>
        <tr>
          <td colspan="2">Completed Academic Requirements (CAR)</td>
          <td>9</td>
          <td id="nt-print-education-dcar"></td>
        </tr>
        <tr>
          <td colspan="2">Completed 100% of Total number of units required</td>
          <td>10</td>
          <td id="nt-print-education-d100"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">III. EXPERIENCE (20%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Experience</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2">5 yrs. To 10 yrs. Experience</td>
          <td>15</td>
          <td id="nt-print-experience-5-10"></td>
        </tr>
        <tr>
          <td colspan="2">3 yrs. To 4 yrs. Experience</td>
          <td>10</td>
          <td id="nt-print-experience-3-4"></td>
        </tr>
        <tr>
          <td colspan="2">1 yr to 2 yrs. Experience</td>
          <td>5</td>
          <td id="nt-print-experience-1-2"></td>
        </tr>
        <tr>
          <td colspan="2">Additional one point for every year of service (more than 10 yrs. Experience)</td>
          <td>1</td>
          <td id="nt-print-experience-additional"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">IV. TRAINING (10%)</td>
        </tr>
        <tr>
          <td colspan="2">Relevance and Appropriateness of Training (40hrs)</td>
          <td>5</td>
          <td id="nt-print-training-basic"></td>
        </tr>
        <tr>
          <td colspan="2">Additional one point for every 8 hrs of training</td>
          <td>1</td>
          <td id="nt-print-training-additional"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">V. ELIGIBILITY (10%)</td>
        </tr>
        <tr>
          <td colspan="2">RA 1080, CSC Exam, BAR/BOARD Exam</td>
          <td>10</td>
          <td id="nt-print-eligibility"></td>
        </tr>
        <tr class="print-criteria-header">
          <td colspan="4">VI. OUTSTANDING ACCOMPLISHMENTS (5%)</td>
        </tr>
        <tr>
          <td colspan="2">Citations, Recognitions, Honor Graduates, Board/Bar Topnotcher, CSC Topnotcher</td>
          <td>5</td>
          <td id="nt-print-accomplishments"></td>
        </tr>
      </table>

      <table class="print-table">
        <tr>
          <th colspan="3">SUMMARY OF SCORES</th>
        </tr>
        <tr>
          <th>CRITERIA</th>
          <th style="width: 15mm;">%</th>
          <th style="width: 15mm;">SCORE</th>
        </tr>
        <tr>
          <td>I. Potential</td>
          <td>15%</td>
          <td id="nt-print-summary-potential"></td>
        </tr>
        <tr>
          <td>II. Education</td>
          <td>40%</td>
          <td id="nt-print-summary-education"></td>
        </tr>
        <tr>
          <td>III. Experience</td>
          <td>20%</td>
          <td id="nt-print-summary-experience"></td>
        </tr>
        <tr>
          <td>IV. Training</td>
          <td>10%</td>
          <td id="nt-print-summary-training"></td>
        </tr>
        <tr>
          <td>V. Eligibility</td>
          <td>10%</td>
          <td id="nt-print-summary-eligibility"></td>
        </tr>
        <tr>
          <td>VI. Outstanding Accomplishment</td>
          <td>5%</td>
          <td id="nt-print-summary-accomplishment"></td>
        </tr>
        <tr class="print-total-row">
          <td></td>
          <td>100%</td>
          <td id="nt-print-summary-total"></td>
        </tr>
      </table>

      <div class="print-signature">
        <div>(Signature above Printed Name)</div>
        <div>EVALUATOR</div>
        <div>Date: _______________</div>
      </div>

      <div class="print-footer">
        LSPU-HRO-SF-030<br>
        Rev.1<br>
        10 September 2018
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- AOS (Animate On Scroll) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
  <!-- Custom JS -->
  <script>
    // Global variables to store current evaluation data
    let currentApplicationId = null;
    let currentCategory = null;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            once: true
        });

        // Initialize DataTable
        $('#applicantsTable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search applicants...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Mobile sidebar toggle
        function checkMobileView() {
            if (window.innerWidth < 992) {
                document.getElementById('mobileSidebarToggle').style.display = 'flex';
            } else {
                document.getElementById('mobileSidebarToggle').style.display = 'none';
            }
        }
        
        // Initial check
        checkMobileView();
        
        // Check on resize
        window.addEventListener('resize', checkMobileView);

        // Show print modal if we just evaluated someone
        <?php if ($showPrintModal): ?>
            showPrintModal(<?= $lastEvaluated ?>);
        <?php endif; ?>
    });

    // Add Evaluation Modal Functions
    function showAddEvaluationModal() {
        document.getElementById('add-evaluation-modal').style.display = 'block';
    }

    function closeAddEvaluationModal() {
        document.getElementById('add-evaluation-modal').style.display = 'none';
    }

    function startNewEvaluation(applicationId, name, position, category) {
        // Set form values
        document.getElementById('application-id').value = applicationId;
        document.getElementById('applicant-name').value = name;
        document.getElementById('applicant-position').value = position;
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = new Date().toLocaleDateString();
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Set default values for new evaluation
        document.getElementById('interview-personality').value = 8;
        document.getElementById('interview-communication').value = 8;
        document.getElementById('interview-analytical').value = 8;
        document.getElementById('interview-achievement').value = 8;
        document.getElementById('interview-leadership').value = 8;
        document.getElementById('interview-relationship').value = 8;
        document.getElementById('interview-jobfit').value = 8;
        document.getElementById('aptitude-test').value = 4;
        
        if (category === 'Teaching') {
            document.getElementById('education-rating').value = 35;
            document.getElementById('education-units').value = 0;
        } else {
            document.getElementById('nt-education-rating').value = 30;
            document.getElementById('nt-education-units').value = 0;
        }
        
        document.getElementById('experience-rating').value = 15;
        document.getElementById('additional-experience').value = 0;
        document.getElementById('training-rating').value = 5;
        document.getElementById('eligibility-rating').value = 10;
        document.getElementById('accomplishment-rating').value = 0;
        
        // Open evaluation modal
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    function startEvaluation() {
        const select = document.getElementById('applicant-select');
        const applicationId = select.value;
        const selectedOption = select.options[select.selectedIndex];
        const category = selectedOption.getAttribute('data-category');
        
        if (!applicationId) {
            alert('Please select an applicant to evaluate');
            return;
        }
        
        // Set form values
        document.getElementById('application-id').value = applicationId;
        document.getElementById('applicant-name').value = selectedOption.text;
        document.getElementById('applicant-position').value = '';
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = new Date().toLocaleDateString();
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Set default values for new evaluation
        document.getElementById('interview-personality').value = 8;
        document.getElementById('interview-communication').value = 8;
        document.getElementById('interview-analytical').value = 8;
        document.getElementById('interview-achievement').value = 8;
        document.getElementById('interview-leadership').value = 8;
        document.getElementById('interview-relationship').value = 8;
        document.getElementById('interview-jobfit').value = 8;
        document.getElementById('aptitude-test').value = 4;
        
        if (category === 'Teaching') {
            document.getElementById('education-rating').value = 35;
            document.getElementById('education-units').value = 0;
        } else {
            document.getElementById('nt-education-rating').value = 30;
            document.getElementById('nt-education-units').value = 0;
        }
        
        document.getElementById('experience-rating').value = 15;
        document.getElementById('additional-experience').value = 0;
        document.getElementById('training-rating').value = 5;
        document.getElementById('eligibility-rating').value = 10;
        document.getElementById('accomplishment-rating').value = 0;
        
        // Close add evaluation modal and open evaluation modal
        closeAddEvaluationModal();
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    // Evaluation Modal Functions
    function editApplicant(id, name, position, category, date, score) {
        document.getElementById('application-id').value = id;
        document.getElementById('applicant-name').value = name;
        document.getElementById('applicant-position').value = position;
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = date;
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Fetch existing evaluation data if available
        fetchEvaluationData(id, category);
        
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    function fetchEvaluationData(applicationId, category) {
        // Make an AJAX request to fetch evaluation data
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: applicationId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    // Set form values
                    document.getElementById('interview-personality').value = evalData.personality || 8;
                    document.getElementById('interview-communication').value = evalData.communication || 8;
                    document.getElementById('interview-analytical').value = evalData.analytical || 8;
                    document.getElementById('interview-achievement').value = evalData.achievement || 8;
                    document.getElementById('interview-leadership').value = evalData.leadership || 8;
                    document.getElementById('interview-relationship').value = evalData.relationship || 8;
                    document.getElementById('interview-jobfit').value = evalData.jobfit || 8;
                    document.getElementById('aptitude-test').value = evalData.aptitude || 4;
                    
                    if (category === 'Teaching') {
                        document.getElementById('education-rating').value = evalData.education_rating || 35;
                        document.getElementById('education-units').value = evalData.education_units || 0;
                    } else {
                        document.getElementById('nt-education-rating').value = evalData.education_rating || 30;
                        document.getElementById('nt-education-units').value = evalData.education_units || 0;
                    }
                    
                    // Fix for experience rating issue
                    let experienceRating = evalData.experience_rating || 15;
                    if (experienceRating === 15) {
                        document.getElementById('experience-rating').value = 15;
                    } else if (experienceRating === 10) {
                        document.getElementById('experience-rating').value = 10;
                    } else {
                        document.getElementById('experience-rating').value = 5;
                    }
                    
                    document.getElementById('additional-experience').value = evalData.additional_experience || 0;
                    document.getElementById('training-rating').value = evalData.training_rating || 5;
                    document.getElementById('eligibility-rating').value = evalData.eligibility_rating || 10;
                    document.getElementById('accomplishment-rating').value = evalData.accomplishment_rating || 0;
                } else {
                    // Set default values if no evaluation exists
                    document.getElementById('interview-personality').value = 8;
                    document.getElementById('interview-communication').value = 8;
                    document.getElementById('interview-analytical').value = 8;
                    document.getElementById('interview-achievement').value = 8;
                    document.getElementById('interview-leadership').value = 8;
                    document.getElementById('interview-relationship').value = 8;
                    document.getElementById('interview-jobfit').value = 8;
                    document.getElementById('aptitude-test').value = 4;
                    
                    if (category === 'Teaching') {
                        document.getElementById('education-rating').value = 35;
                        document.getElementById('education-units').value = 0;
                    } else {
                        document.getElementById('nt-education-rating').value = 30;
                        document.getElementById('nt-education-units').value = 0;
                    }
                    
                    document.getElementById('experience-rating').value = 15;
                    document.getElementById('additional-experience').value = 0;
                    document.getElementById('training-rating').value = 5;
                    document.getElementById('eligibility-rating').value = 10;
                    document.getElementById('accomplishment-rating').value = 0;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function closeModal() {
        document.getElementById('evaluation-modal').style.display = 'none';
    }

    // Delete Confirmation Modal Functions
    function confirmDelete(applicationId, name) {
        document.getElementById('delete-confirmation-message').textContent = 
            `Are you sure you want to delete the evaluation for ${name}?`;
        document.getElementById('delete-application-id').value = applicationId;
        document.getElementById('delete-confirmation-modal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('delete-confirmation-modal').style.display = 'none';
    }

    // View Evaluation Modal Functions
    function viewEvaluation(id, name, position, category) {
        currentApplicationId = id;
        currentCategory = category;
        
        // Make an AJAX request to fetch evaluation data
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: id },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    // Calculate scores
                    const interviewTotal = evalData.personality + evalData.communication +
                                          evalData.analytical + evalData.achievement +
                                          evalData.leadership + evalData.relationship +
                                          evalData.jobfit;

                    let potentialScore, educationScore;

                    // Interview part: 70/70 = 10%
                    const interviewPercent = (interviewTotal / 70) * 10;

                    // Aptitude part: 5/5 = 5%
                    const aptitudePercent = (evalData.aptitude / 5) * 5;

                    // Total potential = Interview (10%) + Aptitude (5%) = 15%
                    if (category === 'Teaching') {
                        potentialScore = interviewPercent + aptitudePercent;
                        educationScore = evalData.education_rating + evalData.education_units;
                    } else {
                        potentialScore = interviewPercent + aptitudePercent;
                        educationScore = evalData.education_rating + evalData.education_units;
                    }
                    
                    const experienceScore = evalData.experience_rating + evalData.additional_experience;
                    const trainingScore = evalData.training_rating;
                    const eligibilityScore = evalData.eligibility_rating;
                    const accomplishmentScore = evalData.accomplishment_rating;
                    
                    const totalScore = potentialScore + educationScore + experienceScore + 
                                      trainingScore + eligibilityScore + accomplishmentScore;
                    
                    // Build the view content
                    let viewContent = `
                        <div class="view-section">
                            <h4>Applicant Information</h4>
                            <div class="view-row">
                                <div class="view-label">Name:</div>
                                <div class="view-value">${name}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Position:</div>
                                <div class="view-value">${position}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Category:</div>
                                <div class="view-value">${category}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Interview Scores</h4>
                            <div class="view-row">
                                <div class="view-label">Personality:</div>
                                <div class="view-value">${evalData.personality}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Communication:</div>
                                <div class="view-value">${evalData.communication}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Analytical Skills:</div>
                                <div class="view-value">${evalData.analytical}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Achievement Orientation:</div>
                                <div class="view-value">${evalData.achievement}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Leadership/Management:</div>
                                <div class="view-value">${evalData.leadership}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Relationship Management:</div>
                                <div class="view-value">${evalData.relationship}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Job Fit:</div>
                                <div class="view-value">${evalData.jobfit}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Aptitude Test:</div>
                                <div class="view-value">${getAptitudeLabel(evalData.aptitude)} (${evalData.aptitude})</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Interview Total:</div>
                                <div class="view-value">${interviewTotal}/70</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Education</h4>
                            <div class="view-row">
                                <div class="view-label">Education Rating:</div>
                                <div class="view-value">${evalData.education_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Additional Units:</div>
                                <div class="view-value">${evalData.education_units}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Experience & Training</h4>
                            <div class="view-row">
                                <div class="view-label">Experience Rating:</div>
                                <div class="view-value">${evalData.experience_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Additional Experience:</div>
                                <div class="view-value">${evalData.additional_experience}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Training Rating:</div>
                                <div class="view-value">${evalData.training_rating}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Other Criteria</h4>
                            <div class="view-row">
                                <div class="view-label">Eligibility Rating:</div>
                                <div class="view-value">${evalData.eligibility_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Accomplishment Rating:</div>
                                <div class="view-value">${evalData.accomplishment_rating}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Score Summary</h4>
                            <div class="view-row">
                                <div class="view-label">Potential Score (15%):</div>
                                <div class="view-value">${potentialScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Education Score (40%):</div>
                                <div class="view-value">${educationScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Experience Score (20%):</div>
                                <div class="view-value">${experienceScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Training Score (10%):</div>
                                <div class="view-value">${trainingScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Eligibility Score (10%):</div>
                                <div class="view-value">${eligibilityScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Accomplishment Score (5%):</div>
                                <div class="view-value">${accomplishmentScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label"><strong>Total Score:</strong></div>
                                <div class="view-value"><strong>${totalScore.toFixed(2)}/100</strong></div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('view-content').innerHTML = viewContent;
                    document.getElementById('view-modal').style.display = 'block';
                } else {
                    alert('Error loading evaluation data: ' + (data.message || 'No evaluation data found'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function getAptitudeLabel(score) {
        switch(parseInt(score)) {
            case 5: return 'Superior';
            case 4: return 'Above Average';
            case 3: return 'Average';
            case 2: return 'Below Average';
            case 1: return 'Lowest';
            default: return 'Not Rated';
        }
    }

    function closeViewModal() {
        document.getElementById('view-modal').style.display = 'none';
    }

    function printFromView() {
        closeViewModal();
        showPrintModal(currentApplicationId);
    }

    // Print Modal Functions
    function showPrintModal(id, name = null, position = null, category = null) {
        // If we're calling from PHP with just an ID, we need to fetch the details
        if (name === null) {
            $.ajax({
                url: 'get_application_details.php',
                type: 'GET',
                data: { application_id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        loadPrintForm(id, data.application.name, data.application.position, data.application.category);
                    } else {
                        alert('Error loading application details: ' + (data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching application details:', error);
                    alert('Error loading application details. Please try again.');
                }
            });
        } else {
            loadPrintForm(id, name, position, category);
        }
    }

    function loadPrintForm(id, name, position, category) {
        // First hide both forms
        document.getElementById('print-teaching-form').style.display = 'none';
        document.getElementById('print-non-teaching-form').style.display = 'none';
        
        // Fetch evaluation data for printing
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: id },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    if (category === 'Teaching') {
                        // Show teaching form
                        const teachingForm = document.getElementById('print-teaching-form');
                        teachingForm.style.display = 'block';
                        
                        // Basic info
                        document.getElementById('print-name').textContent = name;
                        document.getElementById('print-position').textContent = position;
                        
                        // Set evaluation data
                        document.getElementById('print-personality').textContent = evalData.personality;
                        document.getElementById('print-communication').textContent = evalData.communication;
                        document.getElementById('print-analytical').textContent = evalData.analytical;
                        document.getElementById('print-achievement').textContent = evalData.achievement;
                        document.getElementById('print-leadership').textContent = evalData.leadership;
                        document.getElementById('print-relationship').textContent = evalData.relationship;
                        document.getElementById('print-jobfit').textContent = evalData.jobfit;
                        
                        // Calculate and set totals
                        const interviewTotal = evalData.personality + evalData.communication + 
                                              evalData.analytical + evalData.achievement + 
                                              evalData.leadership + evalData.relationship + 
                                              evalData.jobfit;
                        
                        document.getElementById('print-interview-total').textContent = interviewTotal;
                        
                        // Set aptitude test
                        const aptitudeOptions = ['superior', 'above', 'average', 'below', 'lowest'];
                        const aptitudeValue = evalData.aptitude;
                        document.getElementById(`print-aptitude-${aptitudeOptions[5 - aptitudeValue]}`).textContent = aptitudeValue;
                        
                        // Set education
                        document.getElementById('print-education-main').textContent = evalData.education_rating + evalData.education_units;
                        document.getElementById('print-education-basic').textContent = evalData.education_rating;
                        
                        if (evalData.education_units > 0) {
                            document.getElementById(`print-education-${evalData.education_units * 20}`).textContent = evalData.education_units;
                        }
                        
                        // Set experience - fixed the experience rating mapping
                        const experienceRating = evalData.experience_rating;
                        let experienceOption = '';
                        if (experienceRating === 15) experienceOption = '5-10';
                        else if (experienceRating === 10) experienceOption = '3-4';
                        else experienceOption = '1-2';
                        
                        document.getElementById(`print-experience-${experienceOption}`).textContent = experienceRating;
                        document.getElementById('print-experience-additional').textContent = evalData.additional_experience;
                        
                        // Set training
                        document.getElementById('print-training-basic').textContent = 5;
                        document.getElementById('print-training-additional').textContent = evalData.training_rating - 5;
                        
                        // Set eligibility and accomplishments
                        document.getElementById('print-eligibility').textContent = evalData.eligibility_rating;
                        document.getElementById('print-accomplishments').textContent = evalData.accomplishment_rating;
                        
                        // Calculate summary scores
                        calculateTeachingScores(evalData);
                        
                        // Load into preview
                        document.getElementById('print-preview').innerHTML = teachingForm.innerHTML;
                    } else {
                        // Show non-teaching form
                        const nonTeachingForm = document.getElementById('print-non-teaching-form');
                        nonTeachingForm.style.display = 'block';
                        
                        // Basic info
                        document.getElementById('nt-print-name').textContent = name;
                        document.getElementById('nt-print-position').textContent = position;
                        
                        // Set evaluation data
                        document.getElementById('nt-print-personality').textContent = evalData.personality;
                        document.getElementById('nt-print-communication').textContent = evalData.communication;
                        document.getElementById('nt-print-analytical').textContent = evalData.analytical;
                        document.getElementById('nt-print-achievement').textContent = evalData.achievement;
                        document.getElementById('nt-print-leadership').textContent = evalData.leadership;
                        document.getElementById('nt-print-relationship').textContent = evalData.relationship;
                        document.getElementById('nt-print-jobfit').textContent = evalData.jobfit;
                        
                        // Calculate and set totals
                        const interviewTotal = evalData.personality + evalData.communication + 
                                              evalData.analytical + evalData.achievement + 
                                              evalData.leadership + evalData.relationship + 
                                              evalData.jobfit;
                        
                        document.getElementById('nt-print-interview-total').textContent = interviewTotal;
                        
                        // Set aptitude test
                        const aptitudeOptions = ['superior', 'above', 'average', 'below', 'lowest'];
                        const aptitudeValue = evalData.aptitude;
                        document.getElementById(`nt-print-aptitude-${aptitudeOptions[5 - aptitudeValue]}`).textContent = aptitudeValue;
                        
                        // Set education
                        document.getElementById('nt-print-education-main').textContent = evalData.education_rating + evalData.education_units;
                        document.getElementById('nt-print-education-basic').textContent = evalData.education_rating;
                        
                        if (evalData.education_units > 0) {
                            if (evalData.education_units <= 5) {
                                document.getElementById(`nt-print-education-${evalData.education_units * 25}`).textContent = evalData.education_units;
                            } else {
                                document.getElementById(`nt-print-education-d${(evalData.education_units - 5) * 25}`).textContent = evalData.education_units;
                            }
                        }
                        
                        // Set experience - fixed the experience rating mapping
                        const experienceRating = evalData.experience_rating;
                        let experienceOption = '';
                        if (experienceRating === 15) experienceOption = '5-10';
                        else if (experienceRating === 10) experienceOption = '3-4';
                        else experienceOption = '1-2';
                        
                        document.getElementById(`nt-print-experience-${experienceOption}`).textContent = experienceRating;
                        document.getElementById('nt-print-experience-additional').textContent = evalData.additional_experience;
                        
                        // Set training
                        document.getElementById('nt-print-training-basic').textContent = 5;
                        document.getElementById('nt-print-training-additional').textContent = evalData.training_rating - 5;
                        
                        // Set eligibility and accomplishments
                        document.getElementById('nt-print-eligibility').textContent = evalData.eligibility_rating;
                        document.getElementById('nt-print-accomplishments').textContent = evalData.accomplishment_rating;
                        
                        // Calculate summary scores
                        calculateNonTeachingScores(evalData);
                        
                        // Load into preview
                        document.getElementById('print-preview').innerHTML = nonTeachingForm.innerHTML;
                    }
                    
                    // Show the print modal
                    document.getElementById('print-modal').style.display = 'block';
                } else {
                    alert('Error loading evaluation data: ' + (data.message || 'No evaluation data found'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function closePrintModal() {
        document.getElementById('print-modal').style.display = 'none';
    }

    function printEvaluationForm() {
        // First hide both forms
        document.getElementById('print-teaching-form').style.display = 'none';
        document.getElementById('print-non-teaching-form').style.display = 'none';
        
        // Determine which form to print based on the preview content
        const previewContent = document.getElementById('print-preview').innerHTML;
        const isTeachingForm = previewContent.includes('NEW APPLICANTS FOR TEACHING POSITION');
        
        if (isTeachingForm) {
            document.getElementById('print-teaching-form').style.display = 'block';
        } else {
            document.getElementById('print-non-teaching-form').style.display = 'block';
        }
        
        // Print only the active form
        window.print();
    }

    function calculateTeachingScores(evalData) {
        // Calculate potential score (15% of total)
        const interviewTotal = evalData.personality + evalData.communication + 
                              evalData.analytical + evalData.achievement + 
                              evalData.leadership + evalData.relationship + 
                              evalData.jobfit;
        
        const interviewScorePercent = (interviewTotal / 70) * 10; // Max 10 points
        const aptitudeScorePercent = (evalData.aptitude / 5) * 5; // Max 5 points

        const potentialScore = interviewScorePercent + aptitudeScorePercent;
        
        // Calculate education score (40% of total)
        const educationScore = evalData.education_rating + evalData.education_units;
        
        // Calculate experience score (20% of total)
        const experienceScore = evalData.experience_rating + evalData.additional_experience;
        
        // Calculate training score (10% of total)
        const trainingScore = evalData.training_rating;
        
        // Calculate eligibility score (10% of total)
        const eligibilityScore = evalData.eligibility_rating;
        
        // Calculate accomplishment score (5% of total)
        const accomplishmentScore = evalData.accomplishment_rating;
        
        // Calculate total score
        const totalScore = potentialScore + educationScore + experienceScore + 
                          trainingScore + eligibilityScore + accomplishmentScore;
        
        // Update summary table
        document.getElementById('print-summary-potential').textContent = potentialScore.toFixed(2);
        document.getElementById('print-summary-education').textContent = educationScore.toFixed(2);
        document.getElementById('print-summary-experience').textContent = experienceScore.toFixed(2);
        document.getElementById('print-summary-training').textContent = trainingScore.toFixed(2);
        document.getElementById('print-summary-eligibility').textContent = eligibilityScore.toFixed(2);
        document.getElementById('print-summary-accomplishment').textContent = accomplishmentScore.toFixed(2);
        document.getElementById('print-summary-total').textContent = totalScore.toFixed(2);
    }

    function calculateNonTeachingScores(evalData) {
        // Calculate potential score (15% of total)
        const interviewTotal = evalData.personality + evalData.communication + 
                              evalData.analytical + evalData.achievement + 
                              evalData.leadership + evalData.relationship + 
                              evalData.jobfit;
        
        const interviewScorePercent = (interviewTotal / 70) * 10; // Max 10 points
        const aptitudeScorePercent = (evalData.aptitude / 5) * 5; // Max 5 points

        const potentialScore = interviewScorePercent + aptitudeScorePercent;
        
        // Calculate education score (40% of total)
        const educationScore = evalData.education_rating + evalData.education_units;
        
        // Calculate experience score (20% of total)
        const experienceScore = evalData.experience_rating + evalData.additional_experience;
        
        // Calculate training score (10% of total)
        const trainingScore = evalData.training_rating;
        
        // Calculate eligibility score (10% of total)
        const eligibilityScore = evalData.eligibility_rating;
        
        // Calculate accomplishment score (5% of total)
        const accomplishmentScore = evalData.accomplishment_rating;
        
        // Calculate total score
        const totalScore = potentialScore + educationScore + experienceScore + 
                          trainingScore + eligibilityScore + accomplishmentScore;
        
        // Update summary table
        document.getElementById('nt-print-summary-potential').textContent = potentialScore.toFixed(2);
        document.getElementById('nt-print-summary-education').textContent = educationScore.toFixed(2);
        document.getElementById('nt-print-summary-experience').textContent = experienceScore.toFixed(2);
        document.getElementById('nt-print-summary-training').textContent = trainingScore.toFixed(2);
        document.getElementById('nt-print-summary-eligibility').textContent = eligibilityScore.toFixed(2);
        document.getElementById('nt-print-summary-accomplishment').textContent = accomplishmentScore.toFixed(2);
        document.getElementById('nt-print-summary-total').textContent = totalScore.toFixed(2);
    }
  </script>
</body>
</html>