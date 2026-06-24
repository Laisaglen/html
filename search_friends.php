<?php
require_once '../includes/config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$user_id = $_SESSION['user_id'];

$db = Database::getInstance()->getConnection();

try {
    $query = "
        SELECT u.*, 
               CASE 
                   WHEN f.user_id = ? THEN 'friend'
                   WHEN f2.user_id = ? THEN 'friend'
                   ELSE 'none'
               END as friendship_status
        FROM users u
        LEFT JOIN friends f ON (f.user_id = ? AND f.friend_user_id = u.user_id AND f.status = 'accepted')
        LEFT JOIN friends f2 ON (f2.user_id = u.user_id AND f2.friend_user_id = ? AND f2.status = 'accepted')
        WHERE u.user_id != ?
    ";
    
    if ($search) {
        $query .= " AND (u.username LIKE :search OR u.department LIKE :search)";
    }
    
    $query .= " ORDER BY u.username";
    
    $stmt = $db->prepare($query);
    if ($search) {
        $stmt->execute([
            'user_id1' => $user_id,
            'user_id2' => $user_id,
            'user_id3' => $user_id,
            'user_id4' => $user_id,
            'user_id5' => $user_id,
            'search' => "%$search%"
        ]);
    } else {
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate HTML
    $html = '';
    foreach ($users as $user) {
        $html .= '<div class="friend-card">';
        $html .= '<div class="friend-avatar">';
        $html .= '<img src="../assets/uploads/profiles/' . ($user['profile_photo'] ?: 'default.png') . '" alt="' . htmlspecialchars($user['username']) . '">';
        $html .= '</div>';
        $html .= '<h4>' . htmlspecialchars($user['username']) . '</h4>';
        $html .= '<p style="font-size: 12px; color: #666;">' . htmlspecialchars($user['department']) . '</p>';
        
        if ($user['friendship_status'] == 'friend') {
            $html .= '<span class="btn-success" style="display: inline-block; padding: 5px 15px; font-size: 12px;">Friends</span>';
        } else {
            $html .= '<button class="add-friend-btn btn-primary" data-user-id="' . $user['user_id'] . '" style="padding: 5px 15px; font-size: 12px;">';
            $html .= '<i class="fas fa-user-plus"></i> Add Friend';
            $html .= '</button>';
        }
        
        $html .= '<a href="view.php?id=' . $user['user_id'] . '" class="btn-primary" style="display: block; margin-top: 5px; padding: 5px 15px; font-size: 12px; text-decoration: none;">';
        $html .= '<i class="fas fa-eye"></i> View Profile';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>