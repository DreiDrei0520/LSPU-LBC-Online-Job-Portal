// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
  
  // Toggle sidebar collapse
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      
      // Save state to session
      const isCollapsed = sidebar.classList.contains('collapsed');
      fetch('update_sidebar_state.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'collapsed=' + (isCollapsed ? '1' : '0')
      });
    });
  }
  
  // Mobile sidebar toggle
  if (mobileSidebarToggle) {
    mobileSidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    if (window.innerWidth < 992) {
      if (!sidebar.contains(e.target) && !mobileSidebarToggle.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    }
  });

  // Highlight active link based on current page
  const currentPage = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('.nav-link');
  
  navLinks.forEach(link => {
    link.classList.remove('active');
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });
});