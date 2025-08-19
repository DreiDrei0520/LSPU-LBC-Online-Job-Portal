<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once('db_connection.php');

// Initialize variables
$loginError = '';
$email = '';

// Check for brute force attempts
require_once('security_functions.php');
checkBruteForce($_SERVER['REMOTE_ADDR']);

// Check for remember me cookie
if (empty($_POST)) {
    checkRememberMe();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $loginError = 'Invalid form submission. Please try again.';
    } else {
        // Get and sanitize input
        $emailInput = trim($_POST['email'] ?? '');
        $email = filter_var($emailInput, FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember-me']);

        // Validate inputs
        if (empty($emailInput)) {
            $loginError = 'Please provide an email address.';
        } elseif (empty($password)) {
            $loginError = 'Please provide a password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loginError = 'Please enter a valid email address.';
            logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
        } else {
            try {
                // Debug: Log the email being attempted
                error_log("Login attempt for email: " . $email);
                
                // Prepare SQL statement to prevent SQL injection
                $stmt = $conn->prepare("SELECT user_id, email, password, role, is_active, first_name, last_name FROM users WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    // Debug: Log user data retrieved
                    error_log("User found: " . print_r($user, true));
                    
                    // Check if account is active
                    if (!$user['is_active']) {
                        $loginError = 'Your account has been deactivated. Please contact support.';
                        logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
                    } else {
                        // Debug: Show the stored hash and input password for debugging
                        error_log("Stored hash: " . $user['password']);
                        error_log("Input password: " . $password);
                        
                        // Verify password
                        if (password_verify($password, $user['password'])) {
                            // Debug: Log successful password verification
                            error_log("Password verified for user: " . $user['email']);
                            
                            // Check if password needs rehash
                            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                                $newHash = password_hash($password, PASSWORD_BCRYPT);
                                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                                $updateStmt->bind_param("si", $newHash, $user['user_id']);
                                $updateStmt->execute();
                                $updateStmt->close();
                            }

                            // Regenerate session ID to prevent session fixation
                            session_regenerate_id(true);
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['first_name'] = $user['first_name'];
                            $_SESSION['last_name'] = $user['last_name'];
                            $_SESSION['logged_in'] = true;
                            $_SESSION['last_activity'] = time();
                            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                            // Debug: Log session data
                            error_log("Session data set: " . print_r($_SESSION, true));

                            // Update last login
                            $updateLoginStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                            $updateLoginStmt->bind_param("i", $user['user_id']);
                            $updateLoginStmt->execute();
                            $updateLoginStmt->close();

                            // Set remember me cookie if selected
                            if ($rememberMe) {
                                setRememberMeCookie($user['user_id'], $conn);
                            }

                            // Clear failed attempts
                            clearFailedAttempts($_SERVER['REMOTE_ADDR'], $email);

                            // Redirect based on role with debug logging
                            switch (strtolower($user['role'])) {
                                case 'superadmin':
                                    error_log("Redirecting superadmin to superadmin_dashboard.php");
                                    $redirect = 'superadmin_dashboard.php';
                                    break;
                                case 'admin':
                                    error_log("Redirecting admin to admin_dashboard.php");
                                    $redirect = 'admin_dashboard.php';
                                    break;
                                case 'applicant':
                                    error_log("Redirecting applicant to dashboard.php");
                                    $redirect = 'dashboard.php';
                                    break;
                                default:
                                    error_log("Redirecting default role to dashboard.php");
                                    $redirect = 'dashboard.php';
                            }
                            
                            header("Location: $redirect");
                            exit;
                        } else {
                            $loginError = 'Invalid email or password.';
                            error_log("Password verification failed for email: " . $email);
                            error_log("Password verification details - Input: '$password', Stored hash: '{$user['password']}'");
                            logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
                        }
                    }
                } else {
                    $loginError = 'Invalid email or password.';
                    error_log("No user found with email: " . $email);
                    logFailedAttempt($_SERVER['REMOTE_ADDR'], $email);
                }
                
                $stmt->close();
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $loginError = 'A system error occurred. Please try again later.';
            }
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Security functions
function checkBruteForce($ip) {
    global $conn;
    
    // Check if this IP has more than 5 failed attempts in last 30 minutes
    $now = time();
    $valid_attempts = $now - (30 * 60);
    $valid_time = date('Y-m-d H:i:s', $valid_attempts);
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ? AND success = 0");
    $stmt->bind_param("ss", $ip, $valid_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    
    if ($row[0] > 5) {
        // Too many failed attempts
        header('HTTP/1.1 429 Too Many Requests');
        die('Too many login attempts. Please try again later.');
    }
}

function logFailedAttempt($ip, $email) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, email, success) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $ip, $email);
    $stmt->execute();
    $stmt->close();
}

function clearFailedAttempts($ip, $email) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, email, success) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $ip, $email);
    $stmt->execute();
    $stmt->close();
}

function checkRememberMe() {
    global $conn;
    
    if (!empty($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        $stmt = $conn->prepare("SELECT u.user_id, u.email, u.role, u.first_name, u.last_name FROM auth_tokens a JOIN users u ON a.user_id = u.user_id WHERE a.token = ? AND a.expires_at > NOW() AND u.is_active = 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Debug: Log remember me success
            error_log("Remember me token valid for user: " . $user['email']);
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            // Update token last used
            $updateStmt = $conn->prepare("UPDATE auth_tokens SET last_used_at = NOW() WHERE token = ?");
            $updateStmt->bind_param("s", $token);
            $updateStmt->execute();
            $updateStmt->close();

            // Redirect based on role
            switch (strtolower($user['role'])) {
                case 'superadmin':
                    $redirect = 'superadmin_dashboard.php';
                    break;
                case 'admin':
                    $redirect = 'admin_dashboard.php';
                    break;
                case 'applicant':
                    $redirect = 'dashboard.php';
                    break;
                default:
                    $redirect = 'dashboard.php';
            }
            header("Location: $redirect");
            exit;
        } else {
            // Invalid token - clear cookie
            error_log("Invalid remember me token: " . $token);
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
}

function setRememberMeCookie($user_id, $conn) {
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 60 * 60 * 24 * 30; // 30 days
    
    // Store token in database
    $tokenStmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $expiry_date = date('Y-m-d H:i:s', $expiry);
    $tokenStmt->bind_param("iss", $user_id, $token, $expiry_date);
    $tokenStmt->execute();
    $tokenStmt->close();
    
    // Set secure cookie
    $secure = isset($_SERVER['HTTPS']);
    setcookie('remember_token', $token, [
        'expires' => $expiry,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Debug: Log remember me cookie set
    error_log("Remember me cookie set for user_id: " . $user_id);
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | LSPU-LBC Online Job Portal</title>
    <meta name="description" content="Login to access your job portal account">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login/login.css">
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
        <h1 class="text-3xl font-bold mb-4">Welcome to LSPU-LBC Job Portal</h1>
        <p class="text-blue-100 opacity-90">Find your dream job at Laguna State Polytechnic University</p>
    </div>
                
                <div class="hidden md:block mt-8 left-content">
                    <div class="flex space-x-4">
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-chalkboard-teacher text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Teaching and non-teaching positions</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                            <i class="fas fa-university text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-sm">Join our academic community</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Login to Your Account</h2>
                    <p class="text-gray-600 mt-2">Access your dashboard and manage your applications</p>
                </div>
                
                <?php if ($loginError): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-600 rounded-lg flex items-start border border-red-200" role="alert">
                    <i class="fas fa-exclamation-circle mt-1 mr-3 text-red-500" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    
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
                        <div class="flex justify-between items-center mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="forgotpass.php" class="text-sm text-blue-600 hover:text-blue-500 hover:underline">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400" aria-hidden="true"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none focus:ring-2 focus:ring-blue-200" 
                                placeholder="••••••••" 
                                required
                                autocomplete="current-password"
                                minlength="8">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full text-white py-3 px-4 rounded-lg font-medium btn-primary hover:bg-blue-700 flex items-center justify-center transition-all duration-300"
                            id="loginButton">
                        <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i> 
                        <span>Sign In</span>
                        <div class="loader" id="loader"></div>
                    </button>
                </form>
                
                <div class="mt-8 text-center text-sm text-gray-600">
                    Don't have an account? 
                    <a href="redirect.php?page=register" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Register now</a>
                </div>
                <div class="mt-4 text-center text-sm text-gray-600">
                    Return to 
                    <a href="home.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for enhanced UX -->
    <script src="assets/js/login/login.js"></script>

    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</body>
</html>