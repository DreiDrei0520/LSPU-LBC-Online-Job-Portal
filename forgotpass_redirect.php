<?php
session_start();

$_SESSION['from_login'] = true; // mark that user came from login page
header('Location: forgotpass.php');
exit;
