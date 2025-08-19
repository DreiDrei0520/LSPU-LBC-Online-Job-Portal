<?php
session_start();
$host = 'localhost';
$db   = 'your_database';
$user = 'your_username';
$pass = 'your_password';
$charset = 'utf8mb4';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'] ?? 1; // Replace with your actual session logic

function fetchUploadedDocuments($conn, $userId) {
    $stmt = $conn->prepare("SELECT document_type, file_path FROM uploaded_documents WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        // Overwrite to keep the latest of each document_type
        $documents[$row['document_type']] = $row;
    }
    $stmt->close();
    return $documents;
}

function handleUpload($conn, $userId) {
    if (!isset($_FILES['documents']) || !isset($_POST['document_labels'])) return;

    $documentTypes = $_POST['document_labels'];
    $files = $_FILES['documents'];

    foreach ($documentTypes as $index => $label) {
        if ($files['error'][$index] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$index];
            $originalName = basename($files['name'][$index]);
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $safeName = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $originalName);
            $filePath = $uploadDir . $safeName;

            if (move_uploaded_file($tmpName, $filePath)) {
                // Remove existing document of the same type
                $stmt = $conn->prepare("DELETE FROM uploaded_documents WHERE user_id = ? AND document_type = ?");
                $stmt->bind_param("is", $userId, $label);
                $stmt->execute();
                $stmt->close();

                // Insert new one
                $stmt = $conn->prepare("INSERT INTO uploaded_documents (user_id, document_type, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $userId, $label, $filePath);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleUpload($conn, $userId);
}

$uploadedDocuments = fetchUploadedDocuments($conn, $userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Documents</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .upload-box { margin-bottom: 20px; }
    </style>
</head>
<body>

<h2>Upload Your Documents</h2>

<form method="POST" enctype="multipart/form-data">
    <div class="upload-box">
        <label>Resume: <input type="file" name="documents[]" required></label>
        <input type="hidden" name="document_labels[]" value="Resume">
    </div>

    <div class="upload-box">
        <label>Cover Letter: <input type="file" name="documents[]" required></label>
        <input type="hidden" name="document_labels[]" value="Cover Letter">
    </div>

    <div class="upload-box">
        <label>Certificates: <input type="file" name="documents[]" required></label>
        <input type="hidden" name="document_labels[]" value="Certificates">
    </div>

    <button type="submit">Upload</button>
</form>

<h3>Uploaded Documents</h3>
<ul>
    <?php foreach ($uploadedDocuments as $doc): ?>
        <li>
            <strong><?= htmlspecialchars($doc['document_type']) ?>:</strong>
            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">View</a>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>
