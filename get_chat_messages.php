<?php
// admin/get_chat_messages.php - Get chat messages for real-time updates

session_start();
header('Content-Type: application/json');

include "../config/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$email = $_POST['email'] ?? '';
$last_id = intval($_POST['last_id'] ?? 0);

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

// Get messages
$query = "SELECT * FROM messages WHERE email = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
$new_messages = 0;

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'message' => $row['message'],
        'reply' => $row['reply'],
        'source' => $row['source'],
        'created_at' => $row['created_at'],
        'replied_at' => $row['replied_at']
    ];
    
    if ($row['id'] > $last_id) {
        $new_messages++;
    }
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages),
    'new_messages' => $new_messages,
    'last_id' => !empty($messages) ? $messages[count($messages) - 1]['id'] : 0
]);
?>