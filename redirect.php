<?php
session_start();

// Allowed pages mapping
$allowedPages = [
    'about' => 'about.php',
    'viewalljob' => 'viewalljob.php',
    'contact' => 'contact.php',
    'login' => 'login.php',
    'register' => 'register.php'
];

if (isset($_GET['page']) && array_key_exists($_GET['page'], $allowedPages)) {
    // Set session flag for home-click-based access
    $_SESSION['from_home'] = true;

    header('Location: ' . $allowedPages[$_GET['page']]);
    exit;
} else {
    header('Location: home.php');
    exit;
}
