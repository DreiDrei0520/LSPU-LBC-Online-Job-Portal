<?php
// Start the session at the top of the page
session_start();

// Check if user_id and job_id are set in session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['job_id'])) {
    die("Error: user_id or job_id is not set in session.");
}

// Retrieve user_id and job_id from session
$user_id = $_SESSION['user_id'];
$job_id = $_SESSION['job_id'];

// Database connection
$host = "localhost"; // Replace with your DB host
$username = "root"; // Replace with your DB username
$password = ""; // Replace with your DB password
$dbname = "appjobsystem"; // Replace with your DB name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    // Add other form fields here as needed

    // Sanitize and validate form inputs as necessary
    $first_name = $conn->real_escape_string($first_name);
    $last_name = $conn->real_escape_string($last_name);
    $email = $conn->real_escape_string($email);
    $phone = $conn->real_escape_string($phone);
    
    // Insert data into database
    $sql = "INSERT INTO job_applications (user_id, job_id, first_name, last_name, email, phone) 
            VALUES ('$user_id', '$job_id', '$first_name', '$last_name', '$email', '$phone')";

    if ($conn->query($sql) === TRUE) {
        echo "Application submitted successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-md mx-auto p-6 bg-white rounded-lg shadow-lg">
        <h1 class="text-xl font-bold mb-4 text-center">Job Application Form</h1>
        
        <form method="POST" action="application.php">
            <!-- Hidden fields for user_id and job_id -->
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">

            <!-- Form fields -->
            <div class="mb-4">
                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                <input type="text" name="first_name" id="first_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>

            <div class="mb-4">
                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" id="last_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" id="phone" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">Submit Application</button>
        </form>
    </div>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Application</title>
  <link rel="stylesheet" href="jobapp.css">
  <style>
    .hidden { display: none; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .tab.active { background-color: #7ce1ea; color: white; border-radius: 5px 5px 0 0; }
    .form-container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ddd; }
    .tabs { display: flex; gap: 10px; margin-bottom: 10px; }
    .tab { padding: 10px 20px; border: 1px solid #ccc; cursor: pointer; background: #eee; }
    .tab.active { background-color: #7ce1ea; color: white; }
    .form-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
    .row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
    input, select, button { padding: 10px; flex: 1; }
    label { font-weight: bold; display: block; margin-top: 10px; }
    .footer { display: flex; justify-content: space-between; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="form-container">
    <h1>Job Application</h1>
    <p class="subtitle">Please complete the form below to apply for a position with us.</p>

    <div class="tabs">
      <button class="tab active" data-tab="personal">Personal Information</button>
      <button class="tab" data-tab="background">Background Information</button>
      <button class="tab" data-tab="additional">Additional Information</button>
    </div>

    <form method="POST" class="form" id="applicationForm">

      <!-- Personal Info -->
      <div id="personal" class="tab-panel active">
        <label>Name</label>
        <div class="row">
          <input type="text" name="first_name" placeholder="First name" required>
          <input type="text" name="middle_name" placeholder="Middle name">
          <input type="text" name="last_name" placeholder="Last name" required>
        </div>

        <label>Birth Date</label>
        <div class="row">
          <select name="birth_month" required>
            <option value="" disabled selected>Month</option>
            <?php for ($month = 1; $month <= 12; $month++): ?>
              <option value="<?= date("F", mktime(0, 0, 0, $month, 1)) ?>"><?= date("F", mktime(0, 0, 0, $month, 1)) ?></option>
            <?php endfor; ?>
          </select>

          <select name="birth_day" required>
            <option value="" disabled selected>Day</option>
            <?php for ($day = 1; $day <= 31; $day++): ?>
              <option value="<?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($day, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>

          <select name="birth_year" required>
            <option value="" disabled selected>Year</option>
            <?php
              $currentYear = date("Y");
              for ($year = 1900; $year <= $currentYear; $year++):
            ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <label>Contact Info</label>
        <div class="row">
          <input type="text" name="phone" placeholder="Phone Number" required>
          <input type="email" name="email" placeholder="Email Address" required>
          <div class="gender">
            <label><input type="radio" name="gender" value="Male" required> Male</label>
            <label><input type="radio" name="gender" value="Female"> Female</label>
          </div>
        </div>

        <label>Your Residential Address</label>
        <div class="row">
          <input type="text" name="current_address" placeholder="Current Address" required>
          <input type="text" name="street_address" placeholder="Street Address">
        </div>
        <div class="row">
          <input type="text" name="zip_code" placeholder="Postal/Zip Code">
          <input type="text" name="city" placeholder="City">
          <input type="text" name="province" placeholder="State/Province">
        </div>
      </div>

      <!-- Background Info -->
      <div id="background" class="tab-panel">
        <label>High School</label>
        <div class="form-grid">
          <input type="text" name="hs_name" placeholder="Name of school">
          <input type="text" name="hs_address" placeholder="School Address">
          <select name="hs_type">
            <option disabled selected>Type of School</option>
            <option>Public</option>
            <option>Private</option>
          </select>
          <input type="text" name="hs_year" placeholder="Year Attended">
        </div>

        <label>College</label>
        <div class="form-grid">
          <input type="text" name="college_name" placeholder="Name of school">
          <input type="text" name="college_address" placeholder="School Address">
          <select name="college_type">
            <option disabled selected>Type of School</option>
            <option>Public</option>
            <option>Private</option>
          </select>
          <input type="text" name="college_year" placeholder="Year Attended">
        </div>
      </div>

      <!-- Additional Info -->
      <div id="additional" class="tab-panel">
        <label>Recent Experience</label>
        <div class="form-grid">
          <input type="text" name="job_title" placeholder="Job Title">
          <input type="text" name="company_name" placeholder="Company Name">
        </div>

        <label>Started</label>
        <div class="form-grid">
          <select name="start_month" required>
            <option disabled selected>Month</option>
            <?php for ($month = 1; $month <= 12; $month++): ?>
              <option value="<?= str_pad($month, 2, '0', STR_PAD_LEFT) ?>"><?= date("F", mktime(0, 0, 0, $month, 1)) ?></option>
            <?php endfor; ?>
          </select>
          <select name="start_year" required>
            <option disabled selected>Year</option>
            <?php
              $currentYear = date("Y");
              for ($year = $currentYear; $year >= 2016; $year--):
            ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <label>Ended</label>
        <div class="form-grid">
          <select name="end_month" required>
            <option disabled selected>Month</option>
            <?php for ($month = 1; $month <= 12; $month++): ?>
              <option value="<?= str_pad($month, 2, '0', STR_PAD_LEFT) ?>"><?= date("F", mktime(0, 0, 0, $month, 1)) ?></option>
            <?php endfor; ?>
          </select>
          <select name="end_year" required>
            <option disabled selected>Year</option>
            <?php
              for ($year = $currentYear; $year >= 2016; $year--):
            ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="footer">
        <a href="login.php" class="back">‹ Back to Login</a>
        <button type="submit">Submit</button>
      </div>
    </form>
  </div>

  <script>
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
      });
    });
  </script>
</body>
</html>
