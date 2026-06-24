<?php
// Additional helper functions

function getFriendsList($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT u.* FROM users u
        JOIN friends f ON (f.user_id = ? AND f.friend_user_id = u.user_id AND f.status = 'accepted')
        UNION
        SELECT u.* FROM users u
        JOIN friends f ON (f.user_id = u.user_id AND f.friend_user_id = ? AND f.status = 'accepted')
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFriendCount($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM friends 
        WHERE (user_id = ? OR friend_user_id = ?) AND status = 'accepted'
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getUnreadMessagesCount($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getPendingFriendRequests($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM friends WHERE friend_user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getActiveUsers() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function formatTimeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "Yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>