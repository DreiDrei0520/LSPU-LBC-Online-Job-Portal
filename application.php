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

// Load Composer's autoloader
require 'vendor/autoload.php';

// Function to send status emails
function sendStatusEmail($email, $first_name, $last_name, $position, $status, $interviewDate = null, $applicationNumber = null) {
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
                'body' => "Dear $first_name $last_name,<br><br>We have received your application (Ref: $applicationNumber) for the position of $position and it is currently pending review."
            ],
            'Applied' => [
                'subject' => 'Application Received',
                'body' => "Dear $first_name $last_name,<br><br>We have received your application (Ref: $applicationNumber) for the position of $position.<br><br>Our team will review your application and get back to you soon."
            ],
            'Under Review' => [
                'subject' => 'Application Under Review',
                'body' => "Dear $first_name $last_name,<br><br>Your application (Ref: $applicationNumber) for $position is currently under review.<br><br>We appreciate your patience during this process."
            ],
            'Interview Scheduled' => [
                'subject' => 'Interview Scheduled',
                'body' => "Dear $first_name $last_name,<br><br>Congratulations! You've been selected for an interview for the $position position (Ref: $applicationNumber).<br><br>Interview Date: " . ($interviewDate ? date('F j, Y \a\t g:i A', strtotime($interviewDate)) : 'To be determined') . "<br><br>Please arrive 15 minutes early."
            ],
            'Under Interviews' => [
                'subject' => 'Interview Process Started',
                'body' => "Dear $first_name $last_name,<br><br>Your application (Ref: $applicationNumber) for $position is now in the interview phase.<br><br>We'll contact you soon with more details."
            ],
            'Interviewed' => [
                'subject' => 'Interview Completed',
                'body' => "Dear $first_name $last_name,<br><br>Thank you for attending the interview for $position (Ref: $applicationNumber).<br><br>We're currently evaluating all candidates and will notify you of our decision soon."
            ],
            'Hired' => [
                'subject' => 'Congratulations! You\'re Hired',
                'body' => "Dear $first_name $last_name,<br><br>We're excited to inform you that you've been selected for the $position position (Ref: $applicationNumber)!<br><br>Welcome to our team!"
            ],
            'Not Selected' => [
                'subject' => 'Application Update',
                'body' => "Dear $first_name $last_name,<br><br>Thank you for applying for the $position position (Ref: $applicationNumber).<br><br>After careful consideration, we've decided to move forward with other candidates at this time."
            ],
            'New Job' => [
                'subject' => 'New Job Opportunities Available',
                'body' => "Dear $first_name $last_name,<br><br>New job opportunities matching your profile are now available.<br><br>Log in to your account to view them."
            ],
            'Exam Completed' => [
                'subject' => 'Exam Process Completed',
                'body' => "Dear $first_name $last_name,<br><br>Thank you for completing the exam for $position (Ref: $applicationNumber).<br><br>We're currently evaluating all candidates and will notify you of our decision soon."
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
    header('Location: application.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$user = null;
$applicationCount = 0;
$nextStep = 'Pending';
$interviewNote = 'No upcoming interview.';
$interviewDate = null;
$currentPosition = '';
$successMsg = "";
$errorMsg = "";
$selectedPosition = "";

if ($userId) {
    // Fetch user details
    $stmt = $conn->prepare("SELECT user_id, profile_pic, first_name, middle_name, last_name, email, phone FROM users WHERE user_id = ?");
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
    $stmt = $conn->prepare("SELECT a.status, a.interview_date, j.title AS job_title, a.application_number 
                           FROM applications a
                           JOIN job_positions j ON a.position_id = j.position_id
                           WHERE a.user_id = ? 
                           ORDER BY a.submitted_at DESC 
                           LIMIT 1");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nextStep = $row['status'] ?? 'Pending';
        $currentPosition = $row['job_title'] ?? '';
        $applicationNumber = $row['application_number'] ?? '';
        if (!empty($row['interview_date'])) {
            $interviewDate = $row['interview_date'];
            $date = date('F j, Y \a\t g:i A', strtotime($interviewDate));
            $interviewNote = "Interview scheduled: $date";
        }
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['position'])) {
    $user_id = $userId;
    $position = trim($_POST['position']);
    $selectedPosition = $position;
    $phone = $_POST['phone'] ?? '';
    
    // Process work experience data
    $work_experience = [];
    if (isset($_POST['work_position'])) {
        foreach ($_POST['work_position'] as $index => $workPos) {
           $work_experience[] = [
            'position' => trim($workPos),
            'company' => trim($_POST['work_company'][$index]),
            'start_date' => $_POST['work_start_date'][$index],
            'end_date' => $_POST['work_end_date'][$index],
            'salary' => trim($_POST['work_salary'][$index]),
            'salary_grade' => trim($_POST['work_salary_grade'][$index] ?? ''), // New field
            'status' => trim($_POST['work_status'][$index]),
            'govt_service' => $_POST['work_govt_service'][$index]
];
        }
    }
    $work_experience_json = json_encode($work_experience);
    
    // Process education data
    $education = [];
    if (isset($_POST['education_level'])) {
        foreach ($_POST['education_level'] as $index => $level) {
            $education[] = [
                'level' => trim($_POST['education_level'][$index]),
                'school' => trim($_POST['education_school'][$index]),
                'degree' => trim($_POST['education_degree'][$index]),
                'start_date' => $_POST['education_start_date'][$index],
                'end_date' => $_POST['education_end_date'][$index],
                'highest_level' => trim($_POST['education_highest_level'][$index] ?? ''),
                'year_graduated' => trim($_POST['education_year_graduated'][$index]),
                'honors' => trim($_POST['education_honors'][$index] ?? '')
            ];
        }
    }
    $education_json = json_encode($education);

    // Generate application number
    $applicationNumber = 'APP-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    // Get position ID - with improved validation
    $position_id = 0;
    $stmt = $conn->prepare("SELECT position_id FROM job_positions WHERE title = ? AND status = 'Open'");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("s", $position);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $position_id = $row['position_id'];
    } else {
        // If position not found, show error message
        $errorMsg = "The position you selected ('" . htmlspecialchars($position) . "') is not currently available. Please select from the available positions.";
        $stmt->close();
        goto render_page; // Skip the rest of the submission process
    }
    $stmt->close();

    // File upload handling
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $errorMsg = "Failed to create upload directory.";
            goto render_page;
        }
    }

    $requiredFiles = ['resume', 'application_letter', 'personal_data_sheet', 'transcript_of_records', 'proof_of_eligibility'];
    $optionalFiles = ['other_documents'];
    $filePaths = [];
    $uploadErrors = [];

    // Process required files
    foreach ($requiredFiles as $field) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            $uploadErrors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            continue;
        }

        $file = $_FILES[$field];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors[] = "Error uploading " . str_replace('_', ' ', $field) . ": " . $file['error'];
            continue;
        }

        // Validate file type and size
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $uploadErrors[] = "Invalid file type for " . str_replace('_', ' ', $field) . ". Only PDF, Word, and Excel documents are allowed.";
            continue;
        }
        
        if ($file['size'] > $maxSize) {
            $uploadErrors[] = "File too large for " . str_replace('_', ' ', $field) . ". Maximum size is 5MB.";
            continue;
        }

        $filename = uniqid() . '-' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $filePaths[$field] = $targetPath;
        } else {
            $uploadErrors[] = "Failed to upload " . str_replace('_', ' ', $field);
        }
    }

    // Process optional files
    foreach ($optionalFiles as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];
            
            // Validate file type and size
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $uploadErrors[] = "Invalid file type for " . str_replace('_', ' ', $field) . ". Only PDF, Word, and Excel documents are allowed.";
                continue;
            }
            
            if ($file['size'] > $maxSize) {
                $uploadErrors[] = "File too large for " . str_replace('_', ' ', $field) . ". Maximum size is 5MB.";
                continue;
            }

            $filename = uniqid() . '-' . basename($file['name']);
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePaths[$field] = $targetPath;
            } else {
                $uploadErrors[] = "Failed to upload " . str_replace('_', ' ', $field);
            }
        }
    }

    if (!empty($uploadErrors)) {
        $errorMsg = implode("<br>", $uploadErrors);
    } else {
        // Set default empty string for optional documents
        $other_documents = $filePaths['other_documents'] ?? '';
        
        // Insert application into database
        $stmt = $conn->prepare("INSERT INTO applications (
            application_number, user_id, position_id, resume, 
            application_letter, personal_data_sheet, 
            transcript_of_records, proof_of_eligibility, 
            other_documents, work_experience, education, status, phone
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        
        if ($stmt === false) {
            $errorMsg = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param(
                "siisssssssss",
                $applicationNumber,
                $user_id,
                $position_id,
                $filePaths['resume'],
                $filePaths['application_letter'],
                $filePaths['personal_data_sheet'],
                $filePaths['transcript_of_records'],
                $filePaths['proof_of_eligibility'],
                $other_documents,
                $work_experience_json,
                $education_json,
                $phone
            );

            if ($stmt->execute()) {
                $lastId = $conn->insert_id;
                
                // Insert work experience into separate table
                foreach ($work_experience as $exp) {
                    $stmtExp = $conn->prepare("INSERT INTO application_work_experience (
                    application_id, position, company, start_date, end_date, salary, 
                    salary_grade, status_of_appointment, govt_service
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmtExp === false) {
                    error_log("Error preparing work experience statement: " . $conn->error);
                    continue;
                    }
                    
                    $stmtExp->bind_param(
                    "issssssss",
                    $lastId,
                    $exp['position'],
                    $exp['company'],
                    $exp['start_date'],
                    $exp['end_date'],
                    $exp['salary'],
                    $exp['salary_grade'],
                    $exp['status'],
                    $exp['govt_service']
);
                    if (!$stmtExp->execute()) {
                        error_log("Error inserting work experience: " . $stmtExp->error);
                    }
                    $stmtExp->close();
                }
                
                // Insert education into separate table
                foreach ($education as $edu) {
                    $stmtEdu = $conn->prepare("INSERT INTO application_education (
                        application_id, level, school, degree, start_date, end_date, 
                        highest_level, year_graduated, honors
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmtEdu === false) {
                        error_log("Error preparing education statement: " . $conn->error);
                        continue;
                    }
                    
                    $stmtEdu->bind_param(
                        "issssssss",
                        $lastId,
                        $edu['level'],
                        $edu['school'],
                        $edu['degree'],
                        $edu['start_date'],
                        $edu['end_date'],
                        $edu['highest_level'],
                        $edu['year_graduated'],
                        $edu['honors']
                    );
                    if (!$stmtEdu->execute()) {
                        error_log("Error inserting education: " . $stmtEdu->error);
                    }
                    $stmtEdu->close();
                }
                
                // Update user's phone number if provided
                if (!empty($phone)) {
                    $stmtPhone = $conn->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
                    if ($stmtPhone === false) {
                        error_log("Error preparing phone update statement: " . $conn->error);
                    } else {
                        $stmtPhone->bind_param("si", $phone, $user_id);
                        if (!$stmtPhone->execute()) {
                            error_log("Error updating phone: " . $stmtPhone->error);
                        }
                        $stmtPhone->close();
                    }
                }
                
                // Send confirmation email
                $emailSent = sendStatusEmail(
                    $user['email'],
                    $user['first_name'],
                    $user['last_name'],
                    $position,
                    'Applied',
                    null,
                    $applicationNumber
                );
                
                if ($emailSent) {
                    $successMsg = "Application submitted successfully! Your application number is: $applicationNumber. A confirmation email has been sent to your email address.";
                } else {
                    $successMsg = "Application submitted successfully! Your application number is: $applicationNumber. (Confirmation email could not be sent)";
                }

                $selectedPosition = "";
            } else {
                $errorMsg = "Error submitting application: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

render_page:

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
    'New Job' => 'New job offers available: %s',
    'Exam Completed' => 'Exam process completed - awaiting decision'
];

$notifications = [];

// Get all application status changes for this user
$stmt = $conn->prepare("SELECT a.status, a.interview_date, a.submitted_at, j.title AS job_title, a.application_number 
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
    $appNumber = $row['application_number'];
    
    switch ($status) {
        case 'Applied':
            $icon = 'file-import';
            break;
        case 'Under Review':
            $icon = 'search';
            break;
        case 'Interview Scheduled':
            $icon = 'calendar-alt';
            $message = sprintf($statusMessages[$status], $row['interview_date'] ? date('F j, Y', strtotime($row['interview_date'])) : 'To be determined');
            break;
        case 'Under Interviews':
            $icon = 'user-clock';
            break;
        case 'Interviewed':
            $icon = 'user-check';
            break;
        case 'Hired':
            $icon = 'trophy';
            break;
        case 'Not Selected':
            $icon = 'times-circle';
            break;
        case 'Pending':
            $icon = 'hourglass-half';
            break;
        case 'Exam Completed':
            $icon = 'file-signature';
            break;
        default:
            $message = "Application status: $status";
    }
    
    // For statuses other than Interview Scheduled, get the default message
    if (!isset($message)) {
        $message = isset($statusMessages[$status]) ? $statusMessages[$status] : "Application status: $status";
    }
    
    // Add application number to message
    $message .= " (Ref: $appNumber)";
    
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
            $user['first_name'],
            $user['last_name'],
            $row['job_title'],
            $status,
            $row['interview_date'],
            $appNumber
        );
        
        if ($emailSent) {
            $_SESSION['status_email_sent_'.$status.'_'.$userId] = true;
        }
    }
}

// Add job offer notifications from job_positions
$jobStmt = $conn->prepare("SELECT title, date_posted FROM job_positions WHERE date_posted >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Open' ORDER BY date_posted DESC");
if ($jobStmt) {
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
}

$stmt->close();

// Count unread notifications
$unreadCount = array_reduce($notifications, function($carry, $item) {
    return $carry + (isset($item['unread']) && $item['unread'] && !isset($_SESSION['notifications_read']) ? 1 : 0);
}, 0);

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);

// Include sidebar variables
$sidebarCollapsed = $_SESSION['sidebar_collapsed'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Application | Job Portal</title>
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
  <!-- Flatpickr for date inputs -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/applicants/application.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content <?= $sidebarCollapsed ? 'sidebar-collapsed' : '' ?>">
        <!-- Topbar -->
        <div class="topbar animate-slide-up">
            <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">
                <h1>Job Application</h1>
                <p>Submit your application for open positions</p>
            </div>
            <div class="topbar-actions">
                <!-- Notifications -->
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
                <!-- Profile -->
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

        <!-- Application Form -->
        <div class="application-container animate-slide-up delay-1">
            <?php if ($successMsg): ?>
                <div class="alert alert-success animate-fade-in">
                    <?= $successMsg ?>
                </div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger animate-fade-in">
                    <?= $errorMsg ?>
                </div>
            <?php endif; ?>
            
            <div class="application-header">
                <h2>Application Form</h2>
                <p>Fill out the form below to apply for a position</p>
            </div>
            
            <form action="application.php" method="POST" enctype="multipart/form-data">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user"></i>
                        <span>Basic Information</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="form-label">Position Applied For</label>
                        <select name="position" id="position" class="form-control form-select" required>
                            <option value="">Select Position</option>
                            <?php
                            $jobs = $conn->query("SELECT title FROM job_positions WHERE status = 'Open' ORDER BY title");
                            while ($row = $jobs->fetch_assoc()) {
                                $selected = ($selectedPosition === $row['title']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($row['title']) . '" ' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
                            }
                            ?>
                        </select>
                        <small class="text-muted">If you don't see your desired position, it may not be currently available</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" class="form-control" value="<?= isset($user['first_name']) ? htmlspecialchars($user['first_name']) : '' ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" id="middle_name" class="form-control" value="<?= isset($user['middle_name']) ? htmlspecialchars($user['middle_name']) : '' ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" class="form-control" value="<?= isset($user['last_name']) ? htmlspecialchars($user['last_name']) : '' ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" class="form-control" value="<?= isset($user['email']) ? htmlspecialchars($user['email']) : '' ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="<?= isset($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>" required>
                                <small class="text-muted">Format: 09123456789</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Work Experience Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-briefcase"></i>
                        <span>Work Experience</span>
                    </div>
                    
                    <div id="work-experience-container">
    <!-- Work Experience Item Template -->
    <div class="work-experience-item">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Work Experience #1</h5>
            <button type="button" class="btn btn-sm btn-outline-danger remove-btn" onclick="removeWorkExperience(this)">
                <i class="fas fa-trash-alt me-1"></i> Remove
            </button>
        </div>
        
        <div class="form-group">
            <label for="work_position[]" class="form-label">POSITION TITLE (Write in full/ Do not abbreviate)</label>
            <input type="text" name="work_position[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="work_company[]" class="form-label">DEPARTMENT / AGENCY/ OFFICE / COMPANY (Write in full/ Do not abbreviate)</label>
            <input type="text" name="work_company[]" class="form-control" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="work_start_date[]" class="form-label">From</label>
                    <input type="date" name="work_start_date[]" class="form-control datepicker" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="work_end_date[]" class="form-label">To</label>
                    <input type="date" name="work_end_date[]" class="form-control datepicker" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="work_salary[]" class="form-label">Monthly Salary</label>
            <input type="text" name="work_salary[]" class="form-control" required>
        </div>

        <!-- Newly Added Form Group -->
        <div class="form-group">
        <label for="work_salary_grade[]" class="form-label">
        SALARY / JOB / PAY GRADE (if applicable) & STEP (Format *00-0*) / INCREMENT
        </label>
        <input type="text" name="work_salary_grade[]" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="work_status[]" class="form-label">STATUS OF APPOINTMENT</label>
            <input type="text" name="work_status[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="work_govt_service[]" class="form-label">GOVERMENT SERVICE (Y/N)</label>
            <select name="work_govt_service[]" class="form-control form-select" required>
                <option value="Y">Yes</option>
                <option value="N">No</option>
            </select>
        </div>
    </div>
</div>

                    
                    <button type="button" class="btn btn-outline-primary add-more-btn" onclick="addWorkExperience()">
                        <i class="fas fa-plus-circle me-1"></i> Add Another Work Experience
                    </button>
                </div>
                
                <!-- Educational Background Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Educational Background</span>
                    </div>
                    
                    <div id="education-container">
                        <!-- Education Item Template -->
                        <div class="education-item">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Education #1</h5>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-btn" onclick="removeEducation(this)">
                                    <i class="fas fa-trash-alt me-1"></i> Remove
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <label for="education_level[]" class="form-label">Level</label>
                                <select name="education_level[]" class="form-control form-select" required>
                                    <option value="">Select Level</option>
                                    <option value="ELEMENTARY">Elementary</option>
                                    <option value="SECONDARY">Secondary</option>
                                    <option value="VOCATIONAL / TRADE COURSE">Vocational/Trade Course</option>
                                    <option value="COLLEGE">College</option>
                                    <option value="GRADUATE STUDIES">Graduate Studies</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="education_school[]" class="form-label">NAME OF SCHOOL (Write in full)</label>
                                <input type="text" name="education_school[]" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="education_degree[]" class="form-label">BASIC EDUCATION / DEGREE/ COURSE (Write in full)</label>
                                <input type="text" name="education_degree[]" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="education_start_date[]" class="form-label">PERIOD OF ATTENDANCE (From)</label>
                                        <input type="date" name="education_start_date[]" class="form-control datepicker" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="education_end_date[]" class="form-label">PERIOD OF ATTENDANCE (To)</label>
                                        <input type="date" name="education_end_date[]" class="form-control datepicker" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="education_highest_level[]" class="form-label">HIGHEST LEVEL/ UNITS EARNED (if not graduated)</label>
                                <input type="text" name="education_highest_level[]" class="form-control" placeholder="If not graduated">
                            </div>
                            
                            <div class="form-group">
                                <label for="education_year_graduated[]" class="form-label">YEAR GRADUATED</label>
                                <input type="text" name="education_year_graduated[]" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="education_honors[]" class="form-label">SCHOLARSHIP / ACADEMIC HONORS RECIEVED</label>
                                <input type="text" name="education_honors[]" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary add-more-btn" onclick="addEducation()">
                        <i class="fas fa-plus-circle me-1"></i> Add Another Education
                    </button>
                </div>
                
                <!-- Document Uploads Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-file-upload"></i>
                        <span>Required Documents</span>
                    </div>
                    
                    <!-- PDS Download Card -->
                    <div class="download-card animate-slide-up delay-2">
                        <div class="download-card-header">
                            <div class="download-card-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <h4 class="download-card-title">Personal Data Sheet (PDS)</h4>
                        </div>
                        <div class="download-card-body">
                            <p>Download the Personal Data Sheet (PDS) form, fill it out completely, and upload it below.</p>
                            <a href="PDS/CS Form No. 212 Personal Data Sheet revised (1).xlsx" class="btn btn-primary" download="PDS_Form.xlsx">
                                <i class="fas fa-download me-2"></i> Download PDS Form
                            </a>
                        </div>
                    </div>
                    
<!-- With this corrected version -->
<div class="form-group">
    <label for="resume" class="form-label">Resume</label>
    <label for="resume" class="file-input-label">
        <i class="fas fa-upload me-2"></i>
        <span id="resume_label">Choose file (PDF or Word, max 5MB)</span>
    </label>
    <input type="file" name="resume" id="resume" class="file-input" accept=".pdf,.doc,.docx" required>
</div>
                                        <div class="form-group">
                        <label for="application_letter" class="form-label">Application Letter</label>
                        <label for="application_letter" class="file-input-label">
                            <i class="fas fa-upload me-2"></i>
                            <span id="application_letter_label">Choose file (PDF or Word, max 5MB)</span>
                        </label>
                        <input type="file" name="application_letter" id="application_letter" class="file-input" accept=".pdf,.doc,.docx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="personal_data_sheet" class="form-label">Personal Data Sheet (PDS) - Filled Out</label>
                        <label for="personal_data_sheet" class="file-input-label">
                            <i class="fas fa-upload me-2"></i>
                            <span id="personal_data_sheet_label">Choose file (Excel, PDF or Word, max 5MB)</span>
                        </label>
                        <input type="file" name="personal_data_sheet" id="personal_data_sheet" class="file-input" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transcript_of_records" class="form-label">Transcript of Records</label>
                        <label for="transcript_of_records" class="file-input-label">
                            <i class="fas fa-upload me-2"></i>
                            <span id="transcript_of_records_label">Choose file (PDF or Word, max 5MB)</span>
                        </label>
                        <input type="file" name="transcript_of_records" id="transcript_of_records" class="file-input" accept=".pdf,.doc,.docx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="proof_of_eligibility" class="form-label">Proof of Eligibility</label>
                        <label for="proof_of_eligibility" class="file-input-label">
                            <i class="fas fa-upload me-2"></i>
                            <span id="proof_of_eligibility_label">Choose file (PDF or Word, max 5MB)</span>
                        </label>
                        <input type="file" name="proof_of_eligibility" id="proof_of_eligibility" class="file-input" accept=".pdf,.doc,.docx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="other_documents" class="form-label">Other Supporting Documents</label>
                        <label for="other_documents" class="file-input-label">
                            <i class="fas fa-upload me-2"></i>
                            <span id="other_documents_label">Choose file (Optional, PDF or Word, max 5MB)</span>
                        </label>
                        <input type="file" name="other_documents" id="other_documents" class="file-input" accept=".pdf,.doc,.docx">
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="declaration" required>
                        <label class="form-check-label" for="declaration">I hereby declare that all the information provided in this application is true and correct to the best of my knowledge. I understand that any false statement may result in the rejection of my application or termination of employment if discovered later.</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block mt-4">Submit Application</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr for date inputs -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- AOS (Animate On Scroll) -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/applicants/application.js"></script>
</body>
</html>