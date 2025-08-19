<?php
// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $userId = 1; // Static for now, should come from session

  $resume = $_FILES['resume']['name'];
  $coverLetter = $_FILES['coverLetter']['name'];
  $transcript = $_FILES['transcript']['name'];

  $targetDir = "uploads/";
  if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  move_uploaded_file($_FILES['resume']['tmp_name'], $targetDir . $resume);
  move_uploaded_file($_FILES['coverLetter']['tmp_name'], $targetDir . $coverLetter);
  move_uploaded_file($_FILES['transcript']['tmp_name'], $targetDir . $transcript);

  $stmt = $conn->prepare("INSERT INTO documents (user_id, resume, cover_letter, transcript) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $userId, $resume, $coverLetter, $transcript);

  if ($stmt->execute()) {
    echo "<script>alert('Documents uploaded successfully!');</script>";
  } else {
    echo "<script>alert('Upload failed.');</script>";
  }

  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Application Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="dashboard.css" />
  <style>
    /* Add dropdown menu styling */
    .profile-dropdown {
      position: relative;
      display: inline-block;
    }

    #profileIcon {
      cursor: pointer;
      font-size: 24px;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 30px;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 5px;
      width: 150px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      z-index: 1000;
    }

    .dropdown-menu a {
      display: block;
      padding: 10px;
      color: #333;
      text-decoration: none;
    }

    .dropdown-menu a:hover {
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <img src="lspulogo.png" alt="Logo">
    <div class="nav-links">
      <a href="dashboard.php">Home</a>
      <a href="#">Job</a>
      <a href="#">Applications</a>
      <a href="#">Status</a>
    </div>
    <div class="nav-icons">
      <i class="fas fa-bell"></i>

      <div class="profile-dropdown">
        <i class="fas fa-user-circle" id="profileIcon"></i>
        <div class="dropdown-menu" id="dropdownMenu">
          <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
          <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container">
  <body class="bg-gray-100">

<div class="max-w-3xl mx-auto mt-10 p-8 bg-white shadow-lg rounded-xl">
  <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Profile</h2>
  
  <div class="flex items-center space-x-6 mb-6">
    <img src="uploads/profile_pics/<?= $userData['profile_pic'] ?? 'default.jpg' ?>" alt="Profile Pic" class="w-24 h-24 rounded-full shadow-md object-cover">
    <div>
      <h3 class="text-xl font-semibold"><?= htmlspecialchars($userData['name']) ?></h3>
      <p class="text-gray-500"><?= htmlspecialchars($userData['email']) ?></p>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data" class="space-y-5">
    <div>
      <label class="block text-gray-700 font-medium">Full Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>" class="w-full px-4 py-2 rounded-md bg-gray-100 border focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    </div>

    <div>
      <label class="block text-gray-700 font-medium">Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" class="w-full px-4 py-2 rounded-md bg-gray-100 border focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    </div>

    <div>
      <label class="block text-gray-700 font-medium">Profile Picture</label>
      <input type="file" name="profile_pic" class="w-full mt-1 text-sm bg-gray-50 rounded-md p-2 border border-gray-200">
    </div>

    <div>
      <label class="block text-gray-700 font-medium">New Password <span class="text-sm text-gray-400">(Leave blank to keep current)</span></label>
      <input type="password" name="password" class="w-full px-4 py-2 rounded-md bg-gray-100 border focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 transition duration-200">
      Save Changes
    </button>
  </form>
</div>

  <script src="dashboard.js"></script>
  <script>
    const profileIcon = document.getElementById('profileIcon');
    const dropdownMenu = document.getElementById('dropdownMenu');

    profileIcon.addEventListener('click', () => {
      dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    window.addEventListener('click', function(e) {
      if (!profileIcon.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.style.display = 'none';
      }
    });
  </script>
</body>
</html>
