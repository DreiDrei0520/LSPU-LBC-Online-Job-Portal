    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        once: true
      });
      
      // Job details modal functionality
      const viewDetailBtns = document.querySelectorAll('.view-details-btn');
      const modal = document.getElementById('jobDetailsModal');
      const closeModalBtn = document.querySelector('.close-modal');
      const closeModalBtn2 = document.querySelector('.close-modal-btn');
      
      viewDetailBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const jobId = this.getAttribute('data-job-id');
          const job = allJobs.find(j => j.position_id == jobId);
          
          if (job) {
            document.getElementById('modalJobTitle').textContent = job.title;
            document.getElementById('modalJobDepartment').textContent = job.department;
            document.getElementById('modalJobType').textContent = job.type;
            document.getElementById('modalJobCategory').textContent = job.category;
            document.getElementById('modalJobLocation').textContent = job.location;
            document.getElementById('modalJobAssignment').textContent = job.place_of_assignment;
            document.getElementById('modalJobSalary').textContent = job.salary_range || 'Salary not specified';
            document.getElementById('modalJobDescription').textContent = job.description || 'No description provided';
            document.getElementById('modalJobDatePosted').textContent = new Date(job.date_posted).toLocaleDateString('en-US', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric' 
            });
            
            // Format bullet point lists
            const formatList = (text) => {
              if (!text) return 'Not specified';
              const items = text.split('\n').filter(item => item.trim() !== '');
              if (items.length === 0) return 'Not specified';
              return '<ul>' + items.map(item => `<li>${item}</li>`).join('') + '</ul>';
            };
            
            document.getElementById('modalJobEligibility').innerHTML = formatList(job.eligibility);
            document.getElementById('modalJobQualification').innerHTML = formatList(job.qualification);
            document.getElementById('modalJobExperience').innerHTML = formatList(job.experience);
            document.getElementById('modalJobTraining').innerHTML = formatList(job.training);
            
            // Set apply button link
            document.getElementById('modalApplyBtn').href = `application.php?job_id=${job.position_id}`;
            
            // Show modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
          }
        });
      });
      
      // Close modal handlers
      function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
      
      closeModalBtn.addEventListener('click', closeModal);
      closeModalBtn2.addEventListener('click', closeModal);
      
      // Close modal when clicking outside
      window.addEventListener('click', function(event) {
        if (event.target === modal) {
          closeModal();
        }
      });
      
      // Close modal with Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          closeModal();
        }
      });

      // Filter buttons functionality
      const filterBtns = document.querySelectorAll('.filter-btn');
      
      filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');
          
          filterBtns.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          
          if (filter === 'all') {
            document.querySelector('.teaching-section').style.display = 'block';
            document.querySelector('.non-teaching-section').style.display = 'block';
            document.querySelector('.teaching-jobs').style.display = 'grid';
            document.querySelector('.non-teaching-jobs').style.display = 'grid';
          } else if (filter === 'teaching') {
            document.querySelector('.teaching-section').style.display = 'block';
            document.querySelector('.non-teaching-section').style.display = 'none';
            document.querySelector('.teaching-jobs').style.display = 'grid';
            document.querySelector('.non-teaching-jobs').style.display = 'none';
          } else if (filter === 'non-teaching') {
            document.querySelector('.teaching-section').style.display = 'none';
            document.querySelector('.non-teaching-section').style.display = 'block';
            document.querySelector('.teaching-jobs').style.display = 'none';
            document.querySelector('.non-teaching-jobs').style.display = 'grid';
          }
        });
      });
      
      // Mobile sidebar toggle
      const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
      const sidebar = document.querySelector('.sidebar');
      
      if (mobileSidebarToggle && sidebar) {
        mobileSidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('show');
        });
      }
      
      // Check screen size and show/hide mobile toggle
      function checkScreenSize() {
        if (window.innerWidth < 992) {
          mobileSidebarToggle.style.display = 'block';
          sidebar.classList.remove('show');
        } else {
          mobileSidebarToggle.style.display = 'none';
          sidebar.classList.add('show');
        }
      }
      
      window.addEventListener('resize', checkScreenSize);
      checkScreenSize();
    });