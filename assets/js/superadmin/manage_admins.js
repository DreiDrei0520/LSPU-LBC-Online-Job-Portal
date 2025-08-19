    document.addEventListener('DOMContentLoaded', function() {
      // Mobile sidebar toggle
      const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
      if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
          document.querySelector('.sidebar').classList.toggle('active');
        });
      }
      
      // Detect mobile view
      function checkMobileView() {
        if (window.innerWidth <= 992) {
          if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'block';
          document.querySelector('.sidebar').classList.remove('active');
        } else {
          if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'none';
          document.querySelector('.sidebar').classList.add('active');
        }
      }
      
      // Initial check
      checkMobileView();
      
      // Add resize listener
      window.addEventListener('resize', checkMobileView);
      
      // Search functionality
      const adminSearch = document.getElementById('adminSearch');
      const searchButton = document.getElementById('searchButton');
      const clearSearch = document.getElementById('clearSearch');
      const adminsTable = document.getElementById('adminsTable');
      
      function filterAdmins() {
        const searchTerm = adminSearch.value.toLowerCase().trim();
        const rows = adminsTable.querySelectorAll('tbody tr');
        
        if (searchTerm === '') {
          clearSearch.style.display = 'none';
        } else {
          clearSearch.style.display = 'block';
        }
        
        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          let found = false;
          
          // Search in name (3rd column) and email (4th column)
          const name = cells[2].textContent.toLowerCase();
          const email = cells[3].textContent.toLowerCase();
          
          if (name.includes(searchTerm)){
            found = true;
          } else if (email.includes(searchTerm)) {
            found = true;
          }
          
          row.style.display = found ? '' : 'none';
        });
      }
      
      // Search button click
      if (searchButton) {
        searchButton.addEventListener('click', filterAdmins);
      }
      
      // Enter key in search field
      if (adminSearch) {
        adminSearch.addEventListener('keyup', function(e) {
          if (e.key === 'Enter') {
            filterAdmins();
          }
        });
        
        // Real-time search as user types (optional)
        adminSearch.addEventListener('input', function() {
          filterAdmins();
        });
      }
      
      // Clear search
      if (clearSearch) {
        clearSearch.addEventListener('click', function() {
          adminSearch.value = '';
          filterAdmins();
        });
      }
      
      // Edit modal population
      const editButtons = document.querySelectorAll('.edit-btn');
      const editAdminModal = document.getElementById('editAdminModal');
      
      if (editButtons && editAdminModal) {
        editButtons.forEach(button => {
          button.addEventListener('click', function() {
            const adminId = this.getAttribute('data-id');
            const firstName = this.getAttribute('data-first_name');
            const middleName = this.getAttribute('data-middle_name');
            const lastName = this.getAttribute('data-last_name');
            const adminEmail = this.getAttribute('data-email');
            const adminRole = this.getAttribute('data-role');
            
            document.getElementById('edit_admin_id').value = adminId;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_middle_name').value = middleName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_email').value = adminEmail;
            document.getElementById('edit_role').value = adminRole;
          });
        });
      }
      
      // Form validation for add admin
      const addAdminForm = document.getElementById('addAdminModal')?.querySelector('form');
      if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(e) {
          const password = document.getElementById('password').value;
          const confirmPassword = document.getElementById('confirm_password').value;
          
          if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
          } else {
            const addAdminBtnText = document.getElementById('addAdminBtnText');
            const addAdminSpinner = document.getElementById('addAdminSpinner');
            
            if (addAdminBtnText && addAdminSpinner) {
              addAdminBtnText.textContent = 'Processing...';
              addAdminSpinner.classList.remove('d-none');
            }
          }
        });
      }
      
      // Form validation for edit admin
      const editAdminForm = document.getElementById('editAdminModal')?.querySelector('form');
      if (editAdminForm) {
        editAdminForm.addEventListener('submit', function(e) {
          const updateAdminBtnText = document.getElementById('updateAdminBtnText');
          const updateAdminSpinner = document.getElementById('updateAdminSpinner');
          
          if (updateAdminBtnText && updateAdminSpinner) {
            updateAdminBtnText.textContent = 'Processing...';
            updateAdminSpinner.classList.remove('d-none');
          }
        });
      }
     
      // Auto-dismiss alerts after 5 seconds
      const alerts = document.querySelectorAll('.alert-fixed');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.classList.add('fade');
          setTimeout(() => alert.remove(), 150);
        }, 5000);
      });
    });