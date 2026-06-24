<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get profile data
$stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $hobbies = sanitizeInput($_POST['hobbies']);
        $likes = sanitizeInput($_POST['likes']);
        $dislikes = sanitizeInput($_POST['dislikes']);
        $facebook = sanitizeInput($_POST['facebook']);
        $tiktok = sanitizeInput($_POST['tiktok']);
        $bio = sanitizeInput($_POST['bio']);
        
        // Update profile
        $stmt = $db->prepare("UPDATE profiles SET bio = ? WHERE user_id = ?");
        $stmt->execute([$bio, $user_id]);
        
        // Update user
        $stmt = $db->prepare("UPDATE users SET hobbies = ?, likes = ?, dislikes = ?, facebook_link = ?, tiktok_link = ? WHERE user_id = ?");
        $stmt->execute([$hobbies, $likes, $dislikes, $facebook, $tiktok, $user_id]);
        
        // Handle profile photo update
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $target_dir = "../assets/uploads/profiles/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $profile_photo = time() . '_' . basename($_FILES['profile_photo']['name']);
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_dir . $profile_photo);
            
            $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
            $stmt->execute([$profile_photo, $user_id]);
            $_SESSION['profile_photo'] = $profile_photo;
        }
        
        // Handle cover photo update
        if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
            $target_dir = "../assets/uploads/covers/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $cover_photo = time() . '_' . basename($_FILES['cover_photo']['name']);
            move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_dir . $cover_photo);
            
            $stmt = $db->prepare("UPDATE users SET cover_photo = ? WHERE user_id = ?");
            $stmt->execute([$cover_photo, $user_id]);
        }
        
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    }
}

// Get friends count
$stmt = $db->prepare("
    SELECT COUNT(*) as friend_count FROM friends 
    WHERE (user_id = ? OR friend_user_id = ?) AND status = 'accepted'
");
$stmt->execute([$user_id, $user_id]);
$friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['friend_count'];
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
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($user['department']); ?></p>
        <p><i class="fas fa-venus-mars"></i> <?php echo ucfirst($user['gender']); ?></p>
        <p><i class="fas fa-users"></i> <?php echo $friend_count; ?> Friends</p>
        <?php if($user['facebook_link']): ?>
            <a href="<?php echo htmlspecialchars($user['facebook_link']); ?>" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
        <?php endif; ?>
        <?php if($user['tiktok_link']): ?>
            <a href="<?php echo htmlspecialchars($user['tiktok_link']); ?>" target="_blank"><i class="fab fa-tiktok"></i> TikTok</a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="main-content">
        <div class="post-card">
            <h3><i class="fas fa-edit"></i> Edit Profile</h3>
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="3"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Hobbies</label>
                    <input type="text" name="hobbies" value="<?php echo htmlspecialchars($user['hobbies']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Things I Like</label>
                    <input type="text" name="likes" value="<?php echo htmlspecialchars($user['likes']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Things I Dislike</label>
                    <input type="text" name="dislikes" value="<?php echo htmlspecialchars($user['dislikes']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Facebook Profile URL</label>
                    <input type="url" name="facebook" value="<?php echo htmlspecialchars($user['facebook_link']); ?>" placeholder="https://facebook.com/username">
                </div>
                
                <div class="form-group">
                    <label>TikTok Profile URL</label>
                    <input type="url" name="tiktok" value="<?php echo htmlspecialchars($user['tiktok_link']); ?>" placeholder="https://tiktok.com/@username">
                </div>
                
                <div class="form-group">
                    <label>Update Profile Photo (max 5MB)</label>
                    <input type="file" name="profile_photo" accept="image/*" class="image-input" id="profile-photo-input">
                    <img id="profile-photo-preview" style="max-width: 100px; margin-top: 10px; display: none;">
                </div>
                
                <div class="form-group">
                    <label>Update Cover Photo (max 5MB)</label>
                    <input type="file" name="cover_photo" accept="image/*" class="image-input" id="cover-photo-input">
                    <img id="cover-photo-preview" style="max-width: 200px; margin-top: 10px; display: none;">
                </div>
                
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            </form>
        </div>
    </div>
    
    <div class="dashboard-sidebar">
        <div class="post-card">
            <h3>Profile Stats</h3>
            <ul style="list-style: none; padding: 0;">
                <li><i class="fas fa-calendar"></i> Member since: <?php echo formatDate($user['created_at']); ?></li>
                <li><i class="fas fa-users"></i> Friends: <?php echo $friend_count; ?></li>
                <li><i class="fas fa-store"></i> <a href="market.php">My Market Items</a></li>
            </ul>
        </div>
        
        <div class="post-card">
            <h3>Quick Actions</h3>
            <a href="settings.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-bottom: 10px;">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="logout.php" class="btn-danger" style="display: block; text-align: center; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>