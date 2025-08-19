<?php 
require 'db_connection.php';

$application_id = $_POST['application_id'] ?? null;

if ($application_id) {
    $uploadDir = 'uploads/documents/';
    $docTypes = ['resume' => 'Resume', 'cover_letter' => 'Cover Letter', 'transcript' => 'Transcript'];

    foreach ($docTypes as $inputName => $docType) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES[$inputName]['tmp_name'];
            $fileName = basename($_FILES[$inputName]['name']);
            $destPath = $uploadDir . $fileName;

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO documents (application_id, doc_type, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $application_id, $docType, $destPath);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error moving file: $fileName";
            }
        }
    }
    echo "Documents uploaded successfully.";
} else {
    echo "Application ID is required.";
}
?>
