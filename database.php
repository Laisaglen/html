<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'knp_dating');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    session_start();
}

// Error reporting - turn off in production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Timezone
date_default_timezone_set('Africa/Nairobi');

// File upload limits
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_VIDEO_SIZE', 30 * 1024 * 1024); // 30MB
define('CONTENT_LIFETIME', 24 * 3600); // 24 hours in seconds

// Base URL - change this to your domain
define('BASE_URL', 'http://localhost/knp-dating/');
define('BASE_PATH', __DIR__ . '/../');

class Database {
    private static $instance = null;
    private $conn;
    private $error = null;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            $this->error = "Connection failed: " . $e->getMessage();
            die($this->error);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function getError() {
        return $this->error;
    }
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function sanitizeInput($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateExpiryTime() {
    return date('Y-m-d H:i:s', time() + CONTENT_LIFETIME);
}

function formatDate($date) {
    if (empty($date)) return '';
    return date('F j, Y, g:i a', strtotime($date));
}

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function validateFile($file, $type = 'image') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    $maxSize = ($type === 'image') ? MAX_IMAGE_SIZE : MAX_VIDEO_SIZE;
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    if ($type === 'image') {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid image type'];
        }
        
        // Check if it's actually an image
        if (!getimagesize($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Invalid image file'];
        }
    } elseif ($type === 'video') {
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid video type'];
        }
    }
    
    return ['valid' => true];
}

function uploadFile($file, $targetDir, $prefix = '') {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

function getFriendStatus($user_id, $friend_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT status FROM friends 
        WHERE (user_id = ? AND friend_user_id = ?) 
           OR (user_id = ? AND friend_user_id = ?)
    ");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['status'];
    }
    
    return 'none';
}

function getUnreadMessages($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM messages 
        WHERE receiver_id = ? AND is_read = FALSE AND expires_at > NOW()
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function getFriendRequests($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM friends 
        WHERE friend_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function getUserById($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getProfilePhoto($user_id) {
    $user = getUserById($user_id);
    return $user ? $user['profile_photo'] : 'default.png';
}

function displayProfilePhoto($user_id) {
    $photo = getProfilePhoto($user_id);
    return BASE_URL . 'assets/uploads/profiles/' . $photo;
}

function displayCoverPhoto($user_id) {
    $user = getUserById($user_id);
    $photo = $user ? $user['cover_photo'] : 'default-cover.jpg';
    return BASE_URL . 'assets/uploads/covers/' . $photo;
}

function debug_log($message) {
    if (is_array($message) || is_object($message)) {
        error_log(print_r($message, true));
    } else {
        error_log($message);
    }
}
?>