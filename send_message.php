<?php
require_once '../includes/config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
$user_id = $_SESSION['user_id'];

if (!$receiver_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid receiver']);
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    $media_type = 'text';
    $media_path = '';
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file = $_FILES['file'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (strpos($file_type, 'image/') === 0 && $file['size'] <= MAX_IMAGE_SIZE) {
            $media_type = 'image';
            $target_dir = "../assets/uploads/posts/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $media_path = time() . '_' . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $target_dir . $media_path);
        } elseif (strpos($file_type, 'video/') === 0 && $file['size'] <= MAX_VIDEO_SIZE) {
            $media_type = 'video';
            $target_dir = "../assets/uploads/posts/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $media_path = time() . '_' . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $target_dir . $media_path);
        }
    }
    
    $expires_at = generateExpiryTime();
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, media_type, media_path, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $receiver_id, $message, $media_type, $media_path, $expires_at]);
    
    echo json_encode(['success' => true, 'message' => 'Message sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>