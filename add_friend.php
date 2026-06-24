<?php
require_once '../includes/config/database.php';
session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$friend_id = (int)$_POST['user_id'];
$user_id = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Check if already requested
$stmt = $db->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);

if ($stmt->rowCount() == 0) {
    $stmt = $db->prepare("INSERT INTO friends (user_id, friend_user_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, $friend_id]);
    echo json_encode(['success' => true, 'message' => 'Friend request sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Friend request already sent']);
}
?>