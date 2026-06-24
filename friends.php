<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Handle friend request actions
if (isset($_GET['action']) && isset($_GET['friend_id'])) {
    $friend_id = (int)$_GET['friend_id'];
    
    if ($_GET['action'] == 'accept') {
        $stmt = $db->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_user_id = ?");
        $stmt->execute([$friend_id, $user_id]);
        $_SESSION['success'] = "Friend request accepted!";
    } elseif ($_GET['action'] == 'reject') {
        $stmt = $db->prepare("DELETE FROM friends WHERE user_id = ? AND friend_user_id = ?");
        $stmt->execute([$friend_id, $user_id]);
        $_SESSION['success'] = "Friend request rejected.";
    }
    header("Location: friends.php");
    exit();
}

// Get friends
$stmt = $db->prepare("
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
    ORDER BY u.username
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friend requests
$stmt = $db->prepare("
    SELECT u.* FROM users u
    JOIN friends f ON f.user_id = u.user_id
    WHERE f.friend_user_id = ? AND f.status = 'pending'
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="friends-container">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <h2><i class="fas fa-users"></i> Friends</h2>
    
    <!-- Friend Requests -->
    <?php if(count($requests) > 0): ?>
    <div class="post-card">
        <h3>Friend Requests</h3>
        <?php foreach($requests as $request): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee;">
            <div style="display: flex; align-items: center;">
                <img src="../assets/uploads/profiles/<?php echo $request['profile_photo'] ?: 'default.png'; ?>" 
                     style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                <div>
                    <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                    <br>
                    <small><?php echo htmlspecialchars($request['department']); ?></small>
                </div>
            </div>
            <div>
                <a href="friends.php?action=accept&friend_id=<?php echo $request['user_id']; ?>" class="btn-success" style="padding: 5px 15px; text-decoration: none;">
                    <i class="fas fa-check"></i> Accept
                </a>
                <a href="friends.php?action=reject&friend_id=<?php echo $request['user_id']; ?>" class="btn-danger" style="padding: 5px 15px; text-decoration: none;">
                    <i class="fas fa-times"></i> Reject
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Search Users -->
    <div class="post-card">
        <div class="form-group">
            <input type="text" id="search-friends" placeholder="Search for friends..." style="width: 100%;">
        </div>
    </div>
    
    <!-- All Users -->
    <div class="friends-grid" id="friends-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
        <?php foreach($users as $user): ?>
        <div class="friend-card">
            <div class="friend-avatar">
                <img src="../assets/uploads/profiles/<?php echo $user['profile_photo'] ?: 'default.png'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
            <p style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($user['department']); ?></p>
            
            <?php if($user['friendship_status'] == 'friend'): ?>
                <span class="btn-success" style="display: inline-block; padding: 5px 15px; font-size: 12px;">Friends</span>
            <?php else: ?>
                <button class="add-friend-btn btn-primary" data-user-id="<?php echo $user['user_id']; ?>" style="padding: 5px 15px; font-size: 12px;">
                    <i class="fas fa-user-plus"></i> Add Friend
                </button>
            <?php endif; ?>
            
            <a href="view.php?id=<?php echo $user['user_id']; ?>" class="btn-primary" style="display: block; margin-top: 5px; padding: 5px 15px; font-size: 12px; text-decoration: none;">
                <i class="fas fa-eye"></i> View Profile
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>