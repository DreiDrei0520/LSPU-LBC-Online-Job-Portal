<?php
include 'db_connection.php'; // Replace with your actual DB connection

$sql = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
mysqli_query($conn, $sql);
?>
