    // Global variables to store current evaluation data
    let currentApplicationId = null;
    let currentCategory = null;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            once: true
        });

        // Initialize DataTable
        $('#applicantsTable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search applicants...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Mobile sidebar toggle
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



    // Add Evaluation Modal Functions
    function showAddEvaluationModal() {
        document.getElementById('add-evaluation-modal').style.display = 'block';
    }

    function closeAddEvaluationModal() {
        document.getElementById('add-evaluation-modal').style.display = 'none';
    }

    function startNewEvaluation(applicationId, name, position, category) {
        // Set form values
        document.getElementById('application-id').value = applicationId;
        document.getElementById('applicant-name').value = name;
        document.getElementById('applicant-position').value = position;
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = new Date().toLocaleDateString();
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Set default values for new evaluation
        document.getElementById('interview-personality').value = 8;
        document.getElementById('interview-communication').value = 8;
        document.getElementById('interview-analytical').value = 8;
        document.getElementById('interview-achievement').value = 8;
        document.getElementById('interview-leadership').value = 8;
        document.getElementById('interview-relationship').value = 8;
        document.getElementById('interview-jobfit').value = 8;
        document.getElementById('aptitude-test').value = 4;
        
        if (category === 'Teaching') {
            document.getElementById('education-rating').value = 35;
            document.getElementById('education-units').value = 0;
        } else {
            document.getElementById('nt-education-rating').value = 30;
            document.getElementById('nt-education-units').value = 0;
        }
        
        document.getElementById('experience-rating').value = 15;
        document.getElementById('additional-experience').value = 0;
        document.getElementById('training-rating').value = 5;
        document.getElementById('eligibility-rating').value = 10;
        document.getElementById('accomplishment-rating').value = 0;
        
        // Open evaluation modal
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    function startEvaluation() {
        const select = document.getElementById('applicant-select');
        const applicationId = select.value;
        const selectedOption = select.options[select.selectedIndex];
        const category = selectedOption.getAttribute('data-category');
        
        if (!applicationId) {
            alert('Please select an applicant to evaluate');
            return;
        }
        
        // Set form values
        document.getElementById('application-id').value = applicationId;
        document.getElementById('applicant-name').value = selectedOption.text;
        document.getElementById('applicant-position').value = '';
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = new Date().toLocaleDateString();
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Set default values for new evaluation
        document.getElementById('interview-personality').value = 8;
        document.getElementById('interview-communication').value = 8;
        document.getElementById('interview-analytical').value = 8;
        document.getElementById('interview-achievement').value = 8;
        document.getElementById('interview-leadership').value = 8;
        document.getElementById('interview-relationship').value = 8;
        document.getElementById('interview-jobfit').value = 8;
        document.getElementById('aptitude-test').value = 4;
        
        if (category === 'Teaching') {
            document.getElementById('education-rating').value = 35;
            document.getElementById('education-units').value = 0;
        } else {
            document.getElementById('nt-education-rating').value = 30;
            document.getElementById('nt-education-units').value = 0;
        }
        
        document.getElementById('experience-rating').value = 15;
        document.getElementById('additional-experience').value = 0;
        document.getElementById('training-rating').value = 5;
        document.getElementById('eligibility-rating').value = 10;
        document.getElementById('accomplishment-rating').value = 0;
        
        // Close add evaluation modal and open evaluation modal
        closeAddEvaluationModal();
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    // Evaluation Modal Functions
    function editApplicant(id, name, position, category, date, score) {
        document.getElementById('application-id').value = id;
        document.getElementById('applicant-name').value = name;
        document.getElementById('applicant-position').value = position;
        document.getElementById('applicant-category').value = category;
        document.getElementById('application-date').value = date;
        
        // Show/hide fields based on category
        if (category === 'Teaching') {
            document.getElementById('teaching-fields').classList.add('active-position-type');
            document.getElementById('non-teaching-fields').classList.remove('active-position-type');
        } else {
            document.getElementById('teaching-fields').classList.remove('active-position-type');
            document.getElementById('non-teaching-fields').classList.add('active-position-type');
        }
        
        // Fetch existing evaluation data if available
        fetchEvaluationData(id, category);
        
        document.getElementById('evaluation-modal').style.display = 'block';
    }

    function fetchEvaluationData(applicationId, category) {
        // Make an AJAX request to fetch evaluation data
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: applicationId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    // Set form values
                    document.getElementById('interview-personality').value = evalData.personality || 8;
                    document.getElementById('interview-communication').value = evalData.communication || 8;
                    document.getElementById('interview-analytical').value = evalData.analytical || 8;
                    document.getElementById('interview-achievement').value = evalData.achievement || 8;
                    document.getElementById('interview-leadership').value = evalData.leadership || 8;
                    document.getElementById('interview-relationship').value = evalData.relationship || 8;
                    document.getElementById('interview-jobfit').value = evalData.jobfit || 8;
                    document.getElementById('aptitude-test').value = evalData.aptitude || 4;
                    
                    if (category === 'Teaching') {
                        document.getElementById('education-rating').value = evalData.education_rating || 35;
                        document.getElementById('education-units').value = evalData.education_units || 0;
                    } else {
                        document.getElementById('nt-education-rating').value = evalData.education_rating || 30;
                        document.getElementById('nt-education-units').value = evalData.education_units || 0;
                    }
                    
                    // Fix for experience rating issue
                    let experienceRating = evalData.experience_rating || 15;
                    if (experienceRating === 15) {
                        document.getElementById('experience-rating').value = 15;
                    } else if (experienceRating === 10) {
                        document.getElementById('experience-rating').value = 10;
                    } else {
                        document.getElementById('experience-rating').value = 5;
                    }
                    
                    document.getElementById('additional-experience').value = evalData.additional_experience || 0;
                    document.getElementById('training-rating').value = evalData.training_rating || 5;
                    document.getElementById('eligibility-rating').value = evalData.eligibility_rating || 10;
                    document.getElementById('accomplishment-rating').value = evalData.accomplishment_rating || 0;
                } else {
                    // Set default values if no evaluation exists
                    document.getElementById('interview-personality').value = 8;
                    document.getElementById('interview-communication').value = 8;
                    document.getElementById('interview-analytical').value = 8;
                    document.getElementById('interview-achievement').value = 8;
                    document.getElementById('interview-leadership').value = 8;
                    document.getElementById('interview-relationship').value = 8;
                    document.getElementById('interview-jobfit').value = 8;
                    document.getElementById('aptitude-test').value = 4;
                    
                    if (category === 'Teaching') {
                        document.getElementById('education-rating').value = 35;
                        document.getElementById('education-units').value = 0;
                    } else {
                        document.getElementById('nt-education-rating').value = 30;
                        document.getElementById('nt-education-units').value = 0;
                    }
                    
                    document.getElementById('experience-rating').value = 15;
                    document.getElementById('additional-experience').value = 0;
                    document.getElementById('training-rating').value = 5;
                    document.getElementById('eligibility-rating').value = 10;
                    document.getElementById('accomplishment-rating').value = 0;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function closeModal() {
        document.getElementById('evaluation-modal').style.display = 'none';
    }

    // Delete Confirmation Modal Functions
    function confirmDelete(applicationId, name) {
        document.getElementById('delete-confirmation-message').textContent = 
            `Are you sure you want to delete the evaluation for ${name}?`;
        document.getElementById('delete-application-id').value = applicationId;
        document.getElementById('delete-confirmation-modal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('delete-confirmation-modal').style.display = 'none';
    }

    // View Evaluation Modal Functions
    function viewEvaluation(id, name, position, category) {
        currentApplicationId = id;
        currentCategory = category;
        
        // Make an AJAX request to fetch evaluation data
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: id },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    // Calculate scores
                    const interviewTotal = evalData.personality + evalData.communication +
                                          evalData.analytical + evalData.achievement +
                                          evalData.leadership + evalData.relationship +
                                          evalData.jobfit;

                    let potentialScore, educationScore;

                    // Interview part: 70/70 = 10%
                    const interviewPercent = (interviewTotal / 70) * 10;

                    // Aptitude part: 5/5 = 5%
                    const aptitudePercent = (evalData.aptitude / 5) * 5;

                    // Total potential = Interview (10%) + Aptitude (5%) = 15%
                    if (category === 'Teaching') {
                        potentialScore = interviewPercent + aptitudePercent;
                        educationScore = evalData.education_rating + evalData.education_units;
                    } else {
                        potentialScore = interviewPercent + aptitudePercent;
                        educationScore = evalData.education_rating + evalData.education_units;
                    }
                    
                    const experienceScore = evalData.experience_rating + evalData.additional_experience;
                    const trainingScore = evalData.training_rating;
                    const eligibilityScore = evalData.eligibility_rating;
                    const accomplishmentScore = evalData.accomplishment_rating;
                    
                    const totalScore = potentialScore + educationScore + experienceScore + 
                                      trainingScore + eligibilityScore + accomplishmentScore;
                    
                    // Build the view content
                    let viewContent = `
                        <div class="view-section">
                            <h4>Applicant Information</h4>
                            <div class="view-row">
                                <div class="view-label">Name:</div>
                                <div class="view-value">${name}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Position:</div>
                                <div class="view-value">${position}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Category:</div>
                                <div class="view-value">${category}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Interview Scores</h4>
                            <div class="view-row">
                                <div class="view-label">Personality:</div>
                                <div class="view-value">${evalData.personality}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Communication:</div>
                                <div class="view-value">${evalData.communication}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Analytical Skills:</div>
                                <div class="view-value">${evalData.analytical}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Achievement Orientation:</div>
                                <div class="view-value">${evalData.achievement}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Leadership/Management:</div>
                                <div class="view-value">${evalData.leadership}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Relationship Management:</div>
                                <div class="view-value">${evalData.relationship}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Job Fit:</div>
                                <div class="view-value">${evalData.jobfit}/10</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Aptitude Test:</div>
                                <div class="view-value">${getAptitudeLabel(evalData.aptitude)} (${evalData.aptitude})</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Interview Total:</div>
                                <div class="view-value">${interviewTotal}/70</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Education</h4>
                            <div class="view-row">
                                <div class="view-label">Education Rating:</div>
                                <div class="view-value">${evalData.education_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Additional Units:</div>
                                <div class="view-value">${evalData.education_units}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Experience & Training</h4>
                            <div class="view-row">
                                <div class="view-label">Experience Rating:</div>
                                <div class="view-value">${evalData.experience_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Additional Experience:</div>
                                <div class="view-value">${evalData.additional_experience}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Training Rating:</div>
                                <div class="view-value">${evalData.training_rating}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Other Criteria</h4>
                            <div class="view-row">
                                <div class="view-label">Eligibility Rating:</div>
                                <div class="view-value">${evalData.eligibility_rating}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Accomplishment Rating:</div>
                                <div class="view-value">${evalData.accomplishment_rating}</div>
                            </div>
                        </div>
                        
                        <div class="view-section">
                            <h4>Score Summary</h4>
                            <div class="view-row">
                                <div class="view-label">Potential Score (15%):</div>
                                <div class="view-value">${potentialScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Education Score (40%):</div>
                                <div class="view-value">${educationScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Experience Score (20%):</div>
                                <div class="view-value">${experienceScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Training Score (10%):</div>
                                <div class="view-value">${trainingScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Eligibility Score (10%):</div>
                                <div class="view-value">${eligibilityScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label">Accomplishment Score (5%):</div>
                                <div class="view-value">${accomplishmentScore.toFixed(2)}</div>
                            </div>
                            <div class="view-row">
                                <div class="view-label"><strong>Total Score:</strong></div>
                                <div class="view-value"><strong>${totalScore.toFixed(2)}/100</strong></div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('view-content').innerHTML = viewContent;
                    document.getElementById('view-modal').style.display = 'block';
                } else {
                    alert('Error loading evaluation data: ' + (data.message || 'No evaluation data found'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function getAptitudeLabel(score) {
        switch(parseInt(score)) {
            case 5: return 'Superior';
            case 4: return 'Above Average';
            case 3: return 'Average';
            case 2: return 'Below Average';
            case 1: return 'Lowest';
            default: return 'Not Rated';
        }
    }

    function closeViewModal() {
        document.getElementById('view-modal').style.display = 'none';
    }

    function printFromView() {
        closeViewModal();
        showPrintModal(currentApplicationId);
    }

    // Print Modal Functions
    function showPrintModal(id, name = null, position = null, category = null) {
        // If we're calling from PHP with just an ID, we need to fetch the details
        if (name === null) {
            $.ajax({
                url: 'get_application_details.php',
                type: 'GET',
                data: { application_id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        loadPrintForm(id, data.application.name, data.application.position, data.application.category);
                    } else {
                        alert('Error loading application details: ' + (data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching application details:', error);
                    alert('Error loading application details. Please try again.');
                }
            });
        } else {
            loadPrintForm(id, name, position, category);
        }
    }

    function loadPrintForm(id, name, position, category) {
        // First hide both forms
        document.getElementById('print-teaching-form').style.display = 'none';
        document.getElementById('print-non-teaching-form').style.display = 'none';
        
        // Fetch evaluation data for printing
        $.ajax({
            url: 'get_evaluation.php',
            type: 'GET',
            data: { application_id: id },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const evalData = data.evaluation;
                    
                    if (category === 'Teaching') {
                        // Show teaching form
                        const teachingForm = document.getElementById('print-teaching-form');
                        teachingForm.style.display = 'block';
                        
                        // Basic info
                        document.getElementById('print-name').textContent = name;
                        document.getElementById('print-position').textContent = position;
                        
                        // Set evaluation data
                        document.getElementById('print-personality').textContent = evalData.personality;
                        document.getElementById('print-communication').textContent = evalData.communication;
                        document.getElementById('print-analytical').textContent = evalData.analytical;
                        document.getElementById('print-achievement').textContent = evalData.achievement;
                        document.getElementById('print-leadership').textContent = evalData.leadership;
                        document.getElementById('print-relationship').textContent = evalData.relationship;
                        document.getElementById('print-jobfit').textContent = evalData.jobfit;
                        
                        // Calculate and set totals
                        const interviewTotal = evalData.personality + evalData.communication + 
                                              evalData.analytical + evalData.achievement + 
                                              evalData.leadership + evalData.relationship + 
                                              evalData.jobfit;
                        
                        document.getElementById('print-interview-total').textContent = interviewTotal;
                        
                        // Set aptitude test
                        const aptitudeOptions = ['superior', 'above', 'average', 'below', 'lowest'];
                        const aptitudeValue = evalData.aptitude;
                        document.getElementById(`print-aptitude-${aptitudeOptions[5 - aptitudeValue]}`).textContent = aptitudeValue;
                        
                        // Set education
                        document.getElementById('print-education-main').textContent = evalData.education_rating + evalData.education_units;
                        document.getElementById('print-education-basic').textContent = evalData.education_rating;
                        
                        if (evalData.education_units > 0) {
                            document.getElementById(`print-education-${evalData.education_units * 20}`).textContent = evalData.education_units;
                        }
                        
                        // Set experience - fixed the experience rating mapping
                        const experienceRating = evalData.experience_rating;
                        let experienceOption = '';
                        if (experienceRating === 15) experienceOption = '5-10';
                        else if (experienceRating === 10) experienceOption = '3-4';
                        else experienceOption = '1-2';
                        
                        document.getElementById(`print-experience-${experienceOption}`).textContent = experienceRating;
                        document.getElementById('print-experience-additional').textContent = evalData.additional_experience;
                        
                        // Set training
                        document.getElementById('print-training-basic').textContent = 5;
                        document.getElementById('print-training-additional').textContent = evalData.training_rating - 5;
                        
                        // Set eligibility and accomplishments
                        document.getElementById('print-eligibility').textContent = evalData.eligibility_rating;
                        document.getElementById('print-accomplishments').textContent = evalData.accomplishment_rating;
                        
                        // Calculate summary scores
                        calculateTeachingScores(evalData);
                        
                        // Load into preview
                        document.getElementById('print-preview').innerHTML = teachingForm.innerHTML;
                    } else {
                        // Show non-teaching form
                        const nonTeachingForm = document.getElementById('print-non-teaching-form');
                        nonTeachingForm.style.display = 'block';
                        
                        // Basic info
                        document.getElementById('nt-print-name').textContent = name;
                        document.getElementById('nt-print-position').textContent = position;
                        
                        // Set evaluation data
                        document.getElementById('nt-print-personality').textContent = evalData.personality;
                        document.getElementById('nt-print-communication').textContent = evalData.communication;
                        document.getElementById('nt-print-analytical').textContent = evalData.analytical;
                        document.getElementById('nt-print-achievement').textContent = evalData.achievement;
                        document.getElementById('nt-print-leadership').textContent = evalData.leadership;
                        document.getElementById('nt-print-relationship').textContent = evalData.relationship;
                        document.getElementById('nt-print-jobfit').textContent = evalData.jobfit;
                        
                        // Calculate and set totals
                        const interviewTotal = evalData.personality + evalData.communication + 
                                              evalData.analytical + evalData.achievement + 
                                              evalData.leadership + evalData.relationship + 
                                              evalData.jobfit;
                        
                        document.getElementById('nt-print-interview-total').textContent = interviewTotal;
                        
                        // Set aptitude test
                        const aptitudeOptions = ['superior', 'above', 'average', 'below', 'lowest'];
                        const aptitudeValue = evalData.aptitude;
                        document.getElementById(`nt-print-aptitude-${aptitudeOptions[5 - aptitudeValue]}`).textContent = aptitudeValue;
                        
                        // Set education
                        document.getElementById('nt-print-education-main').textContent = evalData.education_rating + evalData.education_units;
                        document.getElementById('nt-print-education-basic').textContent = evalData.education_rating;
                        
                        if (evalData.education_units > 0) {
                            if (evalData.education_units <= 5) {
                                document.getElementById(`nt-print-education-${evalData.education_units * 25}`).textContent = evalData.education_units;
                            } else {
                                document.getElementById(`nt-print-education-d${(evalData.education_units - 5) * 25}`).textContent = evalData.education_units;
                            }
                        }
                        
                        // Set experience - fixed the experience rating mapping
                        const experienceRating = evalData.experience_rating;
                        let experienceOption = '';
                        if (experienceRating === 15) experienceOption = '5-10';
                        else if (experienceRating === 10) experienceOption = '3-4';
                        else experienceOption = '1-2';
                        
                        document.getElementById(`nt-print-experience-${experienceOption}`).textContent = experienceRating;
                        document.getElementById('nt-print-experience-additional').textContent = evalData.additional_experience;
                        
                        // Set training
                        document.getElementById('nt-print-training-basic').textContent = 5;
                        document.getElementById('nt-print-training-additional').textContent = evalData.training_rating - 5;
                        
                        // Set eligibility and accomplishments
                        document.getElementById('nt-print-eligibility').textContent = evalData.eligibility_rating;
                        document.getElementById('nt-print-accomplishments').textContent = evalData.accomplishment_rating;
                        
                        // Calculate summary scores
                        calculateNonTeachingScores(evalData);
                        
                        // Load into preview
                        document.getElementById('print-preview').innerHTML = nonTeachingForm.innerHTML;
                    }
                    
                    // Show the print modal
                    document.getElementById('print-modal').style.display = 'block';
                } else {
                    alert('Error loading evaluation data: ' + (data.message || 'No evaluation data found'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching evaluation data:', error);
                alert('Error loading evaluation data. Please try again.');
            }
        });
    }

    function closePrintModal() {
        document.getElementById('print-modal').style.display = 'none';
    }

    function printEvaluationForm() {
        // First hide both forms
        document.getElementById('print-teaching-form').style.display = 'none';
        document.getElementById('print-non-teaching-form').style.display = 'none';
        
        // Determine which form to print based on the preview content
        const previewContent = document.getElementById('print-preview').innerHTML;
        const isTeachingForm = previewContent.includes('NEW APPLICANTS FOR TEACHING POSITION');
        
        if (isTeachingForm) {
            document.getElementById('print-teaching-form').style.display = 'block';
        } else {
            document.getElementById('print-non-teaching-form').style.display = 'block';
        }
        
        // Print only the active form
        window.print();
    }

    function calculateTeachingScores(evalData) {
        // Calculate potential score (15% of total)
        const interviewTotal = evalData.personality + evalData.communication + 
                              evalData.analytical + evalData.achievement + 
                              evalData.leadership + evalData.relationship + 
                              evalData.jobfit;
        
        const interviewScorePercent = (interviewTotal / 70) * 10; // Max 10 points
        const aptitudeScorePercent = (evalData.aptitude / 5) * 5; // Max 5 points

        const potentialScore = interviewScorePercent + aptitudeScorePercent;
        
        // Calculate education score (40% of total)
        const educationScore = evalData.education_rating + evalData.education_units;
        
        // Calculate experience score (20% of total)
        const experienceScore = evalData.experience_rating + evalData.additional_experience;
        
        // Calculate training score (10% of total)
        const trainingScore = evalData.training_rating;
        
        // Calculate eligibility score (10% of total)
        const eligibilityScore = evalData.eligibility_rating;
        
        // Calculate accomplishment score (5% of total)
        const accomplishmentScore = evalData.accomplishment_rating;
        
        // Calculate total score
        const totalScore = potentialScore + educationScore + experienceScore + 
                          trainingScore + eligibilityScore + accomplishmentScore;
        
        // Update summary table
        document.getElementById('print-summary-potential').textContent = potentialScore.toFixed(2);
        document.getElementById('print-summary-education').textContent = educationScore.toFixed(2);
        document.getElementById('print-summary-experience').textContent = experienceScore.toFixed(2);
        document.getElementById('print-summary-training').textContent = trainingScore.toFixed(2);
        document.getElementById('print-summary-eligibility').textContent = eligibilityScore.toFixed(2);
        document.getElementById('print-summary-accomplishment').textContent = accomplishmentScore.toFixed(2);
        document.getElementById('print-summary-total').textContent = totalScore.toFixed(2);
    }

    function calculateNonTeachingScores(evalData) {
        // Calculate potential score (15% of total)
        const interviewTotal = evalData.personality + evalData.communication + 
                              evalData.analytical + evalData.achievement + 
                              evalData.leadership + evalData.relationship + 
                              evalData.jobfit;
        
        const interviewScorePercent = (interviewTotal / 70) * 10; // Max 10 points
        const aptitudeScorePercent = (evalData.aptitude / 5) * 5; // Max 5 points

        const potentialScore = interviewScorePercent + aptitudeScorePercent;
        
        // Calculate education score (40% of total)
        const educationScore = evalData.education_rating + evalData.education_units;
        
        // Calculate experience score (20% of total)
        const experienceScore = evalData.experience_rating + evalData.additional_experience;
        
        // Calculate training score (10% of total)
        const trainingScore = evalData.training_rating;
        
        // Calculate eligibility score (10% of total)
        const eligibilityScore = evalData.eligibility_rating;
        
        // Calculate accomplishment score (5% of total)
        const accomplishmentScore = evalData.accomplishment_rating;
        
        // Calculate total score
        const totalScore = potentialScore + educationScore + experienceScore + 
                          trainingScore + eligibilityScore + accomplishmentScore;
        
        // Update summary table
        document.getElementById('nt-print-summary-potential').textContent = potentialScore.toFixed(2);
        document.getElementById('nt-print-summary-education').textContent = educationScore.toFixed(2);
        document.getElementById('nt-print-summary-experience').textContent = experienceScore.toFixed(2);
        document.getElementById('nt-print-summary-training').textContent = trainingScore.toFixed(2);
        document.getElementById('nt-print-summary-eligibility').textContent = eligibilityScore.toFixed(2);
        document.getElementById('nt-print-summary-accomplishment').textContent = accomplishmentScore.toFixed(2);
        document.getElementById('nt-print-summary-total').textContent = totalScore.toFixed(2);
    }});