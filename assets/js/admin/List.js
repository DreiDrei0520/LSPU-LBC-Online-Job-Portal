    document.addEventListener('DOMContentLoaded', function() {
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

      // Tab functionality for modal
      const tabButtons = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');

      tabButtons.forEach(button => {
        button.addEventListener('click', () => {
          // Remove active class from all buttons and contents
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));

          // Add active class to clicked button and corresponding content
          button.classList.add('active');
          const tabId = button.getAttribute('data-tab');
          document.getElementById(`${tabId}-tab`).classList.add('active');
        });
      });

      // Auto-submit search form when typing (with delay)
      const searchInput = document.querySelector('input[name="search"]');
      if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimer);
          searchTimer = setTimeout(() => {
            this.form.submit();
          }, 500);
        });
      }
    });

    // Modal functions
    function closeModal() {
      document.getElementById('applicationModal').style.display = 'none';
      document.body.style.overflow = 'auto';

      // Remove the view_id parameter from URL
      const url = new URL(window.location.href);
      url.searchParams.delete('view_id');
      window.history.replaceState({}, '', url);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('applicationModal');
      if (modal && event.target === modal) {
        closeModal();
      }
    };

    // Close modal with ESC key
    document.onkeydown = function(evt) {
      evt = evt || window.event;
      if (evt.key === "Escape") {
        closeModal();
      }
    };