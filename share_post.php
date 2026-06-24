<?php
require_once '../includes/config/database.php';
session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Check if already shared
$stmt = $db->prepare("SELECT * FROM shares WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);

if ($stmt->rowCount() == 0) {
    $stmt = $db->prepare("INSERT INTO shares (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$post_id, $user_id]);
}

// Get share count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM shares WHERE post_id = ?");
$stmt->execute([$post_id]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo json_encode(['success' => true, 'shares' => $count]);
?>