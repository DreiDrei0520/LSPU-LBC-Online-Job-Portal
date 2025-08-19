    $(document).ready(function() {
      // Mobile sidebar toggle
      $('#mobileSidebarToggle').click(function() {
        $('.sidebar').toggleClass('active');
      });
      
      // Detect mobile view
      function checkMobileView() {
        if ($(window).width() <= 992) {
          $('#mobileSidebarToggle').show();
          $('.sidebar').removeClass('active');
        } else {
          $('#mobileSidebarToggle').hide();
          $('.sidebar').addClass('active');
        }
      }
      
      // Initial check
      checkMobileView();
      
      // Add resize listener
      $(window).resize(checkMobileView);
      
      // Edit modal population
      $('.edit-btn').click(function() {
        const applicantId = $(this).data('id');
        const firstName = $(this).data('first-name');
        const middleName = $(this).data('middle-name');
        const lastName = $(this).data('last-name');
        const email = $(this).data('email');
        const birthdate = $(this).data('birthdate');
        const phone = $(this).data('phone');
        const isActive = $(this).data('is-active');
        
        $('#edit_applicant_id').val(applicantId);
        $('#edit_first_name').val(firstName);
        $('#edit_middle_name').val(middleName);
        $('#edit_last_name').val(lastName);
        $('#edit_email').val(email);
        $('#edit_birthdate').val(birthdate);
        $('#edit_phone').val(phone);
        $('#edit_is_active').prop('checked', isActive == 1);
      });
      
      // Form validation for add applicant
      $('#addApplicantForm').submit(function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
          e.preventDefault();
          alert('Passwords do not match!');
          $('#confirm_password').focus();
        } else if (password.length < 8) {
          e.preventDefault();
          alert('Password must be at least 8 characters long!');
          $('#password').focus();
        } else {
          $('#addApplicantBtnText').text('Processing...');
          $('#addApplicantSpinner').removeClass('d-none');
          $('#addApplicantBtn').prop('disabled', true);
        }
      });
      
      // Form validation for edit applicant
      $('#editApplicantForm').submit(function(e) {
        const password = $('#edit_password').val();
        
        if (password.length > 0 && password.length < 8) {
          e.preventDefault();
          alert('Password must be at least 8 characters long if changing!');
          $('#edit_password').focus();
          return;
        }
        
        $('#updateApplicantBtnText').text('Processing...');
        $('#updateApplicantSpinner').removeClass('d-none');
        $('#updateApplicantBtn').prop('disabled', true);
      });
      
      // Auto-dismiss alerts after 5 seconds
      $('.alert-fixed').delay(5000).fadeOut(300, function() {
        $(this).alert('close');
      });
      
      // Initialize date picker max date (today)
      $('.datepicker').attr('max', new Date().toISOString().split('T')[0]);
    });