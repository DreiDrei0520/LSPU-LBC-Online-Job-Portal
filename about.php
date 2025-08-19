<?php 
session_start();
include('db_connection.php');

// Optional: clear the session flag to prevent re-access on refresh
unset($_SESSION['from_home']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - LSPU-LBC ONLINE JOB PORTAL</title>
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
    <link rel="stylesheet" href="assets/css/homepage/about.css">
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
                        <a class="nav-link" href="redirect.php?page=viewalljob">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="redirect.php?page=about">About</a>
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
                    <h1 class="display-4 fw-bold mb-4">About <span class="text-warning">LSPU-LBC</span> Online Job Portal</h1>
                    <p class="lead mb-5">Connecting jobs with their dream careers at leading institutions worldwide.</p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <a href="viewalljob.php" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                            <i class="fas fa-briefcase me-2"></i> Browse Jobs
                        </a>
                        <a href="#our-mission" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold">
                            <i class="fas fa-arrow-down me-2"></i> Learn More
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000">
                    <div class="position-relative">
                        <img src="https://img.freepik.com/free-vector/recruitment-agency-searching-job-applicants_1262-19873.jpg" class="img-fluid rounded-4 shadow-lg floating" alt="About Us">
                        <div class="position-absolute top-0 start-0 translate-middle pulse" style="width: 20px; height: 20px; border-radius: 50%;"></div>
                        
                        <div class="position-absolute bottom-0 start-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-users text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">500+ Schools</h6>
                                    <small class="text-muted">Partner institutions</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="position-absolute top-20 end-0 translate-middle bg-white p-3 rounded-3 shadow-sm d-none d-md-block" style="width: 200px;">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-2 rounded-circle me-2">
                                    <i class="fas fa-user-graduate text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">10,000+ Hires</h6>
                                    <small class="text-muted">Successful placements</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">Our Story</h2>
                <p class="text-muted">How we became the leading job platform</p>
            </div>
            
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                    <div class="pe-lg-5">
                        <p class="lead mb-4">Founded in 2025, LSPU-LBC Online Job Portal began as a small initiative to connect local schools campuses with qualified jobs in Los Baños.</p>
                        <p class="text-muted mb-4">What started as a community project quickly grew into a comprehensive platform serving educational institutions across the region. We recognized the unique challenges of hiring in the job sector and built our platform specifically to address those needs.</p>
                        <p class="text-muted">Today, we're proud to be the go-to platform for dream jobs and institutions alike, with thousands of successful placements that have transformed careers and schools.</p>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="position-relative">
                        <img src="images/lspu.jpg" class="img-fluid rounded-4 shadow-lg" alt="Our Story">
                    </div>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="mt-5 pt-5" data-aos="fade-up">
                <div class="timeline">
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                        <h4 class="fw-bold">2025 - Founding</h4>
                        <p class="text-muted">Launched as a local job board for schools in Los Baños</p>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="fw-bold">2026 - Expansion</h4>
                        <p class="text-muted">Expanded to serve the entire other canmpuses</p>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                        <h4 class="fw-bold">2027 - Platform Upgrade</h4>
                        <p class="text-muted">Launched our advanced matching algorithm</p>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                        <h4 class="fw-bold">Present</h4>
                        <p class="text-muted">National platform with thousands of active users</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission Section -->
    <section class="py-5" id="our-mission">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">Our Mission</h2>
                <p class="text-muted">What drives us every day</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="mission-card p-4 h-100">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h4 class="fw-bold mb-3 text-center">Empower Institutions</h4>
                        <p class="text-muted text-center">Help schools and universities find the perfect candidates for their open positions quickly and efficiently.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="mission-card p-4 h-100">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4 class="fw-bold mb-3 text-center">Support Educators</h4>
                        <p class="text-muted text-center">Provide educators with access to the best career opportunities that match their skills and aspirations.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="mission-card p-4 h-100">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4 class="fw-bold mb-3 text-center">Enhance Education</h4>
                        <p class="text-muted text-center">Contribute to better education by ensuring the right people are in the right positions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Ready to find your dream job?</h2>
                    <p class="lead mb-0">Join thousands of jobs who have advanced their careers through our platform.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="redirect.php?page=register" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                        <i class="fas fa-user-plus me-2"></i> Register Now
                    </a>
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
                    <p class="mb-4">Connecting jobs with their dream careers at top institutions worldwide.</p>
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
    <script src="assets/js/homepage/about.js"></script>
</body>
</html>