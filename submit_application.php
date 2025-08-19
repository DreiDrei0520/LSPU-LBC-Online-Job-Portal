<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'appjobsystem';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  die("User not logged in.");
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $position = $_POST['position'] ?? '';
  $firstName = $_POST['first_name'] ?? '';
  $middleName = $_POST['middle_name'] ?? '';
  $lastName = $_POST['last_name'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';

  // Upload directory
  $uploadDir = 'uploads/documents/';
  if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  // List of expected files
  $docFields = [
    'application_letter',
    'personal_data_sheet',
    'transcript_of_records',
    'proof_of_eligibility',
    'other_documents'
  ];

  $uploadedFiles = [];

  foreach ($docFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
      $filename = basename($_FILES[$field]['name']);
      $targetPath = $uploadDir . time() . "_" . $filename;
      if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        $uploadedFiles[$field] = $targetPath;
      } else {
        $uploadedFiles[$field] = null; // Could not upload
      }
    } else {
      $uploadedFiles[$field] = null; // No file uploaded
    }
  }

  // Save to database
  $stmt = $conn->prepare("
    INSERT INTO applications
    (user_id, position_applied, first_name, middle_name, last_name, email, phone,
     application_letter, personal_data_sheet, transcript_of_records, proof_of_eligibility, other_documents, submitted_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $stmt->bind_param("isssssssssss",
    $userId,
    $position,
    $firstName,
    $middleName,
    $lastName,
    $email,
    $phone,
    $uploadedFiles['application_letter'],
    $uploadedFiles['personal_data_sheet'],
    $uploadedFiles['transcript_of_records'],
    $uploadedFiles['proof_of_eligibility'],
    $uploadedFiles['other_documents']
  );

  if ($stmt->execute()) {
    // Success: redirect back with a success flag
    header("Location: application.php?submitted=1");
    exit;
  } else {
    echo "Error: " . $stmt->error;
  }
  $stmt->close();
}

$conn->close();
?>
