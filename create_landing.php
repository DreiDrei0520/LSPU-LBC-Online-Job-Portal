<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get job details
if (isset($_GET['id'])) {
    $jobId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM job_offers WHERE id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();
    
    if (!$job) {
        header('Location: joblistings.php');
        exit;
    }
} else {
    header('Location: joblistings.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate unique landing page URL
    $landingUrl = 'job_' . uniqid() . '.php';
    $landingPath = 'landing_pages/' . $landingUrl;
    
    // Get form data
    $customTitle = $_POST['custom_title'] ?? $job['title'];
    $customDescription = $_POST['custom_description'] ?? $job['description'];
    $applicationDeadline = $_POST['application_deadline'] ?? $job['deadline'];
    $contactEmail = $_POST['contact_email'] ?? '';
    $additionalInfo = $_POST['additional_info'] ?? '';
    
    // Create the landing page file
    $template = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$customTitle} | LSPU Job Opportunity</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #e6f2fc;
      margin: 0;
      padding: 0;
    }
    .poster {
      max-width: 800px;
      background: white;
      margin: 40px auto;
      padding: 40px;
      border-radius: 10px;
      border: 4px solid #003366;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .poster img.logo {
      width: 100px;
      display: block;
      margin: 0 auto 20px;
    }
    h1, h2 {
      text-align: center;
      margin: 0;
    }
    h1 {
      font-weight: 700;
      font-size: 36px;
      color: #003366;
    }
    .highlight {
      background: #ffc107;
      padding: 5px 10px;
      border-radius: 5px;
      display: inline-block;
    }
    h2 {
      color: #003366;
      font-size: 24px;
      margin-top: 10px;
    }
    .details {
      margin: 20px 0;
      font-size: 16px;
      line-height: 1.6;
    }
    .details strong {
      display: inline-block;
      width: 160px;
    }
    .apply-section {
      margin-top: 20px;
    }
    .apply-section ul {
      margin-top: 0;
    }
    .apply-now {
      display: block;
      width: 200px;
      margin: 30px auto 10px;
      padding: 15px;
      text-align: center;
      background-color: #004080;
      color: white;
      font-weight: bold;
      font-size: 18px;
      border-radius: 8px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    .apply-now:hover {
      background-color: #002b5c;
    }
    .footer {
      font-size: 13px;
      margin-top: 30px;
      text-align: center;
      color: #444;
    }
    .deadline {
      color: #d9534f;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="poster">
    <img src="../lspulogo.png" alt="LSPU Logo" class="logo">
    <h1>{$customTitle}</h1>
    <h2>{$job['category']} POSITION<br>LSPU - {$job['location']}</h2>
    
    <div class="details">
      <p><strong>Place of Assignment:</strong> {$job['department']}</p>
      <p><strong>Salary Grade:</strong> {$job['salary_grade']}</p>
      <p><strong>Qualification:</strong> {$job['qualifications']}</p>
      <p><strong>Eligibility:</strong> {$job['eligibility']}</p>
      <p><strong>Application Deadline:</strong> <span class="deadline">{$applicationDeadline}</span></p>
    </div>

    <div class="apply-section">
      <h3>How to Apply</h3>
      <p>{$customDescription}</p>
      {$additionalInfo}
      
      <p>Qualified applicants are advised to hand in or send through courier/email their application not later than <strong>{$applicationDeadline}</strong> to:</p>
      <p>
        <strong>MARIO R. BRIONES, EdD</strong><br>
        University President<br>
        Brgy. Malinta, Los Baños, Laguna<br>
        <a href="mailto:{$contactEmail}">{$contactEmail}</a>
      </p>
    </div>

    <a href="mailto:{$contactEmail}?subject=Application for {$customTitle} Position" class="apply-now">APPLY NOW</a>

    <div class="footer">
      LSPU - {$job['location']} adheres to the general existing Equal Employment Opportunity Principle (EEOP), as such, there is no discrimination based on gender identity, sexual orientation, disabilities, religion and/or indigenous group membership in the implementation of Human Resource Merit Promotion and Selection. All interested and qualified applicants are encouraged to apply.<br>
      <br>
      {$job['job_reference']}
    </div>
  </div>
</body>
</html>
HTML;

    // Save the landing page
    if (!is_dir('landing_pages')) {
        mkdir('landing_pages', 0755, true);
    }
    
    file_put_contents($landingPath, $template);
    
    // Update job record with landing page URL
    $stmt = $conn->prepare("UPDATE job_offers SET landing_page = ? WHERE id = ?");
    $stmt->bind_param("si", $landingUrl, $jobId);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to success page with the URL
    header("Location: landing_created.php?url=" . urlencode($landingUrl));
    exit;
}

// Get admin profile data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id, profile_pic, name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$defaultProfilePic = 'uploads/profile_pics/default.jpg';
$profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
$picsPath = 'uploads/profile_pics/' . $profilePicFilename;
$hasProfilePic = file_exists($picsPath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Landing Page | Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Same CSS as your admin_dashboard.php */
    :root {
      --primary: #4361ee;
      --primary-light: #eef2ff;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --warning: #f8961e;
      --danger: #f72585;
      --dark: #212529;
      --light: #f8f9fa;
      --gray: #6c757d;
      --gray-light: #e9ecef;
      --white: #ffffff;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
      --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
      --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
      --radius-sm: 0.25rem;
      --radius-md: 0.5rem;
      --radius-lg: 1rem;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7fb;
      color: var(--dark);
      line-height: 1.6;
    }
    
    .dashboard {
      display: grid;
      grid-template-columns: 240px 1fr;
      min-height: 100vh;
    }
    
    /* Sidebar - Same as your existing sidebar CSS */
    
    /* Main Content */
    .main-content {
      padding: 2rem;
    }
    
    /* Header - Same as your existing header CSS */
    
    /* Form Styles */
    .form-container {
      background: var(--white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      padding: 2rem;
      margin-top: 2rem;
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--dark);
    }
    
    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="email"],
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--gray-light);
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 1rem;
    }
    
    .form-group textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 2rem;
    }
    
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-sm);
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      font-size: 1rem;
    }
    
    .btn-primary {
      background: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background: var(--secondary);
    }
    
    .btn-secondary {
      background: var(--gray-light);
      color: var(--dark);
    }
    
    .btn-secondary:hover {
      background: #d1d7e0;
    }
    
    .preview-container {
      margin-top: 2rem;
      border: 1px dashed var(--gray-light);
      padding: 1rem;
      border-radius: var(--radius-md);
    }
    
    .preview-title {
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: var(--dark);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .dashboard {
        grid-template-columns: 1fr;
      }
      
      .sidebar {
        display: none;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar - Same as your existing sidebar -->
    
    <!-- Main Content -->
    <main class="main-content">
      <!-- Header - Same as your existing header -->
      
      <!-- Form Content -->
      <div class="form-container">
        <h2>Create Landing Page for: <?= htmlspecialchars($job['title']) ?></h2>
        <p>Customize the landing page that applicants will see when they view this job posting.</p>
        
        <form method="POST" action="">
          <div class="form-group">
            <label for="custom_title">Job Title</label>
            <input type="text" id="custom_title" name="custom_title" value="<?= htmlspecialchars($job['title']) ?>">
          </div>
          
          <div class="form-group">
            <label for="custom_description">Job Description</label>
            <textarea id="custom_description" name="custom_description"><?= htmlspecialchars("Interested and qualified applicants should signify their interest in writing. Attach the following documents to the application letter:\n\n• Fully accomplished Personal Data Sheet with recent passport-sized picture (CSC Form No. 212, Rev. 2017)\n• Comprehensive resume with 2x2 picture\n• Photocopy of certificate of eligibility/rating/license\n• Photocopy of transcript of records") ?></textarea>
          </div>
          
          <div class="form-group">
            <label for="application_deadline">Application Deadline</label>
            <input type="date" id="application_deadline" name="application_deadline" value="<?= htmlspecialchars($job['deadline']) ?>">
          </div>
          
          <div class="form-group">
            <label for="contact_email">Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" value="lspulbc.hrmo@lspu.edu.ph">
          </div>
          
          <div class="form-group">
            <label for="additional_info">Additional Information (HTML allowed)</label>
            <textarea id="additional_info" name="additional_info" placeholder="<p>Add any additional instructions or information here...</p>"></textarea>
          </div>
          
          <div class="form-actions">
            <a href="joblistings.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Generate Landing Page</button>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>