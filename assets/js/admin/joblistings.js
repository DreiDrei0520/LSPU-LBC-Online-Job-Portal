document.addEventListener('DOMContentLoaded', function () {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Show mobile toggle button only on mobile
    function checkMobileView() {
        const toggleBtn = document.getElementById('mobileSidebarToggle');
        if (window.innerWidth < 992) {
            toggleBtn.style.display = 'flex';
        } else {
            toggleBtn.style.display = 'none';
        }
    }

    // Initial check
    checkMobileView();

    // Check on resize
    window.addEventListener('resize', checkMobileView);

    // Mobile sidebar toggle
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('show');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 992) {
            if (!sidebar.contains(e.target) && !mobileSidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Auto-focus first input in create modal when shown
    const createModal = document.getElementById('createJobModal');
    if (createModal) {
        createModal.addEventListener('shown.bs.modal', function () {
            document.getElementById('create_title').focus();
        });
    }
});

// Function to open edit modal with job data
function openEditModal(jobId) {
    // Fetch job data via AJAX
    fetch('get_job_data.php?id=' + jobId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Populate the form fields
            document.getElementById('edit_job_id').value = data.position_id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_department_name').value = data.department_name;
            document.getElementById('edit_type').value = data.type;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_location_name').value = data.location_name;
            document.getElementById('edit_place_of_assignment').value = data.place_of_assignment;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_salary_range').value = data.salary_range;
            document.getElementById('edit_status').value = data.status;

            // Populate requirements
            document.getElementById('edit_eligibility').value = data.requirements.eligibility || '';
            document.getElementById('edit_qualification').value = data.requirements.qualification || '';
            document.getElementById('edit_experience').value = data.requirements.experience || '';
            document.getElementById('edit_training').value = data.requirements.training || '';

            // Show the modal
            const editModal = new bootstrap.Modal(document.getElementById('editJobModal'));
            editModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch job data');
        });
}