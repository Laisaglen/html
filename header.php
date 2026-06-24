<?php
// Absolute path to config
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNP Dating Site</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php if(isLoggedIn()): 
        $unread = getUnreadMessages($_SESSION['user_id']);
        $requests = getFriendRequests($_SESSION['user_id']);
    ?>
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo">
                <h1><i class="fas fa-heart"></i> KNP Dating</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>pages/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/friends.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'friends.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Friends
                        <?php if($requests > 0): ?>
                            <span class="badge"><?php echo $requests; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/chat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Inbox
                        <?php if($unread > 0): ?>
                            <span class="badge"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/market.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'market.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Market
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Profile
                    </a></li>
                </ul>
            </nav>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <main>