<?php
// api/functions.php - API helper functions

/**
 * Log API activity
 */
function logApiActivity($action, $data = null) {
    $log_dir = __DIR__ . '/logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'api_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $log_entry = "[$timestamp] IP: $ip | Action: $action | UA: $user_agent";
    if ($data) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    $log_entry .= PHP_EOL;
    
    error_log($log_entry, 3, $log_file);
}

/**
 * Validate API request
 */
function validateApiRequest($required_fields = []) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "Missing required field: $field";
        }
    }
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'errors' => $errors
        ];
    }
    
    return ['valid' => true];
}

/**
 * Generate API token
 */
function generateApiToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Verify API token
 */
function verifyApiToken($token) {
    // Implement your token verification logic
    // Example: check against database or environment variable
    $valid_tokens = [
        getenv('API_TOKEN') ?: 'LGK_API_2026'
    ];
    
    return in_array($token, $valid_tokens);
}

/**
 * Send API response
 */
function sendApiResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

/**
 * Handle API error
 */
function sendApiError($message, $code = 400, $details = null) {
    $response = [
        'success' => false,
        'error' => $message,
        'code' => $code,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($details) {
        $response['details'] = $details;
    }
    
    sendApiResponse($response, $code);
}

/**
 * Get request data (JSON or form)
 */
function getRequestData() {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }
    
    return $_POST;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Rate limit API calls
 */
function rateLimit($key, $limit = 60, $window = 60) {
    $cache_dir = __DIR__ . '/cache/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $file = $cache_dir . 'rate_limit_' . md5($key) . '.json';
    $now = time();
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        
        // Clean old entries
        $data['requests'] = array_filter($data['requests'], function($time) use ($now, $window) {
            return $time > ($now - $window);
        });
        
        if (count($data['requests']) >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'remaining' => 0,
                'reset' => $data['requests'][0] + $window
            ];
        }
    } else {
        $data = ['requests' => []];
    }
    
    // Add current request
    $data['requests'][] = $now;
    file_put_contents($file, json_encode($data));
    
    return [
        'allowed' => true,
        'limit' => $limit,
        'remaining' => $limit - count($data['requests']),
        'reset' => $data['requests'][0] + $window
    ];
}
?>