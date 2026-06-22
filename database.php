<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = '-';
$port = 3306;

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("System temporarily unavailable. Please try again later.");
}

// Set charset
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+03:00'");

// Enable strict mode
$conn->query("SET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function getDB() {
    global $conn;
    return $conn;
}

function escape($value) {
    global $conn;
    return mysqli_real_escape_string($conn, $value);
}

function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->get_result()->fetch_assoc();
}

function fetchValue($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_row()) {
        return $row[0];
    }
    return null;
}

function insertData($table, $data) {
    global $conn;
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($data));
    $stmt->bind_param($types, ...array_values($data));
    $stmt->execute();
    return $conn->insert_id;
}

function updateData($table, $data, $where, $whereParams = []) {
    global $conn;
    $set = [];
    $params = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = ?";
        $params[] = $value;
    }
    $params = array_merge($params, $whereParams);
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->affected_rows;
}

function logActivity($admin_id, $username, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $sql = "INSERT INTO admin_logs (admin_id, username, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $admin_id, $username, $action, $details, $ip, $user_agent);
    $stmt->execute();
}
?>