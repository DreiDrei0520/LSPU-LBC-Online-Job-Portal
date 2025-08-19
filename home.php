<?php
session_start();
require_once('db_connection.php');

// Fetch job positions from the database
$recentJobs = [];
$sql = "SELECT p.position_id as id, p.title, d.name as department, p.type, p.category, l.name as location, p.date_posted 
        FROM job_positions p
        JOIN departments d ON p.department_id = d.department_id
        JOIN locations l ON p.location_id = l.location_id
        WHERE p.status = 'Open'
        ORDER BY p.date_posted DESC 
        LIMIT 6";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentJobs[] = $row;
    }
}

// Define job categories (you can fetch these from database if needed)
$categories = [
    'Teaching' => ['Primary Education', 'Secondary Education', 'Higher Education'],
    'Non-Teaching' => ['Administration', 'IT', 'Finance', 'Human Resources']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - LSPU-LBC ONLINE JOB PORTAL</title>
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
    <link rel="stylesheet" href="assets/css/homepage/home.css">
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
                        <a class="nav-link active" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="redirect.php?page=viewalljob">Jobs</a>
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

    <!-- Hero Section -->
    <section class="hero-section py-5 text-white">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="display-4 fw-bold mb-4">Find Your <span class="text-warning">Dream Job</span> in Lspu</h1>
                    <p class="lead mb-5">Join thousands of educators and professionals in discovering rewarding career opportunities at leading institutions worldwide.</p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <a href="redirect.php?page=viewalljob" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                            <i class="fas fa-briefcase me-2"></i> Browse All Jobs
                        </a>
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="redirect.php?page=register" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold">
                                <i class="fas fa-user-plus me-2"></i> Register Now
                            </a>
                        <?php endif; ?>
                    </div>
                    
                   
                </div>
                
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000">
                    <div class="position-relative">
                       <img src="https://img.freepik.com/free-vector/recruitment-agency-searching-job-applicants_1262-19873.jpg" class="img-fluid rounded-4 shadow-lg floating" alt="Hero Image">
                        <div class="position-absolute top-0 start-0 translate-middle pulse" style="width: 20px; height: 20px; border-radius: 50%;"></div>
                        
                        <div class="position-absolute bottom-0 start-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-briefcase text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">1,200+ Jobs</h6>
                                    <small class="text-muted">Posted this week</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="position-absolute top-20 end-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-user-graduate text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">500+ Schools</h6>
                                    <small class="text-muted">Partner institutions</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Popular Searches Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">Popular Job Searches</h2>
                <p class="text-muted">Browse our most sought-after positions in the education sector</p>
            </div>
            
           <div class="d-flex flex-wrap justify-content-center gap-3" data-aos="fade-up" data-aos-delay="100">
    <a href="redirect.php?page=viewalljob&title=Professor&category=Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-chalkboard-teacher me-1"></i> Professor
    </a>
    <a href="redirect.php?page=viewalljob&title=Lecturer&category=Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-user-graduate me-1"></i> Lecturer
    </a>
    <a href="redirect.php?page=viewalljob&title=Administrator&category=Non-Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-tasks me-1"></i> Administrator
    </a>
    <a href="redirect.php?page=viewalljob&title=IT+Specialist&category=Non-Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-laptop-code me-1"></i> IT Specialist
    </a>
    <a href="redirect.php?page=viewalljob&title=Finance+Officer&category=Non-Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-coins me-1"></i> Finance Officer
    </a>
    <a href="redirect.php?page=viewalljob&title=Guidance+Counselor&category=Non-Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-hands-helping me-1"></i> Guidance Counselor
    </a>
    <a href="redirect.php?page=viewalljob&title=Librarian&category=Non-Teaching" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 tag-hover">
        <i class="fas fa-book-reader me-1"></i> Librarian
    </a>
</div>

        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">Browse by Category</h2>
                <p class="text-muted">Find jobs that match your expertise and interests</p>
            </div>
            
            <div class="row justify-content-center g-4">
                <?php foreach ($categories as $mainCategory => $subCategories): ?>
                    <div class="col-md-5 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $mainCategory === 'Teaching' ? '100' : '200' ?>">
                        <div class="category-card h-100 p-4">
                            <div class="d-flex align-items-start mb-4">
                                <div class="category-icon-container me-3">
                                    <i class="fas <?= $mainCategory === 'Teaching' ? 'fa-chalkboard-teacher' : 'fa-briefcase' ?> category-icon"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-1"><?= $mainCategory ?></h4>
                                    <p class="text-muted mb-0"><?= count($subCategories) ?> sub-categories</p>
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <?php foreach ($subCategories as $subCategory): ?>
                                    <li class="mb-2">
                                        <a href="viewalljob.php?category=<?= urlencode($mainCategory) ?>&subcategory=<?= urlencode($subCategory) ?>" 
                                           class="text-decoration-none d-flex align-items-center">
                                            <i class="fas fa-angle-right me-2 text-primary"></i>
                                            <span><?= $subCategory ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

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
                        <a href="login.php" class="btn btn-primary py-3 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i> Login Now
                        </a>
                        <a href="register.php" class="btn btn-outline-primary py-3 fw-bold">
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

    <!-- Recent Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-up">
                <div>
                    <h2 class="section-title fw-bold">Recently Posted Jobs</h2>
                    <p class="text-muted mb-0">New career opportunities added daily</p>
                </div>
                <a href="redirect.php?page=viewalljob" class="btn btn-outline-primary">
                    View All Jobs <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($recentJobs)): ?>
                    <?php foreach ($recentJobs as $job): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $job['id'] % 3 * 100 ?>">
                            <div class="job-card p-4 h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="job-type-badge <?= $job['type'] === 'Full-Time' ? 'job-type-fulltime' : 'job-type-parttime' ?>">
                                            <?= $job['type'] ?>
                                        </span>
                                    </div>
                                    <button class="btn btn-sm btn-link text-muted p-0">
                                        <i class="far fa-bookmark"></i>
                                    </button>
                                </div>
                                
                                <h4 class="fw-bold mb-2"><?= htmlspecialchars($job['title']) ?></h4>
                                <p class="text-muted mb-3"><?= htmlspecialchars($job['department']) ?></p>
                                
                                <div class="d-flex align-items-center mb-4">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                    </div>
                                    <span class="text-muted"><?= htmlspecialchars($job['location']) ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">
                                        <i class="far fa-clock me-1"></i> <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                                    </span>
                                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary px-3">
                                        <i class="fas fa-eye me-1"></i> Apply Now!
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5" data-aos="fade-up">
                        <div class="bg-light p-5 rounded-3">
                            <i class="fas fa-briefcase fa-3x text-muted mb-4"></i>
                            <h4 class="fw-bold mb-3">No Jobs Available</h4>
                            <p class="text-muted mb-4">There are currently no open positions. Please check back later.</p>
                            <a href="redirect.php?page=viewalljob" class="btn btn-primary px-4">
                                Browse All Jobs
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5 bg-white" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">How It Works</h2>
                <p class="text-muted">Get your dream education job in just a few simple steps</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="how-it-works-card">
                        <div class="step-number">1</div>
                        <h4 class="fw-bold mb-3">Create Your Profile</h4>
                        <p class="text-muted">Register and build your professional profile to showcase your skills, experience, and qualifications.</p>
                        <div class="mt-4">
                            <i class="fas fa-user-circle fa-3x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="how-it-works-card">
                        <div class="step-number">2</div>
                        <h4 class="fw-bold mb-3">Search & Apply</h4>
                        <p class="text-muted">Browse our extensive job listings and apply for positions that match your expertise and interests.</p>
                        <div class="mt-4">
                            <i class="fas fa-search fa-3x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="how-it-works-card">
                        <div class="step-number">3</div>
                        <h4 class="fw-bold mb-3">Get Hired</h4>
                        <p class="text-muted">Connect with employers, attend interviews, and secure your next career opportunity.</p>
                        <div class="mt-4">
                            <i class="fas fa-handshake fa-3x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <div class="pe-lg-5">
                        <h2 class="fw-bold mb-4">Why Choose Our Platform?</h2>
                        <p class="lead mb-5">We're dedicated to connecting education professionals with their ideal career opportunities through innovative technology and personalized support.</p>
                        
                        <div class="row g-4">
                            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">Targeted Matching</h5>
                                    <p class="text-muted mb-0">Our algorithm matches your skills and preferences with the perfect job opportunities.</p>
                                </div>
                            </div>
                            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">Verified Employers</h5>
                                    <p class="text-muted mb-0">All institutions are thoroughly vetted to ensure legitimate opportunities.</p>
                                </div>
                            </div>
                            <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">Direct Communication</h5>
                                    <p class="text-muted mb-0">Message employers directly through our secure platform.</p>
                                </div>
                            </div>
                            <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">Job Alerts</h5>
                                    <p class="text-muted mb-0">Get notified when new jobs matching your criteria are posted.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="position-relative">
                        <img src="https://img.freepik.com/free-vector/app-development-illustration_52683-47931.jpg" class="img-fluid rounded-4 shadow-lg" alt="Features">
                        
                        <div class="position-absolute top-0 start-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-check-circle text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Verified</h6>
                                    <small class="text-muted">Employer status</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="position-absolute bottom-0 end-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-bolt text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Quick Apply</h6>
                                    <small class="text-muted">1-click applications</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 text-white">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-4">Ready to Find Your <span class="text-warning">Dream Job?</span></h2>
                    <p class="lead mb-5">Join thousands of educators who have advanced their careers through our platform.</p>
                    
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="dashboard.php" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                                <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                            </a>
                        <?php else: ?>
                            <a href="redirect.php?page=register" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                                <i class="fas fa-user-plus me-2"></i> Register Now
                            </a>
                            <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                        <li class="mb-2"><a href="redirect.php?page=viewalljob" class="text-white text-decoration-none">Browse Jobs</a></li>
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

        // Job application login requirement
        $(document).ready(function() {
            $('.job-card .btn-primary').on('click', function(e) {
                <?php if(!isset($_SESSION['user_id'])): ?>
                    e.preventDefault();
                    var jobModal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
                    jobModal.show();
                    
                    // Store the clicked job URL to redirect after login
                    var jobUrl = $(this).attr('href');
                    $('#loginRequiredModal .btn-primary').attr('href', 'login.php?redirect=' + encodeURIComponent(jobUrl));
                    $('#loginRequiredModal .btn-outline-primary').attr('href', 'register.php?redirect=' + encodeURIComponent(jobUrl));
                <?php endif; ?>
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>