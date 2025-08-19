<?php
// sidebar.php
$sidebarCollapsed = $_SESSION['sidebar_collapsed'] ?? false;
?>
<!-- Sidebar -->
<div class="sidebar <?= $sidebarCollapsed ? 'collapsed' : '' ?>" id="sidebar">
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-chevron-left"></i>
  </button>
  <div class="sidebar-brand">
    <img src="images/lspulogo.png" alt="Job Portal" class="img-fluid">
    <span>LSPU-LBC JOB PORTAL</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item">
      <a href="admin_dashboard.php" class="nav-link active">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <span class="tooltip-text">Dashboard</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="joblistings.php" class="nav-link">
        <i class="fas fa-briefcase"></i>
        <span>Job Listings</span>
        <span class="tooltip-text">Job Listings</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="applicantschedule.php" class="nav-link">
        <i class="fas fa-calendar-alt"></i>
        <span>Applicants</span>
        <span class="tooltip-text">Applicant Scheduled</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="applicantstatus.php" class="nav-link">
        <i class="fas fa-tasks"></i>
        <span>Applicant Status</span>
        <span class="tooltip-text">Applicant Status</span>
      </a>
    </div>
     <div class="nav-item">
      <a href="matrix.php" class="nav-link">
        <i class="fas fa-table"></i>
        <span>Matrix</span>
        <span class="tooltip-text">Matrix</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="List.php" class="nav-link">
        <i class="fas fa-users"></i>
        <span>Applicant List</span>
        <span class="tooltip-text">Applicant List</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="admin_settings.php" class="nav-link">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
        <span class="tooltip-text">Settings</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
        <span class="tooltip-text">Logout</span>
      </a>
    </div>
  </nav>
  
  <!-- Collapsed state user profile -->
  <div class="sidebar-user-collapsed">
    <?php if ($hasProfilePic): ?>
      <img src="<?= htmlspecialchars($picsPath) ?>" class="profile-img-sm" alt="Profile">
    <?php else: ?>
      <i class="fas fa-user-circle"></i>
    <?php endif; ?>
  </div>
</div>

 <link rel="stylesheet" href="assets/css/admin/admin_sidebar.css">

<script src="assets/js/admin/admin_sidebar.js"></script>