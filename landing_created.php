<?php
session_start();
require 'db.php';



$landingUrl = $_GET['url'];
$fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/landing_pages/$landingUrl";

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
  <title>Landing Page Created | Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Same CSS as your admin_dashboard.php */
    
    .success-container {
      background: var(--white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      padding: 2rem;
      text-align: center;
      margin-top: 2rem;
    }
    
    .success-icon {
      font-size: 4rem;
      color: var(--success);
      margin-bottom: 1.5rem;
    }
    
    .success-message {
      font-size: 1.25rem;
      margin-bottom: 2rem;
    }
    
    .url-container {
      background: var(--light);
      border-radius: var(--radius-sm);
      padding: 1rem;
      margin: 2rem 0;
      word-break: break-all;
    }
    
    .copy-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.2s ease;
      margin-top: 1rem;
    }
    
    .copy-btn:hover {
      background: var(--secondary);
    }
    
    .copy-btn.copied {
      background: var(--success);
    }
    
    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 2rem;
    }
    
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-sm);
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-block;
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
  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar - Same as your existing sidebar -->
    
    <!-- Main Content -->
    <main class="main-content">
      <!-- Header - Same as your existing header -->
      
      <div class="success-container">
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Landing Page Created Successfully!</h2>
        <p class="success-message">Your job posting now has a public landing page that you can share with applicants.</p>
        
        <div class="url-container">
          <p>Landing Page URL:</p>
          <p id="landing-url"><?= htmlspecialchars($fullUrl) ?></p>
        </div>
        
        <button id="copy-btn" class="copy-btn" onclick="copyToClipboard()">
          <i class="fas fa-copy"></i> Copy Link
        </button>
        
        <div class="action-buttons">
          <a href="<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="btn btn-primary">
            <i class="fas fa-external-link-alt"></i> View Landing Page
          </a>
          <a href="joblistings.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Job Listings
          </a>
        </div>
      </div>
    </main>
  </div>

  <script>
    function copyToClipboard() {
      const url = document.getElementById('landing-url').innerText;
      navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
          btn.innerHTML = '<i class="fas fa-copy"></i> Copy Link';
          btn.classList.remove('copied');
        }, 2000);
      });
    }
  </script>
</body>
</html>