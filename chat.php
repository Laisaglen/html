<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get selected chat user
$chat_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Get friends for chat list
$stmt = $db->prepare("
    SELECT u.* FROM users u
    JOIN friends f ON (f.user_id = ? AND f.friend_user_id = u.user_id AND f.status = 'accepted')
    UNION
    SELECT u.* FROM users u
    JOIN friends f ON (f.user_id = u.user_id AND f.friend_user_id = ? AND f.status = 'accepted')
");
$stmt->execute([$user_id, $user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messages with selected user
$messages = [];
if ($chat_user_id) {
    $stmt = $db->prepare("
        SELECT m.*, u.username, u.profile_photo 
        FROM messages m
        JOIN users u ON u.user_id = m.sender_id
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        AND m.expires_at > NOW()
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $chat_user_id, $chat_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="chat-container">
    <div class="chat-sidebar">
        <h3><i class="fas fa-comments"></i> Chats</h3>
        <div class="form-group">
            <input type="text" placeholder="Search friends..." id="search-chat">
        </div>
        <div id="chat-friends-list">
            <?php foreach($friends as $friend): ?>
            <a href="chat.php?user=<?php echo $friend['user_id']; ?>" 
               style="display: block; padding: 10px; border-radius: 5px; margin-bottom: 5px; text-decoration: none; color: #333; <?php echo ($chat_user_id == $friend['user_id']) ? 'background: #f0f2f5;' : ''; ?>">
                <div style="display: flex; align-items: center;">
                    <img src="../assets/uploads/profiles/<?php echo $friend['profile_photo'] ?: 'default.png'; ?>" 
                         style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                    <div>
                        <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                        <br>
                        <small style="color: #666;"><?php echo htmlspecialchars($friend['department']); ?></small>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="chat-main">
        <?php if($chat_user_id): ?>
            <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                <?php
                $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$chat_user_id]);
                $chat_user = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <img src="../assets/uploads/profiles/<?php echo $chat_user['profile_photo'] ?: 'default.png'; ?>" 
                     style="width: 40px; height: 40px; border-radius: 50%; margin-right: 15px;">
                <div>
                    <strong><?php echo htmlspecialchars($chat_user['username']); ?></strong>
                    <br>
                    <small style="color: #666;"><?php echo htmlspecialchars($chat_user['department']); ?></small>
                </div>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <?php foreach($messages as $message): ?>
                <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                    <div class="message-content">
                        <?php if($message['media_type'] == 'image' && $message['media_path']): ?>
                            <img src="../assets/uploads/posts/<?php echo $message['media_path']; ?>" style="max-width: 200px; border-radius: 5px;">
                        <?php elseif($message['media_type'] == 'video' && $message['media_path']): ?>
                            <video controls style="max-width: 200px; border-radius: 5px;">
                                <source src="../assets/uploads/posts/<?php echo $message['media_path']; ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        <div style="font-size: 10px; color: <?php echo $message['sender_id'] == $user_id ? '#fff' : '#999'; ?>; margin-top: 5px;">
                            <?php echo formatDate($message['created_at']); ?>
                            <?php if($message['sender_id'] != $user_id): ?>
                                <span class="post-expiry" data-expiry="<?php echo $message['expires_at']; ?>" style="display: block;"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-input">
                <input type="hidden" id="receiver-id" value="<?php echo $chat_user_id; ?>">
                <input type="text" id="message-input" placeholder="Type a message..." style="flex: 1;">
                <input type="file" id="file-input" accept="image/*,video/*" style="display: none;">
                <button id="attach-file-btn" class="btn-primary" onclick="document.getElementById('file-input').click();">
                    <i class="fas fa-paperclip"></i>
                </button>
                <button id="send-message-btn" class="btn-primary"><i class="fas fa-paper-plane"></i></button>
            </div>
        <?php else: ?>
            <div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #999;">
                <div style="text-align: center;">
                    <i class="fas fa-comment" style="font-size: 50px; margin-bottom: 20px;"></i>
                    <h3>Select a friend to start chatting</h3>
                    <p>Choose a friend from the list on the left</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Scroll to bottom of chat
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>