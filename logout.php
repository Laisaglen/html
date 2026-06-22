<?php
// admin/logout.php - Secure logout

session_start();

include "../config/database.php";

// Log the logout
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
    logActivity($_SESSION['admin_id'], $_SESSION['admin_username'], 'logout', 'User logged out');
}

// Clear remember me token
if (isset($_COOKIE['admin_remember'])) {
    $token = $_COOKIE['admin_remember'];
    $stmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    setcookie('admin_remember', '', time() - 3600, '/');
}

// Clear session
$_SESSION = [];
session_destroy();

// Redirect to login
header("Location: login.php?logout=success");
exit();
?>