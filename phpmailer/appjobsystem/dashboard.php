<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$userDb = 'root';
$pass = '';
$conn = new mysqli($host, $userDb, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
  $stmt = $conn->prepare("SELECT user_id, profile_pic FROM users WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();
}

// Handle individual document uploads
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $uploadDirectory = 'uploads/documents/';

  // Create directory if it doesn't exist
  if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
  }

  function safeFileName($name) {
    return preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($name));
  }

  $messages = [];

  // Upload Resume
  if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
    $resume = $_FILES['resume'];
    $resumePath = $uploadDirectory . safeFileName($resume['name']);
    if (move_uploaded_file($resume['tmp_name'], $resumePath)) {
      $messages[] = "Resume uploaded successfully.";
    } else {
      $messages[] = "Failed to upload Resume.";
    }
  }

  // Upload Cover Letter
  if (isset($_FILES['coverLetter']) && $_FILES['coverLetter']['error'] === UPLOAD_ERR_OK) {
    $coverLetter = $_FILES['coverLetter'];
    $coverLetterPath = $uploadDirectory . safeFileName($coverLetter['name']);
    if (move_uploaded_file($coverLetter['tmp_name'], $coverLetterPath)) {
      $messages[] = "Cover Letter uploaded successfully.";
    } else {
      $messages[] = "Failed to upload Cover Letter.";
    }
  }

  // Upload Transcript
  if (isset($_FILES['transcript']) && $_FILES['transcript']['error'] === UPLOAD_ERR_OK) {
    $transcript = $_FILES['transcript'];
    $transcriptPath = $uploadDirectory . safeFileName($transcript['name']);
    if (move_uploaded_file($transcript['tmp_name'], $transcriptPath)) {
      $messages[] = "Transcript uploaded successfully.";
    } else {
      $messages[] = "Failed to upload Transcript.";
    }
  }

  if (count($messages) > 0) {
    echo "<script>alert('" . implode("\\n", $messages) . "');</script>";
  } else {
    echo "<script>alert('No file was selected.');</script>";
  }
}

// Fetch job positions
function fetchPositions($conn, $type) {
  $positions = [];
  $stmt = $conn->prepare("SELECT * FROM job_positions WHERE position_type = ?");
  $stmt->bind_param("s", $type);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $positions[] = $row;
  }
  $stmt->close();
  return $positions;
}

$teachingPositions = fetchPositions($conn, 'Teaching');
$nonTeachingPositions = fetchPositions($conn, 'Non-Teaching');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Application Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .modal-overlay { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%; }
    .job-list li { margin-bottom: 15px; }
    .apply-btn, .close-btn { margin-top: 5px; padding: 5px 10px; color: #fff; border: none; border-radius: 5px; }
    .apply-btn { background-color: #3490dc; }
    .close-btn { background-color: #e3342f; }
    .profile-dropdown { position: relative; display: inline-block; }
    #profileIcon { cursor: pointer; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .dropdown-menu { display: none; position: absolute; right: 0; top: 30px; background-color: white; border: 1px solid #ddd; border-radius: 5px; width: 150px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 1000; }
    .dropdown-menu a { display: block; padding: 10px; color: #333; text-decoration: none; }
    .dropdown-menu a:hover { background-color: #f0f0f0; }
    .dropdown-menu.show { display: block; }
  </style>
</head>
<body>
<div class="navbar">
  <img src="lspulogo.png" alt="Logo">
  <div class="nav-links">
    <a href="dashboard.php">Home</a>
    <a href="job.php">Job Offers</a>
    <a href="#">Applications</a>
    <a href="#">Status</a>
  </div>
  <div class="nav-icons">
    <i class="fas fa-bell"></i>
    <div class="profile-dropdown">
      <?php
      $defaultProfilePic = 'default.jpg';
      $profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : $defaultProfilePic;
      $picPath = 'uploads/profile_pics/' . $profilePicFilename;
      $hasProfilePic = file_exists($picPath);
      ?>
      <?php if ($hasProfilePic): ?>
        <img src="<?= htmlspecialchars($picPath) ?>" alt="Profile Picture" id="profileIcon" />
      <?php else: ?>
        <i id="profileIcon" class="fas fa-user-circle"></i>
      <?php endif; ?>
      <div id="dropdownMenu" class="dropdown-menu">
        <a href="settings.php"><i class="fas fa-cog mr-2"></i>Settings</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="section">
    <h3>Application Process Flow</h3>
    <div class="process-flow">
      <div class="process-step"><i class="fas fa-user-circle"></i><span>Login</span></div>
      <div class="process-step"><i class="fas fa-briefcase"></i><span>Select Job</span></div>
      <div class="process-step"><i class="fas fa-upload"></i><span>Upload Documents</span></div>
      <div class="process-step"><i class="fas fa-check-circle"></i><span>Confirmation</span></div>
    </div>
  </div>

  <div class="section position-section">
    <div class="position-card">
      <h4><i class="fas fa-chalkboard-teacher"></i> Teaching Positions</h4>
      <p>Browse and apply for available teaching positions in our institution.</p>
      <button onclick="openModal('teachingModal')">View Positions</button>
    </div>
    <div class="position-card">
      <h4><i class="fas fa-users-cog"></i> Non-Teaching Positions</h4>
      <p>Explore administrative and support staff opportunities.</p>
      <button onclick="openModal('nonTeachingModal')">View Positions</button>
    </div>
  </div>

  <div class="section">
    <h3>Application Status Tracker</h3>
    <div class="status-tracker">
      <div class="status-step completed">
        <i class="fas fa-file-alt"></i>
        <span>Document Screening</span>
        <small>Completed on Jan 15, 2025</small>
      </div>
      <div class="status-step">
        <i class="fas fa-pen"></i>
        <span>Written Examination</span>
        <small>Scheduled for Jan 20, 2025</small>
      </div>
      <div class="status-step">
        <i class="fas fa-comments"></i>
        <span>Interview</span>
        <small>Pending</small>
      </div>
    </div>
  </div>

  <div class="section">
    <h3>Upload Documents</h3>
    <form class="upload-form" method="POST" enctype="multipart/form-data">
  <label for="resume">Resume/CV:</label>
  <input type="file" id="resume" name="resume">

  <label for="coverLetter">Cover Letter:</label>
  <input type="file" id="coverLetter" name="coverLetter">

  <label for="transcript">Transcript of Records:</label>
  <input type="file" id="transcript" name="transcript">

  <button type="submit">Upload Documents</button>
</form>

  </div>

  <!-- Modals -->
  <div id="teachingModal" class="modal-overlay">
    <div class="modal-content">
      <h4>Available Teaching Positions</h4>
      <ul class="job-list">
        <?php foreach ($teachingPositions as $position): ?>
          <li>
            <p><strong><?= htmlspecialchars($position['position_title']) ?></strong></p>
            <p><?= htmlspecialchars($position['description']) ?></p>
            <button class="apply-btn">Apply</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <button class="close-btn" onclick="closeModal('teachingModal')">Close</button>
    </div>
  </div>

  <div id="nonTeachingModal" class="modal-overlay">
    <div class="modal-content">
      <h4>Available Non-Teaching Positions</h4>
      <ul class="job-list">
        <?php foreach ($nonTeachingPositions as $position): ?>
          <li>
            <p><strong><?= htmlspecialchars($position['position_title']) ?></strong></p>
            <p><?= htmlspecialchars($position['description']) ?></p>
            <button class="apply-btn">Apply</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <button class="close-btn" onclick="closeModal('nonTeachingModal')">Close</button>
    </div>
  </div>
</div>

<script>
  document.getElementById('profileIcon').addEventListener('click', function() {
    var dropdown = document.getElementById('dropdownMenu');
    dropdown.classList.toggle('show');
  });

  function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }
</script>
</body>
</html>
