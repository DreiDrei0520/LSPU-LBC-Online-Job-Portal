<?php
// applicantlogin.php
session_start();
include('db_connection.php');

// initialize the variable so it always exists
$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple empty-check
    if ($email === '' || $password === '') {
        $loginError = 'Please provide both email and password.';
    } else {
        // Fetch user by email
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Set the session and redirect
                $_SESSION['user_id'] = $row['user_id'];
                header('Location: dashboard.php');
                exit;
            }
        }
        $loginError = 'Invalid email or password.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Applicant Login</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <link rel="stylesheet" href="applicantlogin.css">
  <script defer src="applicantlogin.js"></script>
</head>
<body>
  <div class="flex h-screen">
    <div class="w-1/2 bg-gradient-to-b from-blue-500 to-blue-300 flex items-center justify-center">
      <div class="text-white text-3xl font-bold">LOGIN</div>
    </div>
    <div class="w-1/2 bg-white flex flex-col items-center justify-center p-8">
      <img src="lspulogo.png" alt="University Logo" class="mb-4" />
      <h2 class="text-2xl font-semibold text-blue-600 mb-4">LOGIN</h2>

      <?php if ($loginError): ?>
        <div class="text-red-500 mb-4"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form class="w-full" method="POST" action="">
        <div class="mb-4">
          <label for="email" class="block text-zinc-700">Email</label>
          <input type="email" id="email" name="email"
                 class="border border-zinc-300 rounded-lg p-2 w-full"
                 required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" />
        </div>
        <div class="mb-4">
          <label for="password" class="block text-zinc-700">Password</label>
          <input type="password" id="password" name="password"
                 class="border border-zinc-300 rounded-lg p-2 w-full" required />
        </div>
        <a href="forgotpass.php" class="text-blue-600 text-sm mb-4">Forgot Password?</a>
        <button type="submit"
                class="bg-blue-500 text-white rounded-lg p-2 w-full hover:bg-blue-600">
          LOGIN
        </button>
      </form>

      <div class="mt-4">
        <span class="text-zinc-600">Don't have an account?</span>
        <a href="register.php" class="text-blue-600">REGISTER</a>
      </div>
    </div>
  </div>
</body>
</html>
