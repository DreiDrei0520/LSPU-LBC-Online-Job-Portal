 document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        once: true
      });

      // Password toggle
      const togglePassword = document.getElementById('togglePassword');
      const password = document.getElementById('password');
      
      if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
          const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
          password.setAttribute('type', type);
          this.classList.toggle('fa-eye-slash');
          this.classList.toggle('fa-eye');
        });
      }

      // Profile picture preview
      const profilePicInput = document.getElementById('profile_pic');
      const profilePicPreview = document.getElementById('profilePicPreview');
      
      if (profilePicInput && profilePicPreview) {
        profilePicInput.addEventListener('change', function() {
          const file = this.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              profilePicPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
          }
        });
      }

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
    });