<?php
// forgot_password.php
$resetMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $resetMsg = 'Please enter your email address.';
    } else {
        // TODO: Send reset link via email (simulate here)
        $resetMsg = "If this email exists, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <link rel="stylesheet" href="applicantlogin.css">
</head>
<body>
  <div class="flex h-screen">
    <div class="w-1/2 bg-gradient-to-b from-blue-500 to-blue-300 flex items-center justify-center">
      <div class="text-white text-3xl font-bold">RESET</div>
    </div>
    <div class="w-1/2 bg-white flex flex-col items-center justify-center p-8">
      <img src="lspulogo.png" alt="University Logo" class="mb-4" />
      <h2 class="text-2xl font-semibold text-blue-600 mb-4">Forgot Password</h2>

      <?php if ($resetMsg): ?>
        <div class="text-blue-600 mb-4"><?= htmlspecialchars($resetMsg) ?></div>
      <?php endif; ?>

      <form class="w-full" method="POST" action="">
        <div class="mb-4">
          <label for="email" class="block text-zinc-700">Enter your email</label>
          <input type="email" id="email" name="email"
                 class="border border-zinc-300 rounded-lg p-2 w-full"
                 required />
        </div>
        <button type="submit"
                class="bg-blue-500 text-white rounded-lg p-2 w-full hover:bg-blue-600">
          Send Reset Link
        </button>
      </form>

      <div class="mt-4">
        <a href="applicantlogin.php" class="text-blue-600">Back to Login</a>
      </div>
    </div>
  </div>
</body>
</html>
