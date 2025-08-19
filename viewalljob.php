<?php
session_start();
include('db_connection.php');

// Optional: clear the session flag to prevent re-access on refresh
unset($_SESSION['from_home']);

// Pagination settings
$jobsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $jobsPerPage;

// Get search/filter parameters from URL
$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location_id = isset($_GET['location_id']) ? $_GET['location_id'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build SQL query with filters
$sql = "SELECT SQL_CALC_FOUND_ROWS p.position_id, p.title, d.name as department, p.type, p.category, 
               l.name as location, p.date_posted, p.place_of_assignment, p.description, p.salary_range
        FROM job_positions p
        JOIN departments d ON p.department_id = d.department_id
        JOIN locations l ON p.location_id = l.location_id
        WHERE p.status = 'Open'";

$conditions = [];
$params = [];
$types = '';

if (!empty($title)) {
    $conditions[] = "p.title LIKE ?";
    $params[] = "%$title%";
    $types .= 's';
}

if (!empty($category)) {
    $conditions[] = "p.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($location_id)) {
    $conditions[] = "p.location_id = ?";
    $params[] = $location_id;
    $types .= 'i';
}

if (!empty($type)) {
    $conditions[] = "p.type = ?";
    $params[] = $type;
    $types .= 's';
}

if (!empty($department_id)) {
    $conditions[] = "p.department_id = ?";
    $params[] = $department_id;
    $types .= 'i';
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Add sorting
switch ($sort) {
    case 'date_asc':
        $sql .= " ORDER BY p.date_posted ASC";
        break;
    case 'date_desc':
    default:
        $sql .= " ORDER BY p.date_posted DESC";
        break;
}

// Add pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $jobsPerPage;
$params[] = $offset;
$types .= 'ii';

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);

// Get total number of jobs for pagination
$totalJobs = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$totalPages = ceil($totalJobs / $jobsPerPage);

// Get distinct values for filters
$categories = $conn->query("SELECT DISTINCT category FROM job_positions WHERE status = 'Open' ORDER BY category")->fetch_all(MYSQLI_ASSOC);
$types = $conn->query("SELECT DISTINCT type FROM job_positions WHERE status = 'Open' ORDER BY type")->fetch_all(MYSQLI_ASSOC);
$departments = $conn->query("SELECT department_id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT location_id, name FROM locations ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - LSPU-LBC ONLINE JOB PORTAL</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/homepage/viewalljob.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="home.php">
                <img src="images/lspulogo.png" alt="Logo" class="me-2 logo-img">
                <span class="fw-bold d-none d-lg-block">LSPU-LBC Online Job Portal</span>
            </a>  
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="redirect.php?page=viewalljob">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redirect.php?page=about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redirect.php?page=contact">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-outline-primary me-2">Dashboard</a>
                        <a href="logout.php" class="btn btn-primary">Logout</a>
                    <?php else: ?>
                        <a href="redirect.php?page=login" class="btn btn-outline-primary me-2">Login</a>
                        <a href="redirect.php?page=register" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section - Redesigned -->
    <section class="hero-section" style="margin-top: 80px;">
        <div class="container-fluid px-0">
            <div class="hero-gradient py-5 position-relative overflow-hidden">
                <!-- Animated background elements -->
                <div class="position-absolute top-0 start-0 w-100 h-100">
                    <div class="circle-shape position-absolute rounded-circle bg-primary opacity-10" style="width: 300px; height: 300px; top: -100px; right: -100px;"></div>
                    <div class="circle-shape position-absolute rounded-circle bg-success opacity-10" style="width: 400px; height: 400px; bottom: -200px; left: -100px;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 20px 20px;"></div>
                </div>
                
                <div class="container position-relative py-4 py-lg-5">
                    <div class="row align-items-center">
                        <div class="col-lg-7 mb-4 mb-lg-0" data-aos="fade-right">
                            <h1 class="display-4 fw-bold text-white mb-3">Find Your <span class="text-warning">Dream Job</span> in Lspu</h1>
                            <p class="lead text-white-80 mb-4">Browse <?= number_format($totalJobs) ?> open positions at top educational institutions worldwide</p>
                            
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <span class="badge bg-white-10 text-white border border-white-10 px-3 py-2 rounded-pill">
                                    <i class="fas fa-check-circle me-1 text-success"></i> Verified Employers
                                </span>
                                <span class="badge bg-white-10 text-white border border-white-10 px-3 py-2 rounded-pill">
                                    <i class="fas fa-bolt me-1 text-warning"></i> Immediate Hiring
                                </span>
                                <span class="badge bg-white-10 text-white border border-white-10 px-3 py-2 rounded-pill">
                                    <i class="fas fa-globe me-1 text-primary"></i> Remote Opportunities
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-lg-5" data-aos="fade-left" data-aos-delay="200">
                            <div class="svg-container">
                                <svg class="job-search-svg float-animation" viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Background circle -->
                                    <circle cx="300" cy="200" r="150" fill="rgba(255,255,255,0.1)" class="pulse-animation"/>
                                    
                                    <!-- Main document -->
                                    <rect x="200" y="120" width="200" height="160" rx="10" fill="#fff" stroke="#22809d" stroke-width="3"/>
                                    <rect x="220" y="150" width="160" height="10" rx="5" fill="#e9ecef"/>
                                    <rect x="220" y="170" width="120" height="10" rx="5" fill="#e9ecef"/>
                                    <rect x="220" y="190" width="140" height="10" rx="5" fill="#e9ecef"/>
                                    <rect x="220" y="210" width="100" height="10" rx="5" fill="#e9ecef"/>
                                    <rect x="220" y="230" width="160" height="10" rx="5" fill="#e9ecef"/>
                                    
                                    <!-- Magnifying glass -->
                                    <circle cx="270" cy="270" r="30" fill="none" stroke="#22809d" stroke-width="8" stroke-dasharray="60" stroke-dashoffset="60" class="draw" style="animation-delay: 0.5s"/>
                                    <line x1="300" y1="300" x2="340" y2="340" stroke="#22809d" stroke-width="8" stroke-dasharray="60" stroke-dashoffset="60" class="draw" style="animation-delay: 0.8s"/>
                                    
                                    <!-- Checkmark -->
                                    <path d="M400 150 L430 180 L470 140" fill="none" stroke="#4cc9f0" stroke-width="10" stroke-linecap="round" stroke-dasharray="100" stroke-dashoffset="100" class="draw" style="animation-delay: 1.2s"/>
                                    
                                    <!-- Person -->
                                    <circle cx="450" cy="150" r="20" fill="#f8f9fa" stroke="#0E6A87" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="60" class="draw" style="animation-delay: 1.5s"/>
                                    <path d="M450 170 L450 220" stroke="#0E6A87" stroke-width="3" stroke-linecap="round" stroke-dasharray="50" stroke-dashoffset="50" class="draw" style="animation-delay: 1.7s"/>
                                    <path d="M450 180 L420 200" stroke="#0E6A87" stroke-width="3" stroke-linecap="round" stroke-dasharray="30" stroke-dashoffset="30" class="draw" style="animation-delay: 1.9s"/>
                                    <path d="M450 180 L480 200" stroke="#0E6A87" stroke-width="3" stroke-linecap="round" stroke-dasharray="30" stroke-dashoffset="30" class="draw" style="animation-delay: 2.1s"/>
                                    <path d="M450 220 L430 260" stroke="#0E6A87" stroke-width="3" stroke-linecap="round" stroke-dasharray="40" stroke-dashoffset="40" class="draw" style="animation-delay: 2.3s"/>
                                    <path d="M450 220 L470 260" stroke="#0E6A87" stroke-width="3" stroke-linecap="round" stroke-dasharray="40" stroke-dashoffset="40" class="draw" style="animation-delay: 2.5s"/>
                                    
                                    <!-- Document lines (animated) -->
                                    <line x1="220" y1="150" x2="380" y2="150" stroke="#22809d" stroke-width="3" stroke-dasharray="160" stroke-dashoffset="160" class="document-line" style="animation-delay: 0.2s"/>
                                    <line x1="220" y1="170" x2="340" y2="170" stroke="#22809d" stroke-width="3" stroke-dasharray="120" stroke-dashoffset="120" class="document-line" style="animation-delay: 0.4s"/>
                                    <line x1="220" y1="190" x2="360" y2="190" stroke="#22809d" stroke-width="3" stroke-dasharray="140" stroke-dashoffset="140" class="document-line" style="animation-delay: 0.6s"/>
                                    <line x1="220" y1="210" x2="320" y2="210" stroke="#22809d" stroke-width="3" stroke-dasharray="100" stroke-dashoffset="100" class="document-line" style="animation-delay: 0.8s"/>
                                    <line x1="220" y1="230" x2="380" y2="230" stroke="#22809d" stroke-width="3" stroke-dasharray="160" stroke-dashoffset="160" class="document-line" style="animation-delay: 1.0s"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Animated scrolling indicator -->
                <div class="scroll-indicator text-center position-absolute start-0 w-100" style="bottom: 20px;" data-aos="fade-up" data-aos-delay="400">
                    <a href="#job-listings" class="text-white text-decoration-none">
                        <div class="d-flex flex-column align-items-center">
                            <span class="mb-1 small">Scroll to Browse</span>
                            <i class="fas fa-chevron-down animate-bounce"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5" id="job-listings">
        <div class="container">
            <div class="row g-4">
                <!-- Filters Column -->
                <div class="col-lg-4">
                    <div class="filter-card p-4 mb-4" data-aos="fade-up">
                        <h3 class="h5 fw-bold mb-4">Search Filters</h3>
                        <form id="filters-form" method="get">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="page" value="1">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Teacher, Professor" value="<?= htmlspecialchars($title) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location_id" class="form-label">Location</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">All Locations</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc['location_id']) ?>" <?= $location_id == $loc['location_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Job Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= htmlspecialchars($t['type']) ?>" <?= $type === $t['type'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['type']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department_id']) ?>" <?= $department_id == $dept['department_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                            
                            <a href="viewalljob.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times me-2"></i> Reset Filters
                            </a>
                        </form>
                    </div>
                </div>
                
                <!-- Jobs Column -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
                        <h2 class="h4 fw-bold mb-0">Available Jobs</h2>
                        <div class="d-flex align-items-center">
                            <span class="me-2">Sort by:</span>
                            <select id="sort-select" class="form-select form-select-sm" style="width: auto;">
                                <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                                <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (count($jobs) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($jobs as $job): ?>
                                <div class="col-12" data-aos="fade-up">
                                    <div class="job-card p-4 h-100">
                                        <div class="d-flex flex-column flex-md-row justify-content-between">
                                            <div class="mb-3 mb-md-0">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h3 class="h5 fw-bold mb-0 me-3"><?= htmlspecialchars($job['title']) ?></h3>
                                                    <span class="job-type-badge <?= $job['type'] === 'Full-Time' ? 'job-type-fulltime' : 'job-type-parttime' ?>">
                                                        <?= htmlspecialchars($job['type']) ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-3 mb-2">
                                                     <span class="text-muted">
                                                        <i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['department']) ?>
                                                    </span>
                                                    <span class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-briefcase me-1"></i> <?= htmlspecialchars($job['category']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-end">
                                                <span class="text-muted small mb-2">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                                                </span>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary view-details" data-job-id="<?= $job['position_id'] ?>">
                                                        <i class="far fa-eye me-1"></i> Details
                                                    </button>
                                                    <button class="btn btn-sm btn-primary apply-btn" data-job-id="<?= $job['position_id'] ?>">
                                                        <i class="far fa-paper-plane me-1"></i> Apply
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-5" data-aos="fade-up">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationLink(1) ?>" aria-label="First">
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationLink(max(1, $currentPage - 1)) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    if ($startPage > 1) {
                                         echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= buildPaginationLink($i) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    
                                    if ($endPage < $totalPages) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationLink(min($totalPages, $currentPage + 1)) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationLink($totalPages) ?>" aria-label="Last">
                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5" data-aos="fade-up">
                            <div class="bg-white p-5 rounded-3 shadow-sm">
                                <i class="fas fa-search fa-3x text-muted mb-4"></i>
                                <h3 class="h4 fw-bold mb-3">No jobs found</h3>
                                <p class="text-muted mb-4">Try adjusting your search or filter to find what you're looking for.</p>
                                <a href="viewalljob.php" class="btn btn-primary px-4">
                                    <i class="fas fa-times me-2"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Details Modal - Updated with landingpoint.php design -->
<div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content job-detail-modal">
            <div class="job-detail-logo">
                <img src="images/lspulogo.png" alt="LSPU Logo" style="width: 100%; height: auto;">
            </div>
                
                <div class="modal-body p-5">
                    <div class="job-detail-header">
                        <h1 class="job-detail-title">WE ARE <span>HIRING</span></h1>
                        <div class="job-detail-badge" id="modal-job-category"></div>
                        <p class="job-detail-subtitle" id="modal-job-campus"></p>
                    </div>
                    
                    
                    <div class="job-detail-grid">
                        <p class="job-detail-item">
                            <span>Position:</span>
                            <span id="modal-job-position">Not specified</span>
                        </p>
                        <p class="job-detail-item">
                            <span>Salary Grade:</span>
                            <span id="modal-job-salary"></span>
                        </p>
                        <p class="job-detail-item">
                            <span>Training:</span>
                            <span id="modal-job-training">Not specified</span>
                        </p>
                        <p class="job-detail-item">
                            <span>Experience:</span>
                            <span id="modal-job-experience">Not specified</span>
                        </p>
                        <p class="job-detail-item">
                            <span>Eligibility:</span>
                            <span id="modal-job-eligibility">Not specified</span>
                        </p>
                        <p class="job-detail-item">
                            <span>Place of Assignment:</span>
                            <span id="modal-job-place"></span>
                        </p>
                    </div>
                    
                    <div class="job-detail-content">
                        <p class="font-bold mb-4" style="font-weight: 700;">How to Apply</p>
                        <p class="mb-2">Interested and qualified applicants should signify their interest in writing. Attach the following documents to the application letter:</p>
                        <ul class="job-detail-list" id="modal-job-requirements">
                            <li>Fully accomplished Personal Data Sheet (PDS) with recent passport-sized picture (CS Form No. 212, Rev. 2018) which can be downloaded at www.csc.gov.ph</li>
                            <li>Performance rating in the last rating period (if applicable)</li>
                            <li>Photocopy of certificate of eligibility/rating/license</li>
                            <li>Photocopy of transcript of records</li>
                        </ul>
                        <p class="mt-4">Qualified applicants are advised to hand in or send through courier/email their application to:</p>
                        <p class="job-detail-contact" id="modal-job-contact-person"></p>
                        <p id="modal-job-contact-position"></p>
                        <p id="modal-job-contact-address"></p>
                        <p><a class="underline hover:text-blue-700" id="modal-job-contact-email" href="#"></a></p>
                    </div>
                    
                    <p class="job-detail-note">Note: Applications with incomplete documents shall not be entertained.</p>
                    
                    <p class="job-detail-footer-text">
                        LSPU - Los Baños Campus adheres to the general existing Equal Employment Opportunity Principle (EEOP), as such, there is no discrimination based on gender identity, sexual orientation, disabilities, religion and/or indigenous group membership in the implementation of Human Resource Merit Promotion and Selection. All interested and qualified applicants are encouraged to apply.
                    </p>
                    
                    <p class="job-detail-footer" id="modal-job-reference"></p>
                </div>
                
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" id="modal-apply-btn">
                        <i class="far fa-paper-plane me-2"></i> Apply Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Required Modal -->
    <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-labelledby="loginRequiredModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="loginRequiredModalLabel">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Please Login to Apply</h4>
                    <p class="text-muted mb-4">You need to be logged in to apply for this job. Please login or register if you don't have an account yet.</p>
                    
                    <div class="d-flex flex-column gap-3">
                        <a href="login.php" class="btn btn-primary py-3 fw-bold" id="login-link">
                            <i class="fas fa-sign-in-alt me-2"></i> Login Now
                        </a>
                        <a href="register.php" class="btn btn-outline-primary py-3 fw-bold" id="register-link">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <p class="text-muted mb-0">You can browse jobs without an account</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-white pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a class="navbar-brand d-flex align-items-center mb-4" href="home.php">
                        <img src="images/lspulogo.png" alt="Logo" class="me-2 logo-img">
                        <span class="fw-bold">LSPU-LBC Online Job Portal</span>
                    </a>
                    <p class="mb-4">Connecting educators with their dream careers at top institutions worldwide.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-sm-6 col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4">For Job Seekers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="viewalljob.php" class="text-white text-decoration-none">Browse Jobs</a></li>
                        <li class="mb-2"><a href="register.php" class="text-white text-decoration-none">Create Account</a></li>
                        <li class="mb-2"><a href="dashboard.php" class="text-white text-decoration-none">Candidate Dashboard</a></li>
                        <li class="mb-2"><a href="about.php" class="text-white text-decoration-none">Job Search Tips</a></li>
                        <li><a href="contact.php" class="text-white text-decoration-none">Help Center</a></li>
                    </ul>
                </div>
                
                <div class="col-sm-6 col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4">For Employers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Post a Job</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Browse Candidates</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Employer Dashboard</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Recruiting Solutions</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Pricing Plans</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-4">
                    <h5 class="fw-bold mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex">
                            <i class="fas fa-map-marker-alt me-3 mt-1"></i>
                            <span>Los Baños Campus, Brgy. Malinta</span>
                        </li>
                        <li class="mb-3 d-flex">
                            <i class="fas fa-phone-alt me-3 mt-1"></i>
                            <span>0912-121-5453</span>
                        </li>
                        <li class="mb-3 d-flex">
                            <i class="fas fa-envelope me-3 mt-1"></i>
                            <span>info@lspu-lbc.edu.ph</span>
                        </li>
                        <li class="d-flex">
                            <i class="fas fa-clock me-3 mt-1"></i>
                            <span>Mon-Fri: 9:00 AM - 6:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 bg-light opacity-10">
            
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?= date('Y') ?> LSPU-LBC Online Job Portal. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-white text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-white text-decoration-none">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    
    <script>
        // Initialize AOS animation
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('.navbar').addClass('scrolled');
            } else {
                $('.navbar').removeClass('scrolled');
            }
        });
        
        // Back to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').addClass('active');
            } else {
                $('.back-to-top').removeClass('active');
            }
        });
        
        $('.back-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, '300');
        });
        
        // Handle sort select change
        $('#sort-select').change(function() {
            $('input[name="sort"]').val($(this).val());
            $('#filters-form').submit();
        });
        
      // Job details modal
// Job details modal
// Job details modal
$(document).on('click', '.view-details', function() {
    const jobId = $(this).data('job-id');
    
    // Show loading state
    $('#modal-job-title').text('Loading...');
    $('#modal-job-category, #modal-job-campus, #modal-job-position, #modal-job-salary').text('');
    $('#modal-job-training, #modal-job-experience, #modal-job-eligibility').text('');
    $('#modal-job-place, #modal-job-contact-person').text('');
    $('#modal-job-contact-position, #modal-job-contact-address, #modal-job-contact-email').text('');
    $('#modal-job-reference').text('');

    // Fetch job details via AJAX
    $.ajax({
        url: 'get_job_details.php',
        method: 'GET',
        data: { id: jobId },
        dataType: 'json',
        success: function(job) {
            // Format the category badge text
            let categoryBadgeText = '';
            if (job.category && job.type) {
                categoryBadgeText = `${job.category} | ${job.type}`;
            } else if (job.category) {
                categoryBadgeText = job.category;
            } else if (job.type) {
                categoryBadgeText = job.type;
            } else {
                categoryBadgeText = 'Not specified';
            }

            // Populate modal with job details
            $('#modal-job-title').text(job.title || 'Not specified');
            $('#modal-job-category').text(categoryBadgeText); // Set the combined category and type
            $('#modal-job-campus').text(job.location || 'LSPU - Los Baños Campus');
            $('#modal-job-position').text(job.title || 'Not specified');
            $('#modal-job-salary').text(job.salary_range || 'Not specified');
            $('#modal-job-place').text(job.place_of_assignment || 'Not specified');
            
            // Rest of your code remains the same...
            $('#modal-job-contact-person').text('MARIO R. BRIONES, EdD');
            $('#modal-job-contact-position').text('University President');
            $('#modal-job-contact-address').text('Brgy. Malinta, Los Baños, Laguna');
            $('#modal-job-contact-email').text('lspulbc.hrmo@lspu.edu.ph')
                .attr('href', 'mailto:lspulbc.hrmo@lspu.edu.ph');
            
            const currentDate = new Date();
            const refNumber = currentDate.getFullYear() + '-LSPU-JOBS-' + 
                             String(currentDate.getMonth() + 1).padStart(2, '0') + 
                             String(currentDate.getDate()).padStart(2, '0');
            $('#modal-job-reference').text(refNumber);
            
            const jobModal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
            jobModal.show();
            
            $.ajax({
                url: 'get_job_requirements.php',
                method: 'GET',
                data: { position_id: jobId },
                dataType: 'json',
                success: function(requirements) {
                    $('#modal-job-training').text(requirements.training || 'Not specified');
                    $('#modal-job-experience').text(requirements.experience || 'Not specified');
                    $('#modal-job-eligibility').text(requirements.eligibility || 'Not specified');
                    
                    if (requirements.requirements_list) {
                        const requirementsList = requirements.requirements_list.split(';')
                            .map(req => `<li>${req.trim()}</li>`).join('');
                        $('#modal-job-requirements').html(requirementsList);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching requirements:', error);
                    $('#modal-job-training').text('Not specified');
                    $('#modal-job-experience').text('Not specified');
                    $('#modal-job-eligibility').text('Not specified');
                }
            });
        },
        error: function(xhr, status, error) {
            $('#modal-job-title').text('Error loading job details');
            console.error('Error fetching job details:', error);
            
            const jobModal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
            jobModal.show();
        }
    });
});
        
        // Apply button click handler
        $(document).on('click', '.apply-btn, #modal-apply-btn', function(e) {
            e.preventDefault();
            const jobId = $(this).data('job-id');
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                // Show login required modal
                const loginModal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
                loginModal.show();
                
                // Update login and register links to include redirect
                $('#login-link').attr('href', 'login.php?redirect=application.php?job_id=' + jobId);
                $('#register-link').attr('href', 'register.php?redirect=application.php?job_id=' + jobId);
            <?php else: ?>
                // Redirect to application page
                window.location.href = 'application.php?job_id=' + jobId;
            <?php endif; ?>
        });
        
        // Make job cards clickable
        $('.job-card').on('click', function(e) {
            // Don't redirect if clicking on a link or button
            if ($(e.target).is('a, button, .apply-btn, .view-details') || $(e.target).closest('a, button').length) {
                return;
            }
            
            // Trigger view details
            $(this).find('.view-details').trigger('click');
        });
    </script>
</body>
</html>
<?php
// Helper function to build pagination links with current filters
function buildPaginationLink($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'viewalljob.php?' . http_build_query($params);
}

// Close database connection
$conn->close();
?>