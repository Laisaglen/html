<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $content = sanitizeInput($_POST['content']);
    $media_type = 'text';
    $media_path = '';
    
    // Handle file upload
    if (isset($_FILES['post_media']) && $_FILES['post_media']['error'] == 0) {
        $file = $_FILES['post_media'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (strpos($file_type, 'image/') === 0 && $file['size'] <= MAX_IMAGE_SIZE) {
            $media_type = 'image';
            $target_dir = "../assets/uploads/posts/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $media_path = time() . '_' . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $target_dir . $media_path);
        } else if (strpos($file_type, 'video/') === 0 && $file['size'] <= MAX_VIDEO_SIZE) {
            $media_type = 'video';
            $target_dir = "../assets/uploads/posts/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $media_path = time() . '_' . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $target_dir . $media_path);
        }
    }
    
    $expires_at = generateExpiryTime();
    $stmt = $db->prepare("INSERT INTO posts (user_id, content, media_type, media_path, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $content, $media_type, $media_path, $expires_at]);
}

// Get posts (with user info)
$stmt = $db->prepare("
    SELECT p.*, u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
           (SELECT COUNT(*) FROM shares WHERE post_id = p.post_id) as share_count,
           EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.expires_at > NOW()
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-grid">
    <div class="main-content">
        <!-- Create Post -->
        <div class="post-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="create_post" value="1">
                <div class="form-group">
                    <textarea name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="post_media" accept="image/*,video/*">
                    <small>Max: Image 5MB, Video 30MB</small>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-pen"></i> Post</button>
            </form>
        </div>
        
        <!-- Posts Feed -->
        <?php foreach($posts as $post): ?>
        <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
            <div class="post-header">
                <div class="post-avatar">
                    <img src="../assets/uploads/profiles/<?php echo $post['profile_photo'] ?: 'default.png'; ?>" alt="<?php echo htmlspecialchars($post['username']); ?>">
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    <div style="font-size: 12px; color: #666;">
                        <?php echo formatDate($post['created_at']); ?>
                        <span class="post-expiry" data-expiry="<?php echo $post['expires_at']; ?>"></span>
                    </div>
                </div>
            </div>
            <div class="post-content">
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                
                <?php if($post['media_type'] == 'image' && $post['media_path']): ?>
                    <img src="../assets/uploads/posts/<?php echo $post['media_path']; ?>" alt="Post image" class="post-media">
                <?php elseif($post['media_type'] == 'video' && $post['media_path']): ?>
                    <video controls class="post-media">
                        <source src="../assets/uploads/posts/<?php echo $post['media_path']; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </div>
            <div class="post-actions">
                <button class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-thumbs-up"></i> 
                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                </button>
                <button class="share-btn" data-post-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-share"></i> 
                    <span class="share-count"><?php echo $post['share_count']; ?></span>
                </button>
                <a href="view.php?id=<?php echo $post['user_id']; ?>" class="view-profile-btn">
                    <i class="fas fa-user"></i> View Profile
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="dashboard-sidebar">
        <!-- Quick Info -->
        <div class="post-card">
            <h3>Quick Links</h3>
            <ul style="list-style: none; padding: 0;">
                <li><a href="friends.php"><i class="fas fa-user-plus"></i> Find Friends</a></li>
                <li><a href="market.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
            </ul>
        </div>
        
        <!-- Friend Suggestions -->
        <div class="post-card">
            <h3>People You May Know</h3>
            <?php
            $stmt = $db->prepare("
                SELECT u.* FROM users u
                WHERE u.user_id != ? 
                AND u.user_id NOT IN (
                    SELECT friend_user_id FROM friends WHERE user_id = ? AND status = 'accepted'
                    UNION
                    SELECT user_id FROM friends WHERE friend_user_id = ? AND status = 'accepted'
                )
                LIMIT 5
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php foreach($suggestions as $suggestion): ?>
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <img src="../assets/uploads/profiles/<?php echo $suggestion['profile_photo'] ?: 'default.png'; ?>" 
                     style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <div>
                    <strong><?php echo htmlspecialchars($suggestion['username']); ?></strong>
                    <br>
                    <button class="add-friend-btn btn-primary" data-user-id="<?php echo $suggestion['user_id']; ?>" style="padding: 2px 10px; font-size: 12px;">
                        Add Friend
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>