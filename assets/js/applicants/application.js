document.addEventListener('DOMContentLoaded', function () {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Initialize flatpickr for all date inputs
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // File input handling
const fileInputs = {
    'resume': 'resume_label',
    'application_letter': 'application_letter_label',
    'personal_data_sheet': 'personal_data_sheet_label',
    'transcript_of_records': 'transcript_of_records_label',
    'proof_of_eligibility': 'proof_of_eligibility_label',
    'other_documents': 'other_documents_label'
};

    Object.keys(fileInputs).forEach(inputId => {
        const input = document.getElementById(inputId);
        const label = document.getElementById(fileInputs[inputId]);

        if (input && label) {
            input.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                    
                    // Validate file type and size
                    const file = this.files[0];
                    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert('Error: Only PDF, Word, and Excel documents are allowed.');
                        this.value = '';
                        label.textContent = inputId === 'other_documents' 
                            ? 'Choose file (Optional, PDF or Word, max 5MB)' 
                            : 'Choose file (PDF or Word, max 5MB)';
                        return;
                    }
                    
                    if (file.size > maxSize) {
                        alert('Error: File size exceeds 5MB limit.');
                        this.value = '';
                        label.textContent = inputId === 'other_documents' 
                            ? 'Choose file (Optional, PDF or Word, max 5MB)' 
                            : 'Choose file (PDF or Word, max 5MB)';
                        return;
                    }
                } else {
                    label.textContent = inputId === 'other_documents'
                        ? 'Choose file (Optional, PDF or Word, max 5MB)'
                        : 'Choose file (PDF or Word, max 5MB)';
                }
            });
        }
    });

    // Form submission confirmation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            const declaration = document.getElementById('declaration');
            if (!declaration.checked) {
                e.preventDefault();
                alert('You must agree to the declaration before submitting.');
                declaration.focus();
                return;
            }
            
            // Validate salary grade format (00-0)
            const salaryGradeInputs = document.querySelectorAll('input[name="work_salary_grade[]"]');
            for (let input of salaryGradeInputs) {
                if (input.value && !/^\d{2}-\d$/.test(input.value)) {
                    alert('Please enter salary grade in the correct format (00-0)');
                    input.focus();
                    e.preventDefault();
                    return;
                }
            }
            
            if (!confirm("Are you sure you want to submit the application? You won't be able to edit it after submission.")) {
                e.preventDefault();
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

    // Check on window resize
    window.addEventListener('resize', checkMobileView);

    // Initialize all validation functions
    validatePhoneNumber();
    validateWorkDates();
    validateEducationDates();
});

// Global functions for dynamic form elements
let workExperienceCount = 1;
let educationCount = 1;

function addWorkExperience() {
    workExperienceCount++;
    const container = document.getElementById('work-experience-container');
    const newItem = document.createElement('div');
    newItem.className = 'work-experience-item mt-4';
    newItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Work Experience #${workExperienceCount}</h5>
            <button type="button" class="btn btn-sm btn-outline-danger remove-btn" onclick="removeWorkExperience(this)">
                <i class="fas fa-trash-alt me-1"></i> Remove
            </button>
        </div>
        
        <div class="form-group">
            <label for="work_position[]" class="form-label">POSITION TITLE (Write in full/ Do not abbreviate)</label>
            <input type="text" name="work_position[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="work_company[]" class="form-label">DEPARTMENT / AGENCY/ OFFICE / COMPANY (Write in full/ Do not abbreviate)</label>
            <input type="text" name="work_company[]" class="form-control" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="work_start_date[]" class="form-label">From</label>
                    <input type="date" name="work_start_date[]" class="form-control datepicker" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="work_end_date[]" class="form-label">To</label>
                    <input type="date" name="work_end_date[]" class="form-control datepicker" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="work_salary[]" class="form-label">Monthly Salary</label>
            <input type="text" name="work_salary[]" class="form-control" required>
        </div>

        <!-- Newly Added Form Group -->
        <div class="form-group">
        <label for="work_salary_grade[]" class="form-label">
        SALARY / JOB / PAY GRADE (if applicable) & STEP (Format *00-0*) / INCREMENT
        </label>
        <input type="text" name="work_salary_grade[]" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="work_status[]" class="form-label">STATUS OF APPOINTMENT</label>
            <input type="text" name="work_status[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="work_govt_service[]" class="form-label">GOVERMENT SERVICE (Y/N)</label>
            <select name="work_govt_service[]" class="form-control form-select" required>
                <option value="Y">Yes</option>
                <option value="N">No</option>
            </select>
        </div>
    `;
    
    container.appendChild(newItem);
    
    // Initialize flatpickr for the new date inputs
    flatpickr(newItem.querySelectorAll('.datepicker'), {
        dateFormat: "Y-m-d",
        allowInput: true
    });
    
    // Scroll to the new element
    newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Add validation for the new salary grade field
    const salaryGradeInput = newItem.querySelector('input[name="work_salary_grade[]"]');
    if (salaryGradeInput) {
        salaryGradeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^\d-]/g, '');
        });
    }
}

function removeWorkExperience(button) {
    if (workExperienceCount > 1) {
        const item = button.closest('.work-experience-item');
        item.remove();
        workExperienceCount--;
        
        // Update the numbering of remaining items
        const items = document.querySelectorAll('.work-experience-item');
        items.forEach((item, index) => {
            const title = item.querySelector('h5');
            if (title) {
                title.textContent = `Work Experience #${index + 1}`;
            }
        });
    } else {
        alert('You must have at least one work experience entry.');
    }
}

function addEducation() {
    educationCount++;
    const container = document.getElementById('education-container');
    const newItem = document.createElement('div');
    newItem.className = 'education-item mt-4';
    newItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Education #${educationCount}</h5>
            <button type="button" class="btn btn-sm btn-outline-danger remove-btn" onclick="removeEducation(this)">
                <i class="fas fa-trash-alt me-1"></i> Remove
            </button>
        </div>
        
        <div class="form-group">
            <label for="education_level[]" class="form-label">Level</label>
            <select name="education_level[]" class="form-control form-select" required>
                <option value="">Select Level</option>
                <option value="ELEMENTARY">Elementary</option>
                <option value="SECONDARY">Secondary</option>
                <option value="VOCATIONAL / TRADE COURSE">Vocational/Trade Course</option>
                <option value="COLLEGE">College</option>
                <option value="GRADUATE STUDIES">Graduate Studies</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="education_school[]" class="form-label">NAME OF SCHOOL (Write in full)</label>
            <input type="text" name="education_school[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="education_degree[]" class="form-label">BASIC EDUCATION / DEGREE/ COURSE (Write in full)</label>
            <input type="text" name="education_degree[]" class="form-control" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="education_start_date[]" class="form-label">PERIOD OF ATTENDANCE (From)</label>
                    <input type="date" name="education_start_date[]" class="form-control datepicker" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="education_end_date[]" class="form-label">PERIOD OF ATTENDANCE (To)</label>
                    <input type="date" name="education_end_date[]" class="form-control datepicker" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="education_highest_level[]" class="form-label">HIGHEST LEVEL/ UNITS EARNED (if not graduated)</label>
            <input type="text" name="education_highest_level[]" class="form-control" placeholder="If not graduated">
        </div>
        
        <div class="form-group">
            <label for="education_year_graduated[]" class="form-label">YEAR GRADUATED</label>
            <input type="text" name="education_year_graduated[]" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="education_honors[]" class="form-label">SCHOLARSHIP / ACADEMIC HONORS RECIEVED</label>
            <input type="text" name="education_honors[]" class="form-control">
        </div>
    `;
    
    container.appendChild(newItem);
    
    // Initialize flatpickr for the new date inputs
    flatpickr(newItem.querySelectorAll('.datepicker'), {
        dateFormat: "Y-m-d",
        allowInput: true
    });
    
    // Scroll to the new element
    newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeEducation(button) {
    if (educationCount > 1) {
        const item = button.closest('.education-item');
        item.remove();
        educationCount--;
        
        // Update the numbering of remaining items
        const items = document.querySelectorAll('.education-item');
        items.forEach((item, index) => {
            const title = item.querySelector('h5');
            if (title) {
                title.textContent = `Education #${index + 1}`;
            }
        });
    } else {
        alert('You must have at least one education entry.');
    }
}

// Phone number validation
function validatePhoneNumber() {
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/[^\d]/g, '');
            
            // Validate length (11 digits for mobile numbers in the Philippines)
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    }
}

// Date validation for work experience
function validateWorkDates() {
    const container = document.getElementById('work-experience-container');
    if (container) {
        container.addEventListener('change', function(e) {
            if (e.target.name === 'work_start_date[]' || e.target.name === 'work_end_date[]') {
                const item = e.target.closest('.work-experience-item');
                const startDate = item.querySelector('input[name="work_start_date[]"]');
                const endDate = item.querySelector('input[name="work_end_date[]"]');
                
                if (startDate.value && endDate.value && startDate.value > endDate.value) {
                    alert('End date must be after start date');
                    e.target.value = '';
                }
            }
        });
    }
}

// Date validation for education
function validateEducationDates() {
    const container = document.getElementById('education-container');
    if (container) {
        container.addEventListener('change', function(e) {
            if (e.target.name === 'education_start_date[]' || e.target.name === 'education_end_date[]') {
                const item = e.target.closest('.education-item');
                const startDate = item.querySelector('input[name="education_start_date[]"]');
                const endDate = item.querySelector('input[name="education_end_date[]"]');
                
                if (startDate.value && endDate.value && startDate.value > endDate.value) {
                    alert('End date must be after start date');
                    e.target.value = '';
                }
            }
        });
    }
}