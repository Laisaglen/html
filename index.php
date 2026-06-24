<?php
// Main entry point - redirect to login or dashboard
require_once 'includes/config/database.php';

// If user is logged in, go to dashboard
if (isLoggedIn()) {
    header("Location: pages/index.php");
    exit();
}

// Otherwise go to login
header("Location: pages/login.php");
exit();
?>