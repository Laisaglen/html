<?php
// admin/get_unread_count.php - Get unread message count

session_start();
header('Content-Type: application/json');

include "../config/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['count' => 0]);
    exit();
}

$query = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$count1 = $row['count'] ?? 0;

$query2 = "SELECT COUNT(*) as count FROM messages WHERE status = 'pending'";
$result2 = $conn->query($query2);
$row2 = $result2->fetch_assoc();
$count2 = $row2['count'] ?? 0;

echo json_encode([
    'count' => $count1 + $count2,
    'feedback' => $count1,
    'messages' => $count2
]);
?>