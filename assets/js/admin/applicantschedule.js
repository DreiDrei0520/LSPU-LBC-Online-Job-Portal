    document.addEventListener('DOMContentLoaded', function() {
      // Edit Modal Handler
      const editModal = document.getElementById('editModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const applicationId = button.getAttribute('data-application-id');
          const currentStatus = button.getAttribute('data-current-status');
          const interviewDate = button.getAttribute('data-interview-date');
          const examDate = button.getAttribute('data-exam-date');
          
          const modal = this;
          modal.querySelector('#editApplicationId').value = applicationId;
          modal.querySelector('#editStatus').value = currentStatus;
          
          // Set date fields if they exist
          if (interviewDate) {
            modal.querySelector('#editInterviewDate').value = interviewDate;
          }
          if (examDate) {
            modal.querySelector('#editExamDate').value = examDate;
          }
          
          // Show/hide date fields based on current status
          toggleEditDateFields(modal.querySelector('#editStatus'));
        });
      }

      // Schedule Modal Handler
      const scheduleModal = document.getElementById('scheduleModal');
      if (scheduleModal) {
        scheduleModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const applicationId = button.getAttribute('data-application-id');
          const currentStatus = button.getAttribute('data-current-status');
          
          const modal = this;
          modal.querySelector('#scheduleApplicationId').value = applicationId;
          
          // Determine next status based on current status
          let nextStatus = '';
          if (currentStatus === 'Under Review') {
            nextStatus = 'Interview Scheduled';
          } else if (currentStatus === 'Under Interviews' || currentStatus === 'Interviewed') {
            nextStatus = 'Exam Scheduled';
          } else if (currentStatus === 'Exam Completed') {
            nextStatus = 'For Requirements';
          }
          
          modal.querySelector('#scheduleStatus').value = nextStatus;
        });
      }

      // Done Modal Handler
      const doneModal = document.getElementById('doneModal');
      if (doneModal) {
        doneModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const applicationId = button.getAttribute('data-application-id');
          const currentStatus = button.getAttribute('data-current-status');
          
          const modal = this;
          modal.querySelector('#doneApplicationId').value = applicationId;
          
          // Determine next status based on current status
          let nextStatus = '';
          if (currentStatus === 'Interview Scheduled') {
            nextStatus = 'Under Interviews';
          } else if (currentStatus === 'Exam Scheduled') {
            nextStatus = 'Exam Completed';
          }
          
          modal.querySelector('#doneStatus').value = nextStatus;
          modal.querySelector('#nextStatus').value = nextStatus;
        });
      }

      // Function to toggle date fields in edit modal
      function toggleEditDateFields(select) {
          const interviewDateContainer = document.getElementById('editInterviewDateContainer');
          const examDateContainer = document.getElementById('editExamDateContainer');
          
          // Hide both initially
          interviewDateContainer.style.display = 'none';
          examDateContainer.style.display = 'none';
          
          // Show appropriate field based on selection
          if (select.value === 'Interview Scheduled') {
              interviewDateContainer.style.display = 'block';
          } else if (select.value === 'Exam Scheduled') {
              examDateContainer.style.display = 'block';
          }
      }

      // Function to toggle schedule fields in schedule modal
      function toggleScheduleFields(select) {
          const scheduleDateContainer = document.getElementById('scheduleDateContainer');
          
          if (select.value) {
              scheduleDateContainer.style.display = 'block';
          } else {
              scheduleDateContainer.style.display = 'none';
          }
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

      // Handle sidebar toggle for mobile
      const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (mobileSidebarToggle && sidebar) {
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

      // Highlight active nav link
      const currentPage = window.location.pathname.split('/').pop();
      const navLinks = document.querySelectorAll('.nav-link');
      
      navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href') === currentPage) {
              link.classList.add('active');
          }
      });
    });