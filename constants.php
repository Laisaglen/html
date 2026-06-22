<?php
// ============================================================
// CONFIGURATION CONSTANTS
// ============================================================
// File: config/constants.php
// Description: Application-wide constants and configuration
// ============================================================

// ============================================================
// PREVENT DIRECT ACCESS
// ============================================================
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// ============================================================
// APPLICATION CONSTANTS
// ============================================================

// Application Name
define('APP_NAME', 'LGK Tech Solutions');
define('APP_SHORT_NAME', 'LGK');
define('APP_VERSION', '2.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // development, staging, production

// Application URLs
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/lgk/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('API_URL', SITE_URL . 'api/');
define('ASSETS_URL', SITE_URL . 'assets/');

// File Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// ============================================================
// SITE INFORMATION
// ============================================================

// Company Information
define('SITE_NAME', 'LGK Tech Solutions');
define('SITE_TAGLINE', 'Smart IT Solutions For Modern Businesses');
define('SITE_DESCRIPTION', 'Website Development, Cybersecurity, Cloud Services, Computer Repair, Data Analytics and Enterprise IT Support.');

// Contact Information
define('SITE_PHONE', '0714468889');
define('SITE_EMAIL', 'glenklaisa@gmail.com');
define('SITE_ADDRESS', 'Nairobi, Kenya');
define('SITE_WHATSAPP', '254714468889');
define('SITE_WHATSAPP_LINK', 'https://wa.me/254714468889');

// Social Media Links
define('SOCIAL_FACEBOOK', '#');
define('SOCIAL_TWITTER', '#');
define('SOCIAL_LINKEDIN', '#');
define('SOCIAL_YOUTUBE', '#');
define('SOCIAL_INSTAGRAM', '#');

// ============================================================
// TIME & DATE
// ============================================================

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATE_TIME_FORMAT', 'Y-m-d H:i:s');
define('DATE_DISPLAY_FORMAT', 'F d, Y');
define('DATE_DISPLAY_FORMAT_SHORT', 'M d, Y');
define('TIME_DISPLAY_FORMAT', 'h:i A');

// ============================================================
// SECURITY CONSTANTS
// ============================================================

// Encryption Keys (set in .env for production)
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your-32-character-encryption-key-here');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Session Configuration
define('SESSION_NAME', 'lgk_session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_SECURE', APP_ENV === 'production');

// Password Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_BCRYPT_COST', 12);

// Rate Limiting
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_ATTEMPTS_WINDOW', 900); // 15 minutes

// ============================================================
// FILE UPLOAD CONSTANTS
// ============================================================

// Upload Limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FILE_UPLOADS', 10);

// Allowed File Types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Image Sizes
define('IMAGE_SIZE_THUMBNAIL', [150, 150]);
define('IMAGE_SIZE_MEDIUM', [300, 300]);
define('IMAGE_SIZE_LARGE', [800, 800]);

// ============================================================
// DATABASE CONSTANTS
// ============================================================

// Database Tables
define('TABLE_ADMIN_USERS', 'admin_users');
define('TABLE_FEEDBACK', 'feedback');
define('TABLE_MESSAGES', 'messages');
define('TABLE_NEWSLETTER', 'newsletter');
define('TABLE_SETTINGS', 'settings');
define('TABLE_PASSWORD_RESETS', 'password_resets');
define('TABLE_ADMIN_SESSIONS', 'admin_sessions');
define('TABLE_ADMIN_LOGS', 'admin_logs');
define('TABLE_LOGIN_ATTEMPTS', 'login_attempts');
define('TABLE_NOTIFICATIONS', 'notifications');

// ============================================================
// EMAIL CONSTANTS
// ============================================================

define('EMAIL_FROM_NAME', 'LGK Tech Solutions');
define('EMAIL_FROM_ADDRESS', 'noreply@lgktech.com');
define('EMAIL_REPLY_TO', SITE_EMAIL);
define('EMAIL_ADMIN_ADDRESS', SITE_EMAIL);

// SMTP Configuration (set in .env for production)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // tls, ssl

// ============================================================
// PAGINATION CONSTANTS
// ============================================================

define('ITEMS_PER_PAGE', 20);
define('ITEMS_PER_PAGE_ADMIN', 50);
define('MAX_PAGES', 100);

// ============================================================
// CACHE CONSTANTS
// ============================================================

define('CACHE_ENABLED', APP_ENV === 'production');
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_PATH', ROOT_PATH . 'cache/');

// ============================================================
// LOGGING CONSTANTS
// ============================================================

define('LOG_ENABLED', true);
define('LOG_PATH', ROOT_PATH . 'logs/');
define('LOG_LEVEL', APP_ENV === 'production' ? 'error' : 'debug');

// ============================================================
// API CONSTANTS
// ============================================================

define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 60); // requests per minute
define('API_KEY', getenv('API_KEY') ?: 'LGK_API_2026');

// ============================================================
// CURRENCY & LOCALE
// ============================================================

define('CURRENCY_SYMBOL', 'KES');
define('CURRENCY_CODE', 'KES');
define('LOCALE', 'en_KE');

// ============================================================
// MAINTENANCE MODE
// ============================================================

define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'We are currently performing maintenance. Please check back soon.');

// ============================================================
// COOKIE CONSTANTS
// ============================================================

define('COOKIE_REMEMBER_ME', 'admin_remember');
define('COOKIE_REMEMBER_ME_LIFETIME', 30 * 24 * 60 * 60); // 30 days
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIE_SECURE', APP_ENV === 'production');
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax'); // Lax, Strict, None

// ============================================================
// DEPRECATED - DO NOT USE (Backward Compatibility)
// ============================================================

// These constants are deprecated and will be removed in future versions
// Use the constants above instead

// @deprecated Use APP_NAME instead
define('SITE_NAME_OLD', APP_NAME);

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Get application environment
 */
function getEnvironment() {
    return APP_ENV;
}

/**
 * Check if application is in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Check if application is in development
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Check if application is in staging
 */
function isStaging() {
    return APP_ENV === 'staging';
}

/**
 * Get full URL for a path
 */
function siteUrl($path = '') {
    return SITE_URL . ltrim($path, '/');
}

/**
 * Get full path for a file
 */
function sitePath($path = '') {
    return ROOT_PATH . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function assetUrl($path = '') {
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * Get admin URL
 */
function adminUrl($path = '') {
    return ADMIN_URL . ltrim($path, '/');
}

/**
 * Get API URL
 */
function apiUrl($path = '') {
    return API_URL . ltrim($path, '/');
}

/**
 * Debug function (only in development)
 */
function debug($data, $label = null) {
    if (isDevelopment()) {
        if ($label) {
            echo '<pre><strong>' . htmlspecialchars($label) . '</strong>' . PHP_EOL;
        }
        echo '<pre style="background:#1a1a2e;color:#00ffa6;padding:15px;border-radius:8px;overflow:auto;max-height:500px;font-size:12px;font-family:monospace;">';
        print_r($data);
        echo '</pre>';
    }
}

/**
 * Log function
 */
function logMessage($message, $level = 'info') {
    if (!LOG_ENABLED) return;
    
    $log_file = LOG_PATH . 'app_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date(DATE_TIME_FORMAT);
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    error_log($log_entry, 3, $log_file);
}

// ============================================================
// CREATE REQUIRED DIRECTORIES
// ============================================================

$required_dirs = [
    UPLOAD_PATH,
    CACHE_PATH,
    LOG_PATH
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================================
// ERROR REPORTING (Based on Environment)
// ============================================================

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// ============================================================
// SESSION CONFIGURATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    // Session name
    session_name(SESSION_NAME);
    
    // Session cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => COOKIE_PATH,
        'domain' => COOKIE_DOMAIN,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE
    ]);
    
    // Start session if not already started
    session_start();
}

// ============================================================
// LOAD DATABASE CONFIGURATION
// ============================================================

// Include database configuration
if (file_exists(CONFIG_PATH . 'database.php')) {
    require_once CONFIG_PATH . 'database.php';
}

// ============================================================
// LOAD FUNCTIONS
// ============================================================

// Include helper functions
if (file_exists(INCLUDES_PATH . 'functions.php')) {
    require_once INCLUDES_PATH . 'functions.php';
}

// ============================================================
// DEFAULT SETTINGS FROM DATABASE
// ============================================================

/**
 * Get settings from database
 * Usage: getSetting('site_name', 'Default Value')
 */
function getSetting($key, $default = null) {
    global $conn;
    
    if (!isset($conn) || !$conn) {
        return $default;
    }
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Set settings in database
 */
function setSetting($key, $value) {
    global $conn;
    
    if (!isset($conn) || !$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) 
                            VALUES (?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

// ============================================================
// LOAD DYNAMIC SETTINGS
// ============================================================

// Override constants with database settings
$dynamic_settings = ['site_name', 'site_email', 'site_phone', 'site_address'];

foreach ($dynamic_settings as $setting) {
    $db_value = getSetting($setting);
    if ($db_value !== null) {
        define('DB_' . strtoupper($setting), $db_value);
    }
}

// ============================================================
// CONSTANTS COMPLETE
// ============================================================

// Log successful load
logMessage('Constants loaded successfully', 'debug');

// End of file
?>