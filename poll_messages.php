<?php
require_once '../includes/config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$receiver_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid receiver']);
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    // Check for new messages
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE sender_id = ? AND receiver_id = ? AND message_id > ? AND expires_at > NOW()
    ");
    $stmt->execute([$receiver_id, $user_id, $last_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true, 
        'new_messages' => $count > 0,
        'count' => $count
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>