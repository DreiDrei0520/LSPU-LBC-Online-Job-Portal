// Initialize AOS animation
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true
});

// Navbar scroll effect
$(window).scroll(function () {
    if ($(this).scrollTop() > 100) {
        $('.navbar').addClass('scrolled');
    } else {
        $('.navbar').removeClass('scrolled');
    }
});

// Back to top button
$(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').addClass('active');
    } else {
        $('.back-to-top').removeClass('active');
    }
});

$('.back-to-top').click(function (e) {
    e.preventDefault();
    $('html, body').animate({ scrollTop: 0 }, '300');
});

// Handle sort select change
$('#sort-select').change(function () {
    $('input[name="sort"]').val($(this).val());
    $('#filters-form').submit();
});

// Job details modal
$(document).on('click', '.view-details', function () {
    const jobId = $(this).data('job-id');

    // Show loading state
    $('#modal-job-title').text('Loading...');
    $('#modal-job-department, #modal-job-type, #modal-job-location, #modal-job-date').text('');
    $('#modal-job-place, #modal-job-eligibility, #modal-job-qualification, #modal-job-experience, #modal-job-training').text('');

    // Fetch job details via AJAX
    $.ajax({
        url: 'get_job_details.php', // Go up 2 levels from assets/js/
        method: 'GET',
        data: { id: jobId },
        dataType: 'json',
        success: function (job) {
            // Format date
            const postedDate = new Date(job.date_posted);
            const formattedDate = postedDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Populate modal
            $('#modal-job-title').text(job.title || 'Not specified');
            $('#modal-job-department').text(job.department || 'Not specified');
            $('#modal-job-type').text(job.type || 'Not specified');
            $('#modal-job-location').text(job.location || 'Not specified');
            $('#modal-job-date').text(formattedDate);
            $('#modal-job-place').text(job.place_of_assignment || 'Not specified');
            $('#modal-job-description').text(job.description || 'Not specified');
            $('#modal-job-salary').text(job.salary_range || 'Not specified');

            // Set apply button
            const applyBtn = $('#modal-apply-btn');
            applyBtn.data('job-id', jobId);

            // Show modal
            const jobModal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
            jobModal.show();

            // Fetch requirements
            $.ajax({
                url: 'get_job_requirements.php', // Go up 2 levels
                method: 'GET',
                data: { position_id: jobId },
                dataType: 'json',
                success: function (requirements) {
                    $('#modal-job-eligibility').text(requirements.eligibility || 'Not specified');
                    $('#modal-job-qualification').text(requirements.qualification || 'Not specified');
                    $('#modal-job-experience').text(requirements.experience || 'Not specified');
                    $('#modal-job-training').text(requirements.training || 'Not specified');
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching requirements:', error);
                    $('#modal-job-eligibility, #modal-job-qualification, #modal-job-experience, #modal-job-training').text('Not specified');
                }
            });
        },
        error: function (xhr, status, error) {
            $('#modal-job-title').text('Error loading job details');
            console.error('Error fetching job details:', error);

            // Show modal anyway
            const jobModal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
            jobModal.show();
        }
    });
});

// Make job cards clickable
$('.job-card').on('click', function (e) {
    if ($(e.target).is('a, button, .apply-btn, .view-details') || $(e.target).closest('a, button').length) {
        return;
    }

    $(this).find('.view-details').trigger('click');
});