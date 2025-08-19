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
      $error = "Account already exists! <a href='login.php'>Login here</a>";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $birthdate = "$year-$month-$day";

      $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, birthdate, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $fname, $mname, $lname, $email, $birthdate, $username, $hashed_password);

      if ($stmt->execute()) {
        $success = "Registration successful! Redirecting to login page...";
        echo "<script>
          setTimeout(function(){
            window.location.href = 'applicantlogin.php';
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


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="register.css">
</head>
<body>
  <div class="split-screen">
    <div class="left-panel">
      REGISTER
    </div>
    <div class="right-panel">
      <div class="form-container">
        <h2>Create an Account</h2>
        
        <!-- Display Success or Error Message -->
        <?php if ($success): ?>
          <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
          <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
          <div class="form-row">
            <div class="form-group">
              <label for="fname">First Name</label>
              <input type="text" id="fname" name="fname" placeholder="First Name" required>
            </div>
            <div class="form-group">
              <label for="mname">Middle Name</label>
              <input type="text" id="mname" name="mname" placeholder="Middle Name" required>
            </div>
            <div class="form-group">
              <label for="lname">Last Name</label>
              <input type="text" id="lname" name="lname" placeholder="Last Name" required>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="month">Month</label>
              <select id="month" name="month" required>
                <option value="">Month</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="day">Day</label>
              <select id="day" name="day" required>
                <option value="">Day</option>
                <?php for ($i = 1; $i <= 31; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="year">Year</label>
              <select id="year" name="year" required>
                <option value="">Year</option>
                <?php for ($i = date("Y"); $i >= 1900; $i--): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
          </div>

          <button type="submit">Register</button>
        </form>

        <!-- Login Link if Account Already Exists -->
        <div class="login-link">
          <center><p>Already have an account? <a href="applicantlogin.php">Login here</a></p></center>
        </div>
      </div>
    </div>
  </div>

  <script src="register.js"></script>
</body>
</html>
