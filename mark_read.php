<?php
// admin/mark_read.php - Mark messages as read

session_start();
header('Content-Type: application/json');

include "../config/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

$query = "UPDATE messages SET status = 'read' WHERE email = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();

$updated = $stmt->affected_rows;

// Also update feedback table
$query2 = "UPDATE feedback SET status = 'read' WHERE email = ? AND status = 'pending'";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("s", $email);
$stmt2->execute();

echo json_encode([
    'success' => true,
    'updated' => $updated
]);
?>