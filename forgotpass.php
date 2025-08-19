<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appjobsystem";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = '';
$errorMessage = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['email'])) {
        $email = $conn->real_escape_string($_POST['email']);

        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generate 6-digit numeric OTP
            $otp = random_int(100000, 999999);

            // Save OTP and expiry (10 minutes) in DB
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
            $stmt->bind_param("ss", $otp, $email);
            $stmt->execute();

            session_start();
            $_SESSION['reset_email'] = $email;

            $subject = "Your OTP Code for Password Reset";
            $message = "Hello,\n\nYour OTP code for resetting your password is:\n\n" . $otp . "\n\nThis code will expire in 10 minutes.\n\nIf you did not request a password reset, please ignore this email.\n\nThank you,\nLSPU-LBC Online Job Portal";

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'jadesupremo0@gmail.com'; // Your Gmail address
                $mail->Password = 'lfns yegc vqba ywbq'; // Your Gmail app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('jadesupremo0@gmail.com', 'LSPU-LBC Job Portal');
                $mail->addAddress($email);

                $mail->isHTML(false);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();

                // Redirect to resetpassword.php to enter OTP and new password
                header("Location: resetpassword.php");
                exit;
            } catch (Exception $e) {
                $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $errorMessage = "No account found with that email address.";
        }

        $stmt->close();
    } else {
        $errorMessage = "Please enter your email address.";
    }
}

$conn->close();
?>
<?php
session_start();

// Optional: clear it to avoid refresh re-access
unset($_SESSION['from_login']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | LSPU-LBC Online Job Portal</title>
    <meta name="description" content="Reset your password for the job portal account">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login/forgotpass.css">
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
                    <p class="text-blue-100 opacity-90">Enter your email to receive a one-time password (OTP)</p>
                </div>
                
                <div class="hidden md:block mt-8 left-content">
                    <div class="flex space-x-4">
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-shield-alt text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Secure password reset process</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-envelope text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">OTP sent to your registered email</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Forgot Password Form -->
            <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Forgot your password?</h2>
                    <p class="text-gray-600 mt-2">Enter your email to receive a password reset OTP</p>
                </div>
                
                <?php if ($errorMessage): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-600 rounded-lg flex items-start border border-red-200" role="alert">
                    <i class="fas fa-exclamation-circle mt-1 mr-3 text-red-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                <div class="mb-6 p-4 bg-green-50 text-green-600 rounded-lg flex items-start border border-green-200" role="alert">
                    <i class="fas fa-check-circle mt-1 mr-3 text-green-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
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
                    
                    <button type="submit" 
                            class="w-full text-white py-3 px-4 rounded-lg font-medium btn-primary hover:bg-blue-700 flex items-center justify-center transition-all duration-300">
                        <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i> 
                        <span>Send OTP</span>
                    </button>
                </form>
                
                <div class="mt-8 text-center text-sm text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Sign in here</a>
                </div>
                <div class="mt-4 text-center text-sm text-gray-600">
                    Return to 
                    <a href="home.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="assets/js/login/forgotpass.js"></script>
</body>
</html>