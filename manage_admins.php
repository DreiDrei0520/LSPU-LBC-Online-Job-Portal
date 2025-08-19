<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// Database connection using your credentials
$host = 'localhost';
$db = 'appjobsystem';
$userDb = 'root';
$pass = '';
$conn = new mysqli($host, $userDb, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details for the sidebar/profile
$userId = $_SESSION['user_id'];
$user = null;
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submissions
$message = '';
$messageType = '';

// Add new admin
if (isset($_POST['add_admin'])) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $message = "Email already exists in the system!";
        $messageType = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $first_name, $middle_name, $last_name, $email, $password, $role);
        
        if ($stmt->execute()) {
            $message = "Admin account created successfully!";
            $messageType = "success";
            
            // Log the action
            $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Admin Added', 'Superadmin created new $role account for $email')");
        } else {
            $message = "Error creating admin account: " . $stmt->error;
            $messageType = "danger";
        }
        $stmt->close();
    }
}

// Update admin
if (isset($_POST['update_admin'])) {
    $adminId = intval($_POST['admin_id']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Check if email already exists for another user
    $check = $conn->query("SELECT * FROM users WHERE email = '$email' AND user_id != $adminId");
    if ($check->num_rows > 0) {
        $message = "Email already exists for another user!";
        $messageType = "danger";
    } else {
        // Check if password was provided
        $passwordUpdate = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, role = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $role, $password, $adminId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $first_name, $middle_name, $last_name, $email, $role, $adminId);
        }
        
        if ($stmt->execute()) {
            $message = "Admin account updated successfully!";
            $messageType = "success";
            
            // Log the action
            $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Admin Updated', 'Superadmin updated account ID $adminId')");
        } else {
            $message = "Error updating admin account: " . $stmt->error;
            $messageType = "danger";
        }
        $stmt->close();
    }
}

// Delete admin
if (isset($_GET['delete_admin'])) {
    $adminId = intval($_GET['delete_admin']);
    
    // Prevent deleting superadmin accounts (except maybe the current user)
    $check = $conn->query("SELECT role FROM users WHERE user_id = $adminId");
    if ($check->num_rows > 0) {
        $admin = $check->fetch_assoc();
        if ($admin['role'] === 'superadmin' && $adminId != $userId) {
            $message = "Cannot delete other superadmin accounts!";
            $messageType = "danger";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $adminId);
            
            if ($stmt->execute()) {
                $message = "Admin account deleted successfully!";
                $messageType = "success";
                
                // Log the action
                $conn->query("INSERT INTO system_logs (user_id, action, details) VALUES ($userId, 'Admin Deleted', 'Superadmin deleted account ID $adminId')");
            } else {
                $message = "Error deleting admin account: " . $stmt->error;
                $messageType = "danger";
            }
            $stmt->close();
        }
    }
}

// Get all admins (including superadmins)
$admins = [];
$result = $conn->query("SELECT user_id, first_name, middle_name, last_name, email, profile_pic, role, created_at FROM users WHERE role IN ('admin', 'superadmin') ORDER BY role DESC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Get notification count
$unreadCount = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
if ($result) {
    $unreadCount = $result->fetch_assoc()['count'];
}

// Mark notifications as read if requested
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    $_SESSION['notifications_read'] = true;
    header('Location: manage_admins.php');
    exit;
}

$defaultProfilePic = 'uploads/profile_pics/default-profile.png';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default-profile.png';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Admins | LSPU Job Portal</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/superadmin/manage_admins.css">

</head>
<body>
  <?php include 'superadmin_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-primary sidebar-toggler d-lg-none me-2" id="mobileSidebarToggle" style="display: none;">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1>Manage Administrators <span class="superadmin-badge">SUPERADMIN</span></h1>
        <p>Create, update, and delete admin accounts</p>
      </div>
      <div class="topbar-actions">
        <div class="dropdown">
          <button class="notification-btn dropdown-toggle position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="notification-badge"><?= htmlspecialchars($unreadCount) ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown" style="width: 350px;">
            <div class="notification-header d-flex justify-content-between align-items-center p-3 border-bottom">
              <h5 class="mb-0">System Notifications</h5>
              <form method="POST" action="">
                <button type="submit" name="mark_all_read" class="btn btn-sm btn-link text-primary p-0">Mark All as Read</button>
              </form>
            </div>
            <?php if ($unreadCount > 0): ?>
              <div style="max-height: 400px; overflow-y: auto;">
                <?php 
                $notifications = $conn->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
                while ($note = $notifications->fetch_assoc()): ?>
                  <div class="notification-item <?= $note['is_read'] == 0 ? 'unread' : '' ?>">
                    <div class="notification-icon">
                      <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="notification-content">
                      <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                      <span class="notification-time">
                        <i class="far fa-clock me-1"></i> <?= date('M j, Y', strtotime($note['created_at'])) ?>
                      </span>
                    </div>
                    <?php if ($note['is_read'] == 0): ?>
                      <span class="unread-badge"></span>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No new notifications</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="dropdown">
          <button class="profile-btn dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($hasProfilePic): ?>
              <img src="<?= htmlspecialchars($picsPath) ?>" class="profile-img rounded-circle" alt="Profile" style="width: 36px; height: 36px; object-fit: cover;">
            <?php else: ?>
              <i class="fas fa-user-circle fs-5"></i>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end profile-menu" aria-labelledby="profileDropdown">
            <a href="superadmin_settings.php" class="dropdown-item">
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
    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= $messageType ?> alert-dismissible fade show alert-fixed" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Main Content Area -->
    <div class="container-fluid mt-4">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Administrator Accounts</h5>
          <div class="d-flex">
            <div class="search-box-container">
              <div class="input-group search-box">
                <input type="text" id="adminSearch" class="form-control" placeholder="Search admins...">
                <button class="btn btn-outline-secondary" type="button" id="searchButton">
                  <i class="fas fa-search"></i>
                </button>
              </div>
              <span class="search-box-clear" id="clearSearch" style="display: none;">
                <i class="fas fa-times"></i>
              </span>
            </div>
            <button class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addAdminModal">
              <i class="fas fa-plus-circle me-1"></i> Add Admin
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover" id="adminsTable">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($admins as $admin): 
                  $fullName = htmlspecialchars($admin['first_name'] . ' ' . ($admin['middle_name'] ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
                ?>
                  <tr>
                    <td><?= htmlspecialchars($admin['user_id']) ?></td>
                    <td>
                      <img src="uploads/profile_pics/<?= !empty($admin['profile_pic']) ? htmlspecialchars($admin['profile_pic']) : 'default-profile.png' ?>" 
                           class="user-avatar" alt="Profile">
                    </td>
                    <td class="admin-name" title="<?= $fullName ?>"><?= $fullName ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                      <span class="badge <?= $admin['role'] === 'superadmin' ? 'superadmin-badge' : 'admin-role-badge' ?>">
                        <?= htmlspecialchars(ucfirst($admin['role'])) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge status-active">Active</span>
                    </td>
                    <td><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                    <td class="action-btns">
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editAdminModal"
                                data-id="<?= $admin['user_id'] ?>"
                                data-first_name="<?= htmlspecialchars($admin['first_name']) ?>"
                                data-middle_name="<?= htmlspecialchars($admin['middle_name']) ?>"
                                data-last_name="<?= htmlspecialchars($admin['last_name']) ?>"
                                data-email="<?= htmlspecialchars($admin['email']) ?>"
                                data-role="<?= htmlspecialchars($admin['role']) ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($admin['role'] !== 'superadmin' || $admin['user_id'] == $userId): ?>
                          <a href="manage_admins.php?delete_admin=<?= $admin['user_id'] ?>" 
                             class="btn btn-sm btn-outline-danger"
                             onclick="return confirm('Are you sure you want to delete this admin account?')">
                            <i class="fas fa-trash-alt"></i>
                          </a>
                        <?php else: ?>
                          <button class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Admin Modal -->
  <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addAdminModalLabel">
            <i class="fas fa-user-plus me-2"></i>Add New Administrator
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="manage_admins.php">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name">
              </div>
            </div>
            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <select class="form-select" id="role" name="role" required>
                <option value="admin">Admin</option>
                <option value="superadmin">Superadmin</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required minlength="8">
              <div class="form-text">Minimum 8 characters</div>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_admin" class="btn btn-primary">
              <span id="addAdminBtnText">Add Admin</span>
              <div id="addAdminSpinner" class="loading-spinner d-none"></div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Admin Modal -->
  <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editAdminModalLabel">
            <i class="fas fa-user-edit me-2"></i>Edit Administrator
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="manage_admins.php">
          <input type="hidden" id="edit_admin_id" name="admin_id">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="edit_first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="edit_middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
              </div>
            </div>
            <div class="mb-3">
              <label for="edit_last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="edit_role" class="form-label">Role</label>
              <select class="form-select" id="edit_role" name="role" required>
                <option value="admin">Admin</option>
                <option value="superadmin">Superadmin</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_password" class="form-label">New Password (Optional)</label>
              <input type="password" class="form-control" id="edit_password" name="password">
              <div class="form-text">Leave blank to keep current password</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_admin" class="btn btn-primary">
              <span id="updateAdminBtnText">Update Admin</span>
              <div id="updateAdminSpinner" class="loading-spinner d-none"></div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/js/superadmin/manage_admins.js"></script>
</body>
</html>