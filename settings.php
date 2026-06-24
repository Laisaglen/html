<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        $email = sanitizeInput($_POST['email']);
        
        // Check if email is already taken
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already in use by another account.";
        } else {
            // Update password if provided
            if (!empty($_POST['new_password'])) {
                $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET email = ?, password = ? WHERE user_id = ?");
                $stmt->execute([$email, $password, $user_id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$email, $user_id]);
            }
            $_SESSION['email'] = $email;
            $_SESSION['success'] = "Settings updated successfully!";
            header("Location: settings.php");
            exit();
        }
    }
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="settings-container">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <h2><i class="fas fa-cog"></i> Settings</h2>
    
    <div class="settings-tabs">
        <div class="settings-tab active" data-tab="account">Account</div>
        <div class="settings-tab" data-tab="privacy">Privacy</div>
        <div class="settings-tab" data-tab="notifications">Notifications</div>
    </div>
    
    <!-- Account Settings -->
    <div id="account-panel" class="settings-panel">
        <form method="POST">
            <input type="hidden" name="update_settings" value="1">
            
            <div class="form-group">
                <label>Username (Cannot be changed)</label>
                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="Enter new password">
            </div>
            
            <div class="form-group">
                <label>Department</label>
                <input type="text" value="<?php echo htmlspecialchars($user['department']); ?>" disabled>
            </div>
            
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
    
    <!-- Privacy Settings -->
    <div id="privacy-panel" class="settings-panel" style="display: none;">
        <div class="form-group">
            <label>
                <input type="checkbox"> Allow friend requests from everyone
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox"> Show my email on profile
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox"> Allow search engines to index my profile
            </label>
        </div>
        <button class="btn-primary"><i class="fas fa-save"></i> Save Privacy Settings</button>
    </div>
    
    <!-- Notification Settings -->
    <div id="notifications-panel" class="settings-panel" style="display: none;">
        <div class="form-group">
            <label>
                <input type="checkbox" checked> Email notifications
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" checked> Friend request notifications
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" checked> Message notifications
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox"> Market notifications
            </label>
        </div>
        <button class="btn-primary"><i class="fas fa-save"></i> Save Notification Settings</button>
    </div>
</div>

<script>
$(document).ready(function() {
    // Settings tab switching
    $('.settings-tab').click(function() {
        const tab = $(this).data('tab');
        $('.settings-tab').removeClass('active');
        $(this).addClass('active');
        $('.settings-panel').hide();
        $('#' + tab + '-panel').show();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>