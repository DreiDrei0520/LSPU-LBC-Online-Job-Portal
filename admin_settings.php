<?php
session_start();
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

// Initialize variables
$errors = [];
$success_message = '';
$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, email, phone, birthdate, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $first_name = $conn->real_escape_string(trim($_POST['first_name']));
    $middle_name = $conn->real_escape_string(trim($_POST['middle_name']));
    $last_name = $conn->real_escape_string(trim($_POST['last_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $birthdate = $conn->real_escape_string(trim($_POST['birthdate']));
    
    // Validate required fields
    if (empty($first_name)) {
        $errors['first_name'] = "First name is required";
    }
    if (empty($last_name)) {
        $errors['last_name'] = "Last name is required";
    }
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    // Handle password change if any password field is filled
    $password_changed = false;
    if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        if (empty($_POST['current_password'])) {
            $errors['current_password'] = "Current password is required to change password";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $db_password = $result->fetch_assoc()['password'];
            $stmt->close();
            
            if (!password_verify($_POST['current_password'], $db_password)) {
                $errors['current_password'] = "Current password is incorrect";
            } elseif (empty($_POST['new_password'])) {
                $errors['new_password'] = "New password is required";
            } elseif (strlen($_POST['new_password']) < 8) {
                $errors['new_password'] = "Password must be at least 8 characters";
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $errors['confirm_password'] = "Passwords do not match";
            } else {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $password_changed = true;
            }
        }
    }
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_pic']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $target_dir = "uploads/profile_pics/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                // Delete old profile pic if it exists and isn't the default
                if ($profile_pic && $profile_pic !== 'default.jpg' && file_exists($target_dir . $profile_pic)) {
                    unlink($target_dir . $profile_pic);
                }
                $profile_pic = $new_filename;
            } else {
                $errors['profile_pic'] = "Failed to upload profile picture";
            }
        } else {
            $errors['profile_pic'] = "Only JPG, PNG, and GIF files are allowed";
        }
    }
    
    // Update user data if no errors
    if (empty($errors)) {
        if ($password_changed) {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ?, birthdate = ?, profile_pic = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssssi", $first_name, $middle_name, $last_name, $email, $phone, $birthdate, $profile_pic, $new_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ?, birthdate = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $first_name, $middle_name, $last_name, $email, $phone, $birthdate, $profile_pic, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Refresh user data
            $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, email, phone, birthdate, profile_pic FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $errors['database'] = "Error updating profile: " . $conn->error;
        }
    }
}

// Notification system (same as before)
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

// Get all application status changes for this user
$stmt = $conn->prepare("SELECT status, interview_date, submitted_at, position_id FROM applications WHERE user_id = ? ORDER BY submitted_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];

while ($row = $result->fetch_assoc()) {
    // Get position title
    $pos_stmt = $conn->prepare("SELECT title FROM job_positions WHERE position_id = ?");
    $pos_stmt->bind_param("i", $row['position_id']);
    $pos_stmt->execute();
    $pos_result = $pos_stmt->get_result();
    $position = $pos_result->fetch_assoc()['title'] ?? 'Unknown Position';
    $pos_stmt->close();
    
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
        default:
            $message = "Application status: $status";
    }
    
    if (!isset($message)) {
        $message = $statusMessages[$status] ?? "Application status: $status";
    }
    
    $notifications[] = [
        'icon' => $icon,
        'message' => $message . " - " . $position,
        'time' => $time,
        'unread' => !isset($_SESSION['notifications_read'])
    ];
}

$stmt->close();

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
    header('Location: settings.php');
    exit;
}

// Count unread notifications
$unreadCount = array_reduce($notifications, function($carry, $item) {
    return $carry + (isset($item['unread']) && $item['unread'] ? 1 : 0);
}, 0);

// Profile picture path
$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicPath = !empty($user['profile_pic']) ? 'uploads/profile_pics/' . $user['profile_pic'] : $defaultProfilePic;
$hasProfilePic = file_exists($profilePicPath);

// Include sidebar variables
$sidebarCollapsed = $_SESSION['sidebar_collapsed'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Settings | Job Portal</title>
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
  <link rel="stylesheet" href="assets/css/admin/admin_settings.css">
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
        <h1>Profile Settings</h1>
        <p>Manage your account information</p>
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
                <div class="notification-item <?= isset($note['unread']) && $note['unread'] ? 'unread' : '' ?>">
                  <div class="notification-icon">
                    <i class="fas fa-<?= $note['icon'] ?>"></i>
                  </div>
                  <div class="notification-content">
                    <p><?= htmlspecialchars($note['message']) ?></p>
                    <span class="notification-time">
                      <i class="far fa-clock me-1"></i> <?= $note['time'] ?>
                    </span>
                  </div>
                  <?php if (isset($note['unread']) && $note['unread']): ?>
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

    <!-- Settings Content -->
    <div class="settings-container animate-slide-up delay-1">
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success animate-fade-in">
          <?= $_SESSION['success_message'] ?>
          <?php unset($_SESSION['success_message']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($errors['database'])): ?>
        <div class="alert alert-danger animate-fade-in">
          <?= $errors['database'] ?>
        </div>
      <?php endif; ?>
      
      <div class="profile-section">
        <div class="profile-pic-container">
          <img src="<?= $hasProfilePic ? htmlspecialchars($profilePicPath) : $defaultProfilePic ?>" class="profile-pic" alt="Profile Picture" id="profilePicPreview">
          <label for="profile_pic" class="profile-pic-upload">
            <i class="fas fa-camera"></i>
          </label>
        </div>
        <div class="profile-info">
          <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
          <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="file" id="profile_pic" name="profile_pic" class="file-input" accept="image/*">
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="first_name" class="form-label">First Name</label>
              <input type="text" id="first_name" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                     value="<?= htmlspecialchars($user['first_name']) ?>" required>
              <?php if (isset($errors['first_name'])): ?>
                <div class="invalid-feedback"><?= $errors['first_name'] ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="middle_name" class="form-label">Middle Name</label>
              <input type="text" id="middle_name" name="middle_name" class="form-control" 
                     value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="last_name" class="form-label">Last Name</label>
              <input type="text" id="last_name" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                     value="<?= htmlspecialchars($user['last_name']) ?>" required>
              <?php if (isset($errors['last_name'])): ?>
                <div class="invalid-feedback"><?= $errors['last_name'] ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                     value="<?= htmlspecialchars($user['email']) ?>" required>
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= $errors['email'] ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" id="phone" name="phone" class="form-control" 
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="birthdate" class="form-label">Birthdate</label>
              <input type="date" id="birthdate" name="birthdate" class="form-control" 
                     value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
            </div>
          </div>
        </div>
        
        <hr class="my-4">
        
        <h5 class="mb-4">Change Password</h5>
        
        <div class="form-group password-toggle">
          <label for="current_password" class="form-label">Current Password</label>
          <input type="password" id="current_password" name="current_password" 
                 class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>">
          <?php if (isset($errors['current_password'])): ?>
            <div class="invalid-feedback"><?= $errors['current_password'] ?></div>
          <?php endif; ?>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group password-toggle">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" id="new_password" name="new_password" 
                     class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['new_password'])): ?>
                <div class="invalid-feedback"><?= $errors['new_password'] ?></div>
              <?php endif; ?>
              <small class="text-muted">At least 8 characters</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group password-toggle">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" 
                     class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-4">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS (Animate On Scroll) -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/admin/admin_settings.js"></script>
</body>
</html>