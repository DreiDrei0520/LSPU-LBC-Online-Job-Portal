 document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        once: true
      });

      // Show mobile toggle button only on mobile
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

      // Smooth scroll for notification dropdown
      const notificationDropdown = document.getElementById('notificationDropdown');
      if (notificationDropdown) {
        notificationDropdown.addEventListener('shown.bs.dropdown', function() {
          const notificationMenu = document.querySelector('.notification-menu');
          if (notificationMenu) {
            notificationMenu.scrollTo({
              top: 0,
              behavior: 'smooth'
            });
          }
        });
      }

      // Add animation class to elements when they come into view
      const animateElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('aos-animate');
          }
        });
      }, {
        threshold: 0.1
      });

      animateElements.forEach(element => {
        observer.observe(element);
      });
    });
    