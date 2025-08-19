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

// Replace this with the actual user ID stored in the session  
$userId = $_SESSION['user_id'];  

// Fetch user profile information  
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE user_id = ?");  
$stmt->bind_param("i", $userId);  
$stmt->execute();  
$result = $stmt->get_result();  
$user = $result->fetch_assoc();  
$stmt->close();  

// Fetch jobs from database  
$stmt = $conn->prepare("SELECT job_id, job_title, job_description, company_name, job_location FROM jobs");  

if (!$stmt) {  
    die("Prepare failed: " . $conn->error); // Output the error message if prepare fails  
}  

$stmt->execute();  
$result = $stmt->get_result();  
$jobs = $result->fetch_all(MYSQLI_ASSOC);  
$stmt->close();  
?>  

<!DOCTYPE html>  
<html lang="en">  
<head>  
    <meta charset="UTF-8" />  
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
    <title>Job Offers</title>  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>  
    <link rel="stylesheet" href="dashboard.css" />  
    <style>  
        .job-card {  
            border: 1px solid #ddd;  
            border-radius: 5px;  
            padding: 15px;  
            margin-bottom: 15px;  
            background-color: #fff;  
            transition: box-shadow 0.3s;  
        }  

        .job-card:hover {  
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);  
        }  

        .apply-btn {  
            background-color: #007bff;  
            color: white;  
            padding: 10px 15px;  
            border: none;  
            border-radius: 5px;  
            cursor: pointer;  
            text-align: center;  
        }  

        .apply-btn:hover {  
            background-color: #0056b3;  
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
        <a href="job.php">Job Offers</a>  
        <a href="#">Applications</a>  
        <a href="#">Status</a>  
    </div>  
    <div class="nav-icons">  
        <i class="fas fa-bell"></i>  
        <div class="profile-dropdown">  
            <?php  
            // Default profile picture  
            $defaultProfilePic = 'default.jpg'; // Make sure you have a default image available  
            $profilePicFilename = !empty($user['profile_pic']) ? $user['profile_pic'] : $defaultProfilePic;  
            $picPath = 'uploads/profile_pics/' . $profilePicFilename;  

            // Check if file exists and is an image  
            $hasProfilePic = !empty($profilePicFilename) && file_exists($picPath);  
            ?>  

            <?php if ($hasProfilePic): ?>  
                <img  
                    src="<?= htmlspecialchars($picPath) ?>"  
                    alt="Profile Picture"  
                    id="profileIcon"  
                    style="cursor: pointer; width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"  
                />  
            <?php else: ?>  
                <i  
                    id="profileIcon"  
                    class="fas fa-user-circle text-white text-2xl cursor-pointer"  
                ></i>  
            <?php endif; ?>  

            <div id="dropdownMenu" class="dropdown-menu">  
                <a href="settings.php"><i class="fas fa-cog mr-2"></i>Settings</a>  
                <a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>  
            </div>  
        </div>  
    </div>  
</div>  

<div class="container">  
    <h2>Available Job Offers</h2>  
    <?php if ($jobs): ?>  
        <?php foreach ($jobs as $job): ?>  
            <div class="job-card">  
                <h4><?= htmlspecialchars($job['job_title']) ?> at <?= htmlspecialchars($job['company_name']) ?></h4>  
                <p><strong>Location:</strong> <?= htmlspecialchars($job['job_location']) ?></p>  
                <p><?= htmlspecialchars($job['job_description']) ?></p>  
                <button class="apply-btn" onclick="location.href='apply.php?job_id=<?= $job['job_id'] ?>'">Apply Now</button>  
            </div>  
        <?php endforeach; ?>  
    <?php else: ?>  
        <p>No job offers available at this moment.</p>  
    <?php endif; ?>  
</div>  

<script src="dashboard.js"></script>  
<script>  
    // Dropdown functionality for profile menu  
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