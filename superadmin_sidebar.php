<?php
// superadmin_sidebar.php
$sidebarCollapsed = $_SESSION['sidebar_collapsed'] ?? false;
?>
<!-- Superadmin Sidebar -->
<div class="sidebar <?= $sidebarCollapsed ? 'collapsed' : '' ?>" id="sidebar">
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-chevron-left"></i>
  </button>
  <div class="sidebar-brand">
    <img src="images/lspulogo.png" alt="Job Portal" class="img-fluid">
    <span>LSPULBC JOB PORTAL</span>
    <span class="admin-badge"></span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item">
      <a href="superadmin_dashboard.php" class="nav-link active">
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
        <span class="tooltip-text">Superadmin Dashboard</span>
      </a>
    </div>
    
    <div class="nav-item">
      <a href="manage_admins.php" class="nav-link">
        <i class="fas fa-user-shield"></i>
        <span>Admin Accounts</span>
        <span class="tooltip-text">Manage Admin Accounts</span>
      </a>
    </div>
    
    <div class="nav-item">
      <a href="manage_applicants.php" class="nav-link">
        <i class="fas fa-user-graduate"></i>
        <span>Applicants Accounts</span>
        <span class="tooltip-text">Manage Applicant Accounts</span>
      </a>
    </div>
    
    <div class="nav-item">
      <a href="database_cleanup.php" class="nav-link">
        <i class="fas fa-broom"></i>
        <span>Cleanup</span>
        <span class="tooltip-text">Database Cleanup Tools</span>
      </a>
    </div>

    
    <div class="nav-item">
      <a href="superadmin_settings.php" class="nav-link">
        <i class="fas fa-sliders-h"></i>
        <span>Settings</span>
        <span class="tooltip-text">System Configuration</span>
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
    <i class="fas fa-user-crown"></i>
  </div>
</div>

<link rel="stylesheet" href="assets/css/superadmin/superadmin_sidebar.css">
<script src="assets/js/superadmin/superadmin_sidebar.js"></script>