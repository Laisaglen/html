<?php
// admin/reply.php - Send reply via AJAX

session_start();
header('Content-Type: application/json');

include "../config/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$email = $_POST['email'] ?? '';
$reply = trim($_POST['reply'] ?? '');
$admin_id = $_SESSION['admin_id'] ?? 0;

if (empty($email) || empty($reply)) {
    echo json_encode(['success' => false, 'error' => 'Email and reply are required']);
    exit();
}

// Get the latest message from this user
$query = "SELECT id FROM messages WHERE email = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $message_id = $row['id'];
    
    // Update with reply
    $update = $conn->prepare("UPDATE messages SET reply = ?, status = 'replied', replied_at = NOW(), admin_id = ? WHERE id = ?");
    $update->bind_param("sii", $reply, $admin_id, $message_id);
    $update->execute();
    
    // Also create a new message as reply
    $insert = $conn->prepare("INSERT INTO messages (email, name, message, reply, source, status, created_at, admin_id) 
                              VALUES (?, 'Admin', '', ?, 'admin', 'replied', NOW(), ?)");
    $insert->bind_param("ssi", $email, $reply, $admin_id);
    $insert->execute();
    
    // Also update feedback table
    $feedback_query = "SELECT id FROM feedback WHERE email = ? ORDER BY created_at DESC LIMIT 1";
    $feedback_stmt = $conn->prepare($feedback_query);
    $feedback_stmt->bind_param("s", $email);
    $feedback_stmt->execute();
    $feedback_result = $feedback_stmt->get_result();
    
    if ($feedback_row = $feedback_result->fetch_assoc()) {
        $update_feedback = $conn->prepare("UPDATE feedback SET reply = ?, status = 'replied', replied_at = NOW(), admin_id = ? WHERE id = ?");
        $update_feedback->bind_param("sii", $reply, $admin_id, $feedback_row['id']);
        $update_feedback->execute();
    }
    
    // Log activity
    logActivity($admin_id, $_SESSION['admin_username'], 'reply', "Replied to message from $email");
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No message found for this email'
    ]);
}
?>