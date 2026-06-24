<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = (int)$_GET['id'] ?? 0;

if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit();
}

// Get friends count
$stmt = $db->prepare("
    SELECT COUNT(*) as friend_count FROM friends 
    WHERE (user_id = ? OR friend_user_id = ?) AND status = 'accepted'
");
$stmt->execute([$user_id, $user_id]);
$friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['friend_count'];

// Check if already friends
$stmt = $db->prepare("
    SELECT status FROM friends 
    WHERE (user_id = ? AND friend_user_id = ?) 
       OR (user_id = ? AND friend_user_id = ?)
");
$stmt->execute([$_SESSION['user_id'], $user_id, $user_id, $_SESSION['user_id']]);
$friendship = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="profile-header">
    <div class="cover-photo">
        <img src="../assets/uploads/covers/<?php echo $user['cover_photo'] ?: 'default-cover.jpg'; ?>" alt="Cover Photo">
        <div class="profile-photo">
            <img src="../assets/uploads/profiles/<?php echo $user['profile_photo'] ?: 'default.png'; ?>" alt="Profile Photo">
        </div>
    </div>
    <div class="profile-info">
        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($user['department']); ?></p>
        <p><i class="fas fa-venus-mars"></i> <?php echo ucfirst($user['gender']); ?></p>
        <p><i class="fas fa-users"></i> <?php echo $friend_count; ?> Friends</p>
        <?php if($user['hobbies']): ?>
            <p><i class="fas fa-heart"></i> Hobbies: <?php echo htmlspecialchars($user['hobbies']); ?></p>
        <?php endif; ?>
        <?php if($user['likes']): ?>
            <p><i class="fas fa-thumbs-up"></i> Likes: <?php echo htmlspecialchars($user['likes']); ?></p>
        <?php endif; ?>
        <?php if($user['dislikes']): ?>
            <p><i class="fas fa-thumbs-down"></i> Dislikes: <?php echo htmlspecialchars($user['dislikes']); ?></p>
        <?php endif; ?>
        <?php if($user['facebook_link']): ?>
            <a href="<?php echo htmlspecialchars($user['facebook_link']); ?>" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
        <?php endif; ?>
        <?php if($user['tiktok_link']): ?>
            <a href="<?php echo htmlspecialchars($user['tiktok_link']); ?>" target="_blank"><i class="fab fa-tiktok"></i> TikTok</a>
        <?php endif; ?>
        
        <!-- Friend actions -->
        <?php if($_SESSION['user_id'] != $user_id): ?>
            <?php if(!$friendship): ?>
                <button class="add-friend-btn btn-primary" data-user-id="<?php echo $user_id; ?>">
                    <i class="fas fa-user-plus"></i> Add Friend
                </button>
            <?php elseif($friendship['status'] == 'pending'): ?>
                <span class="btn-success">Friend Request Sent</span>
            <?php else: ?>
                <span class="btn-success">Friends</span>
            <?php endif; ?>
            <a href="chat.php?user=<?php echo $user_id; ?>" class="btn-primary">
                <i class="fas fa-envelope"></i> Send Message
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>