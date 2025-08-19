<?php
include 'db_connection.php';
mysqli_query($conn, "INSERT INTO notifications (message) VALUES 
  ('New applicant submitted an application'),
  ('Interview schedule added'),
  ('Application status updated'),
  ('New message from HR team')");
echo "Sample notifications inserted.";
?>
