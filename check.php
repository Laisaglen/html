<?php
// api/check.php - Check various system statuses (health check, authentication, etc.)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
include_once __DIR__ . '/../config/database.php';

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

// Start session for auth checks
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check database connection
 */
function checkDatabase() {
    global $conn;
    
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Database connection failed'];
    }
    
    if ($conn->connect_error) {
        return ['status' => 'error', 'message' => $conn->connect_error];
    }
    
    // Test query
    $result = mysqli_query($conn, "SELECT 1 as test");
    if (!$result) {
        return ['status' => 'error', 'message' => 'Database query failed'];
    }
    
    $row = mysqli_fetch_assoc($result);
    return [
        'status' => 'connected',
        'message' => 'Database connected successfully',
        'server_info' => $conn->server_info,
        'host_info' => $conn->host_info
    ];
}

/**
 * Check admin authentication status
 */
function checkAuth() {
    $is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    $username = $_SESSION['admin_username'] ?? null;
    $role = $_SESSION['admin_role'] ?? null;
    $admin_id = $_SESSION['admin_id'] ?? null;
    
    return [
        'authenticated' => $is_logged_in,
        'username' => $username,
        'role' => $role,
        'admin_id' => $admin_id,
        'session_active' => session_status() === PHP_SESSION_ACTIVE
    ];
}

/**
 * Get system statistics
 */
function getSystemStats() {
    global $conn;
    
    $stats = [];
    
    // Total feedback
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_feedback'] = intval($row['count']);
    }
    
    // Pending feedback
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['pending_feedback'] = intval($row['count']);
    }
    
    // Total messages
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_messages'] = intval($row['count']);
    }
    
    // Newsletter subscribers
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM newsletter WHERE status = 'active'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['subscribers'] = intval($row['count']);
    }
    
    // Total admins
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM admin_users WHERE status = 'active'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['active_admins'] = intval($row['count']);
    }
    
    return $stats;
}

/**
 * Check email existence in newsletter
 */
function checkSubscriber($email) {
    global $conn;
    
    if (empty($email)) {
        return ['status' => 'error', 'message' => 'Email required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Invalid email format'];
    }
    
    $query = "SELECT id, status, subscribed_at FROM newsletter WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return [
            'status' => 'found',
            'subscribed' => $row['status'] === 'active',
            'status' => $row['status'],
            'subscribed_at' => $row['subscribed_at']
        ];
    }
    
    return ['status' => 'not_found', 'message' => 'Email not found in newsletter'];
}

/**
 * Check server health
 */
function checkServerHealth() {
    // Check PHP version
    $php_version = phpversion();
    $php_version_ok = version_compare($php_version, '7.4.0', '>=');
    
    // Check required extensions
    $extensions = ['mysqli', 'json', 'session', 'mail'];
    $extension_status = [];
    foreach ($extensions as $ext) {
        $extension_status[$ext] = extension_loaded($ext);
    }
    
    // Memory and time
    $memory_limit = ini_get('memory_limit');
    $max_execution_time = ini_get('max_execution_time');
    
    return [
        'php_version' => $php_version,
        'php_version_ok' => $php_version_ok,
        'extensions' => $extension_status,
        'memory_limit' => $memory_limit,
        'max_execution_time' => $max_execution_time,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
}

/**
 * Check email configuration
 */
function checkEmailConfig() {
    // Test email configuration
    $test_email = $_GET['test_email'] ?? null;
    
    if ($test_email && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $subject = "LGK Tech Solutions - Email Test";
        $message = "This is a test email from LGK Tech Solutions.\n\n";
        $message .= "If you received this, your email configuration is working correctly.\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        $headers = "From: test@lgktech.com\r\n";
        
        $sent = mail($test_email, $subject, $message, $headers);
        return [
            'test_sent' => $sent,
            'test_email' => $test_email,
            'message' => $sent ? 'Test email sent successfully' : 'Failed to send test email'
        ];
    }
    
    return ['status' => 'info', 'message' => 'Use ?test_email=email@domain.com to test email'];
}

/**
 * Validate API key (optional security)
 */
function validateApiKey() {
    $api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? null;
    
    // If no API key required, return true
    // For production, set a secret key
    $required_key = getenv('API_KEY') ?: 'LGK_API_2026';
    
    if ($api_key === $required_key) {
        return true;
    }
    
    return false;
}

// ============================================================
// ROUTE HANDLING
// ============================================================

$response = [];

switch ($action) {
    case 'status':
    case 'health':
        $response = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => checkServerHealth(),
            'database' => checkDatabase(),
            'environment' => [
                'app_env' => getenv('APP_ENV') ?: 'development',
                'debug' => getenv('APP_DEBUG') ?: 'false'
            ]
        ];
        break;
        
    case 'auth':
        $response = checkAuth();
        break;
        
    case 'stats':
        // Only return stats if authenticated
        if (!checkAuth()['authenticated']) {
            http_response_code(401);
            $response = [
                'success' => false,
                'message' => 'Authentication required',
                'code' => 401
            ];
            break;
        }
        $response = [
            'success' => true,
            'stats' => getSystemStats()
        ];
        break;
        
    case 'subscriber':
        $email = $_GET['email'] ?? $_POST['email'] ?? null;
        if (!$email) {
            $response = ['status' => 'error', 'message' => 'Email parameter required'];
        } else {
            $response = checkSubscriber($email);
        }
        break;
        
    case 'email-test':
        $response = checkEmailConfig();
        break;
        
    case 'ping':
        $response = [
            'status' => 'pong',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        break;
        
    case 'validate-key':
        $response = [
            'valid' => validateApiKey(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        break;
        
    default:
        // Return general info
        $response = [
            'status' => 'ok',
            'message' => 'LGK Tech Solutions API',
            'version' => '1.0.0',
            'endpoints' => [
                'status' => 'Check system status',
                'auth' => 'Check authentication status',
                'stats' => 'Get system statistics (requires auth)',
                'subscriber' => 'Check newsletter subscriber (email parameter required)',
                'email-test' => 'Test email configuration (test_email parameter required)',
                'ping' => 'Simple ping test',
                'validate-key' => 'Validate API key'
            ],
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => phpversion(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]
        ];
        break;
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);

// Close database connection
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>