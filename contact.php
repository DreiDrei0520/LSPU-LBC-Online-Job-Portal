<?php
session_start();

// Database connection
$host = '127.0.0.1';
$dbname = 'appjobsystem';
$username = 'root'; // Change to your database username
$password = ''; // Change to your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// Optional: clear the session flag to prevent re-access on refresh
unset($_SESSION['from_home']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Basic validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message)) $errors[] = 'Message is required';
    
    if (empty($errors)) {
        try {
            // Insert message into database
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                                    VALUES (:name, :email, :subject, :message, NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
            
            $_SESSION['message_sent'] = true;
            header('Location: contact.php');
            exit;
        } catch(PDOException $e) {
            $_SESSION['message_error'] = 'Failed to send message. Please try again.';
            error_log("Database error: " . $e->getMessage());
        }
    } else {
        $_SESSION['message_error'] = implode('<br>', $errors);
    }
    
    header('Location: contact.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - LSPU-LBC ONLINE JOB PORTAL</title>
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
    <link rel="stylesheet" href="assets/css/homepage/contact.css">
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
                        <a class="nav-link" href="redirect.php?page=about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="redirect.php?page=contact">Contact</a>
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
    <section class="contact-hero text-white">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">Get in Touch</h1>
                    <p class="lead mb-5">We'd love to hear from you! Whether you have questions about our services or need assistance, our team is here to help.</p>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <img src="https://img.freepik.com/free-vector/flat-design-illustration-customer-support_23-2148887720.jpg" alt="Contact Us" class="img-fluid floating">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Cards -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-card p-4 text-center h-100">
                        <div class="contact-icon mx-auto">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Our Location</h4>
                        <p class="text-muted">Los Baños Campus, Brgy. Malinta, Los Baños, Laguna</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-card p-4 text-center h-100">
                        <div class="contact-icon mx-auto">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Call Us</h4>
                        <p class="text-muted">+63 912 121 5453</p>
                        <p class="text-muted">Mon-Fri: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-card p-4 text-center h-100">
                        <div class="contact-icon mx-auto">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Email Us</h4>
                        <p class="text-muted">info@lspu-lbc.edu.ph</p>
                        <p class="text-muted">support@lspu-lbc.edu.ph</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <div class="pe-lg-5">
                        <h2 class="fw-bold mb-4">Send Us a Message</h2>
                        <p class="text-muted mb-5">Have questions about our services or need assistance? Fill out the form below and we'll get back to you as soon as possible.</p>
                        
                        <?php if (isset($_SESSION['message_sent'])): ?>
                            <div class="alert alert-success mb-4">
                                Thank you for your message! We will get back to you soon.
                            </div>
                                                            <?= htmlspecialchars($_SESSION['message_sent']) ?>
                            </div>
                            <?php unset($_SESSION['message_sent']); ?>
                        <?php elseif (isset($_SESSION['message_error'])): ?>
                            <div class="alert alert-danger mb-4">
                                <?= htmlspecialchars($_SESSION['message_error']) ?>
                            </div>
                            <?php unset($_SESSION['message_error']); ?>
                        <?php endif; ?>
                        
                        <form id="contactForm" method="POST" action="contact.php">
                            <div class="mb-4">
                                <label for="name" class="form-label fw-medium">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="subject" class="form-label fw-medium">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label fw-medium">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" name="send_message" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="fas fa-paper-plane me-2"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="map-container h-100">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3863.258918724065!2d121.2413083153028!3d14.43478378203238!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397e1a1f1b1f1b1%3A0x1b1f1b1f1b1f1b1!2sLos%20Ba%C3%B1os%20Campus%2C%20Brgy.%20Malinta%2C%20Los%20Ba%C3%B1os%2C%20Laguna!5e0!3m2!1sen!2sph!4v1620000000000!5m2!1sen!2sph" 
                                width="100%" height="100%" style="border:0; min-height: 400px;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title fw-bold">Frequently Asked Questions</h2>
                <p class="text-muted">Find answers to common questions about our services</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                            <h3 class="accordion-header" id="headingOne">
                                <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How do I create an account as a job seeker?
                                </button>
                            </h3>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Creating an account is simple! Click on the "Register" button at the top right corner of the page, fill in your details, and verify your email address. Once verified, you can start applying for jobs immediately.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                            <h3 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How can educational institutions post job openings?
                                </button>
                            </h3>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Institutions need to register as employers on our platform. After approval, you can log in to your dashboard and post job openings with all the necessary details. Our team reviews each posting to ensure quality.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                            <h3 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    What types of educational jobs are available on your platform?
                                </button>
                            </h3>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We list a wide range of educational positions including teachers (all subjects and levels), administrators, counselors, librarians, special education professionals, and university faculty positions across various disciplines.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                            <h3 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    How long does it typically take to get a response after applying?
                                </button>
                            </h3>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Response times vary by institution, but most employers respond within 1-2 weeks of application submission. You can track the status of your applications in your dashboard.
                                </div>
                            </div>
                        </div>
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
                    <h2 class="fw-bold mb-3">Ready to find your <span class="text-warning">Dream Job?</span></h2>
                    <p class="lead mb-0">Join thousands of educators who have advanced their careers through our platform.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                            <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="redirect.php?page=register" class="btn btn-light btn-lg px-4 py-3 fw-bold">
                            <i class="fas fa-user-plus me-2"></i> Register Now
                        </a>
                    <?php endif; ?>
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
    <script src="assets/js/homepage/contact.js"></script>
</body>
</html>