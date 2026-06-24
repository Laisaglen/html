<?php
require_once '../includes/config/database.php';
session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$db = Database::getInstance()->getConnection();
$report_data = [];

// Get all users
$stmt = $db->query("SELECT * FROM users");
$report_data['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total users
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$report_data['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total posts
$stmt = $db->query("SELECT COUNT(*) as total FROM posts");
$report_data['total_posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get active users (last 24 hours)
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_active > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$report_data['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Generate report as CSV
$filename = "report_" . date('Y-m-d_H-i-s') . ".csv";
$filepath = "../assets/reports/" . $filename;

if (!file_exists("../assets/reports/")) {
    mkdir("../assets/reports/", 0777, true);
}

$fp = fopen($filepath, 'w');

// Write headers
$headers = ['User ID', 'Username', 'Email', 'Department', 'Gender', 'Joined Date', 'Last Active'];
fputcsv($fp, $headers);

// Write user data
foreach ($report_data['users'] as $user) {
    fputcsv($fp, [
        $user['user_id'],
        $user['username'],
        $user['email'],
        $user['department'],
        $user['gender'],
        $user['created_at'],
        $user['last_active']
    ]);
}

fclose($fp);

// Save report in database
$stmt = $db->prepare("INSERT INTO admin_reports (generated_by, report_data) VALUES (?, ?)");
$stmt->execute([$_SESSION['user_id'], json_encode($report_data)]);

echo json_encode([
    'success' => true, 
    'report_url' => "../assets/reports/" . $filename,
    'summary' => "Total Users: {$report_data['total_users']}, Total Posts: {$report_data['total_posts']}, Active Users: {$report_data['active_users']}"
]);
?>