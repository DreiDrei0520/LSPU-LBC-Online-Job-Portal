<?php  
session_start();  
$host = 'localhost';  
$db = 'appjobsystem';  
$user = 'root';  
$pass = '';  
$conn = new mysqli($host, $user, $pass, $db);  

if ($conn->connect_error) {  
    die("Connection failed: " . $conn->connect_error);  
}  

// Ensure user is logged in  
if (!isset($_SESSION['user_id'])) {  
    header("Location: login.php");  
    exit;  
}  

$userId = $_SESSION['user_id'];  

// Get user data  
$result = $conn->query("SELECT * FROM users WHERE user_id = $userId");  
if ($result === false) {  
    die("Error: " . $conn->error);  
}  

$userData = $result->fetch_assoc();  
if (!$userData) {  
    die("User not found.");  
}  

// Handle update  
if ($_SERVER['REQUEST_METHOD'] == 'POST') {  
    $name = $_POST['name'];  
    $email = $_POST['email'];  
    $newPassword = $_POST['password'];  

    $profilePic = $userData['profile_pic'];  
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {  
        $fileType = $_FILES['profile_pic']['type'];  
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];  

        if (in_array($fileType, $allowedTypes)) {  
            $uploadDir = 'uploads/profile_pics/';  
            if (!is_dir($uploadDir)) {  
                mkdir($uploadDir, 0777, true);  
            }  
            $newFileName = uniqid() . '-' . basename($_FILES['profile_pic']['name']);  
            $destPath = $uploadDir . $newFileName;  

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destPath)) {  
                $profilePic = $newFileName;  
            }  
        }  
    }  

    if (!empty($newPassword)) {  
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);  
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_pic = ?, password = ? WHERE user_id = ?");  
        $stmt->bind_param("ssssi", $name, $email, $profilePic, $hashedPassword, $userId);  
    } else {  
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_pic = ? WHERE user_id = ?");  
        $stmt->bind_param("sssi", $name, $email, $profilePic, $userId);  
    }  

    if ($stmt->execute()) {  
        echo "<script>alert('Profile updated successfully!'); window.location.href='settings.php';</script>";  
    } else {  
        echo "<script>alert('Failed to update profile.');</script>";  
    }  

    $stmt->close();  
}  
$conn->close();  
?>  

<!DOCTYPE html>  
<html lang="en">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
  <title>Settings</title>  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />  
  <link rel="stylesheet" href="dashboard.css" />  
  <script src="https://cdn.tailwindcss.com"></script>  
  <style>  
    .dropdown-content {  
      display: none;  
      position: absolute;  
      background-color: white;  
      min-width: 160px;  
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);  
      z-index: 1;  
    }  

    .profile-dropdown:hover .dropdown-content {  
      display: block;  
    }  
  </style>  
</head>  
<body class="bg-gray-100">  

  <!-- Navbar -->  
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
        $profilePicFilename = !empty($userData['profile_pic']) ? $userData['profile_pic'] : $defaultProfilePic;  
        $picPath = 'uploads/profile_pics/' . $profilePicFilename;  
        ?>  

        <img src="<?= htmlspecialchars($picPath) ?>" alt="Profile Picture" id="profileIcon" class="rounded-full cursor-pointer" style="width: 40px; height: 40px;">  

        <div class="dropdown-content" id="dropdownMenu">  
          <a href="settings.php"><i class="fas fa-cog mr-2"></i> Settings</a>  
          <a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>  
        </div>  
      </div>  
    </div>  
  </div>  

  <!-- Edit Profile Section -->  
  <div class="max-w-xl mx-auto bg-white rounded-xl shadow-md p-8 mt-10">  
    <h2 class="text-2xl font-bold mb-6">Edit Profile</h2>  

    <div class="flex items-center space-x-4 mb-6">  
      <img src="uploads/profile_pics/<?= htmlspecialchars($userData['profile_pic'] ?? 'default.jpg') ?>" alt="Profile Pic"  
           class="w-20 h-20 rounded-full object-cover shadow">  
      <div>  
        <h3 class="text-lg font-semibold"><?= htmlspecialchars($userData['name']) ?></h3>  
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($userData['email']) ?></p>  
      </div>  
    </div>  

    <form method="POST" enctype="multipart/form-data" class="space-y-5">  
      <div>  
        <label class="block text-gray-700 font-medium mb-1">Full Name</label>  
        <input type="text" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required  
               class="w-full px-4 py-2 rounded-md bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">  
      </div>  

      <div>  
        <label class="block text-gray-700 font-medium mb-1">Email</label>  
        <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required  
               class="w-full px-4 py-2 rounded-md bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">  
      </div>  

      <div>  
        <label class="block text-gray-700 font-medium mb-1">Profile Picture</label>  
        <input type="file" name="profile_pic"  
               class="w-full text-sm bg-gray-50 rounded-md p-2 border border-gray-300">  
      </div>  

      <div>  
        <label class="block text-gray-700 font-medium mb-1">New Password <span class="text-sm text-gray-400">(Leave blank to keep current)</span></label>  
        <input type="password" name="password"  
               class="w-full px-4 py-2 rounded-md bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">  
      </div>  

      <button type="submit"  
              class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 transition duration-200">  
        Save Changes  
      </button>  
    </form>  
  </div>  

  <script>  
    const profileIcon = document.getElementById('profileIcon');  
    const dropdownMenu = document.getElementById('dropdownMenu');  

    // Toggle the dropdown menu when the profile icon is clicked  
    profileIcon.addEventListener('click', (event) => {  
      event.stopPropagation(); // Prevent the click event from bubbling up  
      dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';  
    });  

    // Hide the dropdown when clicking outside of it  
    window.addEventListener('click', function(e) {  
      if (!profileIcon.contains(e.target) && !dropdownMenu.contains(e.target)) {  
        dropdownMenu.style.display = 'none';  
      }  
    });  
  </script>  
</body>  
</html>  
