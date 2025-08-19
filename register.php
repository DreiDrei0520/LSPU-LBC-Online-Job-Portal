<?php
$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "appjobsystem"; 

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $fname = $_POST['fname'];
  $mname = $_POST['mname'];
  $lname = $_POST['lname'];
  $email = $_POST['email'];
  $month = $_POST['month'];
  $day = $_POST['day'];
  $year = $_POST['year'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  if ($password !== $confirm_password) {
    $error = "Passwords do not match!";
  } else {
    // Check if username or email already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $error = "Account already exists! <a href='login.php' class='text-blue-600 hover:text-blue-500'>Login here</a>";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $birthdate = "$year-$month-$day";

      $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, birthdate, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $fname, $mname, $lname, $email, $birthdate, $username, $hashed_password);

      if ($stmt->execute()) {
        $success = "Registration successful! Redirecting to login page...";
        echo "<script>
          setTimeout(function(){
            window.location.href = 'login.php';
          }, 1000);
        </script>";
      } else {
        $error = "Error: " . $stmt->error;
      }

      $stmt->close();
    }
  }
}
?>
<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | LSPU-LBC Online Job Portal</title>
    <meta name="description" content="Create an account to access the job portal">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login/register.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="auth-container w-full max-w-6xl">
        <div class="flex flex-col md:flex-row">
            <!-- Left Side - Visual Branding with Background Image -->
            <div class="gradient-overlay md:w-1/2 p-12 text-white flex flex-col justify-between relative">
                <!-- Background container -->
                <div class="bg-side-container">
                    <div class="bg-side-image"></div>
                </div>
                
                <!-- Content -->
                <div class="left-content">
                    <img src="images/lspulogo.png" alt="LSPU-LBC Logo" class="h-16 mb-6" loading="lazy">
                    <h1 class="text-3xl font-bold mb-4">Welcome to LSPU-LBC Job Portal</h1>
                    <p class="text-blue-100 opacity-90">Create your account and start your career journey</p>
                </div>
                
                <div class="hidden md:block mt-8 left-content">
                    <div class="flex space-x-4">
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-user-plus text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Create your professional profile</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-bell text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Get job alerts matching your skills</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Create Your Account</h2>
                    <p class="text-gray-600 mt-2">Fill in your details to get started</p>
                </div>
                
                <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg flex items-start border border-green-200" role="alert">
                    <i class="fas fa-check-circle mt-1 mr-3 text-green-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-600 rounded-lg flex items-start border border-red-200" role="alert">
                    <i class="fas fa-exclamation-circle mt-1 mr-3 text-red-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6" id="registerForm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="fname" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400" aria-hidden="true"></i>
                                </div>
                                <input type="text" id="fname" name="fname" 
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                    placeholder="First Name"
                                    required value="<?= isset($fname) ? htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') : '' ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label for="mname" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400" aria-hidden="true"></i>
                                </div>
                                <input type="text" id="mname" name="mname" 
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                    placeholder="Middle Name"
                                    required value="<?= isset($mname) ? htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') : '' ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label for="lname" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400" aria-hidden="true"></i>
                                </div>
                                <input type="text" id="lname" name="lname" 
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                    placeholder="Last Name"
                                    required value="<?= isset($lname) ? htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') : '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="email" id="email" name="email" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="you@example.com"
                                required value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                            <select id="month" name="month" class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                                <option value="">Month</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= (isset($month) && $month == $i) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="day" class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                            <select id="day" name="day" class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                                <option value="">Day</option>
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                    <option value="<?= $i ?>" <?= (isset($day) && $day == $i) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <select id="year" name="year" class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" required>
                                <option value="">Year</option>
                                <?php for ($i = date("Y"); $i >= 1900; $i--): ?>
                                    <option value="<?= $i ?>" <?= (isset($year) && $year == $i) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-at text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="text" id="username" name="username" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="Username"
                                required value="<?= (isset($username) && $username !== 'root') ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : '' ?>"
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400" aria-hidden="true"></i>
                                </div>
                                <input type="password" id="password" name="password" 
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                    placeholder="••••••••" 
                                    required
                                    minlength="8">
                                <button type="button" id="togglePassword1" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400" aria-hidden="true"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                    placeholder="••••••••" 
                                    required
                                    minlength="8">
                                <button type="button" id="togglePassword2" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full text-white py-3 px-4 rounded-lg font-medium btn-primary hover:bg-blue-700 flex items-center justify-center transition-all duration-300"
                            id="registerButton">
                        <i class="fas fa-user-plus mr-2" aria-hidden="true"></i> 
                        <span>Register</span>
                        <div class="loader" id="loader"></div>
                    </button>
                </form>
                
                <div class="mt-8 text-center text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Login here</a>
                </div>
                <div class="mt-4 text-center text-sm text-gray-600">
                    Return to 
                    <a href="home.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for enhanced UX -->
    <script src="assets/js/login/register.js"></script>

    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</body>
</html>