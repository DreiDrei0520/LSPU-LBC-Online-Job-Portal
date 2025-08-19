<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appjobsystem";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errorMessage = '';
$successMessage = '';
$email = $_SESSION['reset_email'] ?? '';
$otp = '';
$newPassword = '';
$confirmPassword = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$email || !$otp || !$newPassword || !$confirmPassword) {
        $errorMessage = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        // Check OTP and expiry
        $stmt = $conn->prepare("SELECT reset_token, reset_expires FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $savedOtp = $row['reset_token'];
            $expires = $row['reset_expires'];

            if ($savedOtp === $otp) {
                if (strtotime($expires) >= time()) {
                    // OTP is valid and not expired
                    // Hash new password
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update password and clear reset token & expiry
                    $stmtUpdate = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
                    $stmtUpdate->bind_param("ss", $passwordHash, $email);
                    if ($stmtUpdate->execute()) {
                        $successMessage = "Your password has been reset successfully!";
                        unset($_SESSION['reset_email']);
                    } else {
                        $errorMessage = "Failed to update password. Please try again.";
                    }
                    $stmtUpdate->close();
                } else {
                    $errorMessage = "OTP code has expired. Please request a new password reset.";
                }
            } else {
                $errorMessage = "Invalid OTP code.";
            }
        } else {
            $errorMessage = "Invalid email address.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | LSPU-LBC Online Job Portal</title>
    <meta name="description" content="Reset your password for the job portal account">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login/resetpassword.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="auth-container w-full max-w-6xl">
        <div class="flex flex-col md:flex-row">
            <div class="gradient-overlay md:w-1/2 p-12 text-white flex flex-col justify-between relative">
                <!-- Background container -->
                <div class="bg-side-container">
                    <div class="bg-side-image"></div>
                </div>
                
                <!-- Content -->
                <div class="left-content">
                    <img src="images/lspulogo.png" alt="LSPU-LBC Logo" class="h-16 mb-6" loading="lazy">
                    <h1 class="text-3xl font-bold mb-4">Reset Your Password</h1>
                    <p class="text-blue-100 opacity-90">Enter your OTP and set a new password</p>
                </div>
                
                <div class="hidden md:block mt-8 left-content">
                    <div class="flex space-x-4">
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-shield-alt text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Secure password reset process</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-lock text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Strong password requirements</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Reset Password Form -->
            <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Set New Password</h2>
                    <p class="text-gray-600 mt-2">Enter the OTP sent to your email and create a new password</p>
                </div>
                
                <?php if ($errorMessage): ?>
                <div class="mb-6 p-4 rounded-lg flex items-start alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mt-1 mr-3 text-red-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                <div class="mb-6 p-4 rounded-lg flex items-start alert-success" role="alert">
                    <i class="fas fa-check-circle mt-1 mr-3 text-blue-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!$successMessage): ?>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="email" id="email" name="email" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="you@example.com"
                                required 
                                value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '' ?>"
                                autocomplete="email">
                        </div>
                    </div>
                    
                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">OTP Code</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="text" id="otp" name="otp" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="123456"
                                required value="<?= htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') ?>"
                                maxlength="6"
                                pattern="\d{6}"
                                title="Enter the 6-digit OTP code">
                        </div>
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="password" id="new_password" name="new_password" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="••••••••" 
                                required
                                oninput="checkPasswordStrength(this.value)">
                            <div class="password-strength">
                                <div id="password-strength-fill" class="password-strength-fill"></div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters with at least one number and special character</p>
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
                                required>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full text-white py-3 px-4 rounded-lg font-medium btn-primary hover:bg-blue-700 flex items-center justify-center transition-all duration-300">
                        <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i> 
                        <span>Reset Password</span>
                    </button>
                </form>
                <?php else: ?>
                <div class="mt-8 text-center text-sm text-gray-600">
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Back to Login</a>
                </div>
                <?php endif; ?>
                
                <div class="mt-8 text-center text-sm text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Sign in here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-fill');
            let strength = 0;
            
            // Length check
            if (password.length > 7) strength += 1;
            if (password.length > 11) strength += 1;
            
            // Contains numbers
            if (password.match(/\d/)) strength += 1;
            
            // Contains special chars
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            // Contains both lower and upper case
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            
            // Update strength bar
            const width = strength * 20;
            let color = '#f72585'; // red (danger)
            
            if (strength >= 3) color = '#f8961e'; // amber (warning)
            if (strength >= 4) color = '#22809d'; // primary
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        }
    </script>
    <script src="assets/js/login/resetpassword.js"></script>
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</body>
</html>