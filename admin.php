<?php
// admin.php - LGK Tech Solutions Admin Dashboard
// Location: /admin/index.php or /admin.php
// Rename to admin/index.php if placing in admin folder

session_start();
include "../config/database.php";

// ============================================================
// CHECK ADMIN LOGIN
// ============================================================
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// ============================================================
// GET DASHBOARD STATISTICS
// ============================================================

// Total Feedback
$result = $conn->query("SELECT COUNT(*) as total FROM feedback");
$stats['total_feedback'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Feedback
$result = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE status = 'pending'");
$stats['pending_feedback'] = $result->fetch_assoc()['total'] ?? 0;

// Replied Feedback
$result = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE status = 'replied'");
$stats['replied_feedback'] = $result->fetch_assoc()['total'] ?? 0;

// Total Messages
$result = $conn->query("SELECT COUNT(*) as total FROM messages");
$stats['total_messages'] = $result->fetch_assoc()['total'] ?? 0;

// Unread Messages
$result = $conn->query("SELECT COUNT(*) as total FROM messages WHERE status = 'pending'");
$stats['unread_messages'] = $result->fetch_assoc()['total'] ?? 0;

// Total Subscribers (Newsletter)
$result = $conn->query("SELECT COUNT(*) as total FROM newsletter WHERE status = 'active'");
$stats['subscribers'] = $result->fetch_assoc()['total'] ?? 0;

// Recent Feedback (Last 5)
$recent_feedback = $conn->query("
    SELECT * FROM feedback 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Recent Messages (Last 5)
$recent_messages = $conn->query("
    SELECT * FROM messages 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Recent Subscribers (Last 5)
$recent_subscribers = $conn->query("
    SELECT * FROM newsletter 
    ORDER BY subscribed_at DESC 
    LIMIT 5
");

// ============================================================
// GET RATING DISTRIBUTION
// ============================================================
$rating_stats = [];
$rating_result = $conn->query("
    SELECT rating, COUNT(*) as count 
    FROM feedback 
    WHERE rating > 0 
    GROUP BY rating 
    ORDER BY rating DESC
");
while ($row = $rating_result->fetch_assoc()) {
    $rating_stats[$row['rating']] = $row['count'];
}

// ============================================================
// GET MONTHLY STATS (Last 6 Months)
// ============================================================
$monthly_stats = [];
$monthly_result = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM feedback
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LGK Tech Solutions</title>
    
    <link rel="stylesheet" href="../modern-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
           ADMIN DASHBOARD STYLES
           ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0b1224;
            color: #ffffff;
            min-height: 100vh;
        }
        
        /* =========================
           ADMIN HEADER
           ========================= */
        .admin-header {
            background: rgba(17, 27, 33, 0.95);
            backdrop-filter: blur(20px);
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-header .logo {
            font-size: 20px;
            font-weight: 700;
            color: #00d4ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-header .logo i {
            font-size: 24px;
        }
        
        .admin-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-header .user-info .username {
            color: #a0aab4;
            font-size: 14px;
        }
        
        .admin-header .user-info .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #0b1224;
            font-size: 16px;
        }
        
        .admin-header .user-info .logout {
            color: #ff4d4d;
            text-decoration: none;
            font-size: 13px;
            padding: 6px 15px;
            border: 1px solid rgba(255, 77, 77, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .admin-header .user-info .logout:hover {
            background: rgba(255, 77, 77, 0.1);
        }
        
        /* =========================
           DASHBOARD CONTAINER
           ========================= */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .dashboard-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .dashboard-title h1 {
            font-size: 28px;
            font-weight: 700;
        }
        
        .dashboard-title h1 i {
            color: #00d4ff;
            margin-right: 10px;
        }
        
        .dashboard-title .welcome-text {
            color: #a0aab4;
            font-size: 14px;
        }
        
        .dashboard-title .date-display {
            color: #4a5568;
            font-size: 14px;
        }
        
        /* =========================
           QUICK ACTIONS
           ========================= */
        .quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .quick-action-btn {
            padding: 10px 22px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .quick-action-btn:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: rgba(0, 212, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .quick-action-btn .badge {
            background: #ff4d4d;
            color: #fff;
            font-size: 11px;
            padding: 1px 8px;
            border-radius: 50px;
        }
        
        .quick-action-btn.primary {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(0, 255, 166, 0.15));
            border-color: rgba(0, 212, 255, 0.2);
        }
        
        .quick-action-btn.primary:hover {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.25), rgba(0, 255, 166, 0.25));
        }
        
        /* =========================
           STATS GRID
           ========================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 22px 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 212, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .stat-card .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }
        
        .stat-card .stat-icon.blue {
            background: rgba(0, 212, 255, 0.12);
            color: #00d4ff;
        }
        
        .stat-card .stat-icon.green {
            background: rgba(0, 255, 166, 0.12);
            color: #00ffa6;
        }
        
        .stat-card .stat-icon.orange {
            background: rgba(255, 165, 0, 0.12);
            color: #ffa500;
        }
        
        .stat-card .stat-icon.red {
            background: rgba(255, 77, 77, 0.12);
            color: #ff4d4d;
        }
        
        .stat-card .stat-icon.purple {
            background: rgba(155, 89, 182, 0.12);
            color: #9b59b6;
        }
        
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-card .stat-label {
            color: #a0aab4;
            font-size: 13px;
            font-weight: 500;
        }
        
        .stat-card .stat-change {
            font-size: 12px;
            margin-top: 8px;
            display: inline-block;
            padding: 2px 10px;
            border-radius: 50px;
        }
        
        .stat-change.up {
            background: rgba(0, 255, 166, 0.1);
            color: #00ffa6;
        }
        
        .stat-change.down {
            background: rgba(255, 77, 77, 0.1);
            color: #ff4d4d;
        }
        
        /* =========================
           CHARTS / RATING SECTION
           ========================= */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 25px;
        }
        
        .chart-card h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #a0aab4;
        }
        
        .rating-bars {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .rating-bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .rating-bar-item .rating-label {
            font-size: 14px;
            min-width: 30px;
            color: #a0aab4;
        }
        
        .rating-bar-item .rating-track {
            flex: 1;
            height: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-bar-item .rating-track .rating-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ff6b00);
            border-radius: 4px;
            transition: width 0.8s ease;
        }
        
        .rating-bar-item .rating-count {
            font-size: 13px;
            color: #4a5568;
            min-width: 30px;
            text-align: right;
        }
        
        .total-ratings {
            margin-top: 15px;
            color: #4a5568;
            font-size: 13px;
            text-align: center;
        }
        
        /* Monthly Stats */
        .monthly-stats {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 120px;
            gap: 10px;
            padding-top: 10px;
        }
        
        .monthly-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        
        .monthly-bar .bar {
            width: 100%;
            max-width: 40px;
            background: linear-gradient(180deg, #00d4ff, #00ffa6);
            border-radius: 4px 4px 0 0;
            transition: height 0.8s ease;
            min-height: 4px;
        }
        
        .monthly-bar .bar-label {
            font-size: 11px;
            color: #4a5568;
        }
        
        /* =========================
           RECENT SECTIONS
           ========================= */
        .recent-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .recent-section {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 25px;
        }
        
        .recent-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .recent-section .section-header h3 {
            font-size: 16px;
            font-weight: 600;
        }
        
        .recent-section .section-header .view-all {
            color: #00d4ff;
            text-decoration: none;
            font-size: 13px;
        }
        
        .recent-section .section-header .view-all:hover {
            text-decoration: underline;
        }
        
        .recent-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .recent-item .item-content {
            flex: 1;
        }
        
        .recent-item .item-title {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .recent-item .item-meta {
            color: #a0aab4;
            font-size: 12px;
        }
        
        .recent-item .item-status {
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 50px;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(255, 165, 0, 0.12);
            color: #ffa500;
        }
        
        .status-replied {
            background: rgba(0, 255, 166, 0.12);
            color: #00ffa6;
        }
        
        .status-read {
            background: rgba(0, 212, 255, 0.12);
            color: #00d4ff;
        }
        
        .status-active {
            background: rgba(0, 255, 166, 0.12);
            color: #00ffa6;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #4a5568;
        }
        
        .empty-state i {
            font-size: 36px;
            margin-bottom: 10px;
            opacity: 0.3;
        }
        
        /* =========================
           RESPONSIVE
           ========================= */
        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .recent-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 10px 15px;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .admin-header .user-info .username {
                display: none;
            }
            
            .dashboard-title {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card .stat-number {
                font-size: 22px;
            }
            
            .monthly-bar .bar {
                max-width: 25px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     ADMIN HEADER
     ========================= -->
<header class="admin-header">
    <a href="index.php" class="logo">
        <i class="fas fa-cogs"></i>
        LGK Admin
    </a>
    <div class="user-info">
        <span class="username">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin_username); ?>
        </span>
        <div class="avatar">
            <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
        </div>
        <a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<!-- =========================
     DASHBOARD CONTENT
     ========================= -->
<div class="dashboard-container">
    
    <!-- Dashboard Title -->
    <div class="dashboard-title">
        <div>
            <h1><i class="fas fa-chart-pie"></i> Dashboard</h1>
            <span class="welcome-text">Welcome back, <?php echo htmlspecialchars($admin_username); ?>!</span>
        </div>
        <span class="date-display">
            <i class="fas fa-calendar"></i> <?php echo date('l, F d, Y'); ?>
        </span>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="messages.php" class="quick-action-btn primary">
            <i class="fas fa-envelope"></i> View Messages
            <?php if ($stats['pending_feedback'] > 0): ?>
                <span class="badge"><?php echo $stats['pending_feedback']; ?></span>
            <?php endif; ?>
        </a>
        <a href="messages.php?status=pending" class="quick-action-btn">
            <i class="fas fa-bell"></i> Pending
            <?php if ($stats['pending_feedback'] > 0): ?>
                <span class="badge"><?php echo $stats['pending_feedback']; ?></span>
            <?php endif; ?>
        </a>
        <a href="../feedback.php" class="quick-action-btn" target="_blank">
            <i class="fas fa-plus"></i> New Feedback
        </a>
        <a href="subscribers.php" class="quick-action-btn">
            <i class="fas fa-users"></i> Subscribers
            <?php if ($stats['subscribers'] > 0): ?>
                <span class="badge" style="background:#00d4ff;"><?php echo $stats['subscribers']; ?></span>
            <?php endif; ?>
        </a>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-number"><?php echo $stats['total_feedback']; ?></div>
            <div class="stat-label">Total Feedback</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?php echo $stats['pending_feedback']; ?></div>
            <div class="stat-label">Pending Feedback</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?php echo $stats['replied_feedback']; ?></div>
            <div class="stat-label">Replied</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-comment"></i>
            </div>
            <div class="stat-number"><?php echo $stats['total_messages']; ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-number"><?php echo $stats['unread_messages']; ?></div>
            <div class="stat-label">Unread Messages</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $stats['subscribers']; ?></div>
            <div class="stat-label">Newsletter Subscribers</div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        
        <!-- Rating Distribution -->
        <div class="chart-card">
            <h3><i class="fas fa-star" style="color:#ffd700;"></i> Rating Distribution</h3>
            <div class="rating-bars">
                <?php 
                $total_ratings = array_sum($rating_stats);
                for ($i = 5; $i >= 1; $i--): 
                    $count = $rating_stats[$i] ?? 0;
                    $percentage = $total_ratings > 0 ? ($count / $total_ratings) * 100 : 0;
                ?>
                <div class="rating-bar-item">
                    <span class="rating-label"><?php echo $i; ?> ★</span>
                    <div class="rating-track">
                        <div class="rating-fill" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    <span class="rating-count"><?php echo $count; ?></span>
                </div>
                <?php endfor; ?>
            </div>
            <div class="total-ratings">
                <?php echo $total_ratings; ?> total ratings
            </div>
        </div>
        
        <!-- Monthly Stats -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar" style="color:#00d4ff;"></i> Monthly Feedback</h3>
            <?php if (!empty($monthly_stats)): ?>
                <div class="monthly-stats">
                    <?php 
                    $max_count = max(array_column($monthly_stats, 'count')) ?: 1;
                    foreach ($monthly_stats as $month): 
                        $height = ($month['count'] / $max_count) * 100;
                    ?>
                        <div class="monthly-bar">
                            <div class="bar" style="height: <?php echo max($height, 5); ?>%;"></div>
                            <span class="bar-label"><?php echo date('M', strtotime($month['month'] . '-01')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No data available yet</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- Recent Sections -->
    <div class="recent-grid">
        
        <!-- Recent Feedback -->
        <div class="recent-section">
            <div class="section-header">
                <h3><i class="fas fa-star" style="color:#ffd700;"></i> Recent Feedback</h3>
                <a href="messages.php" class="view-all">View All →</a>
            </div>
            
            <?php if ($recent_feedback && $recent_feedback->num_rows > 0): ?>
                <?php while($row = $recent_feedback->fetch_assoc()): ?>
                    <div class="recent-item">
                        <div class="item-content">
                            <div class="item-title">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </div>
                            <div class="item-meta">
                                <?php echo htmlspecialchars(substr($row['message'], 0, 60)) . (strlen($row['message']) > 60 ? '...' : ''); ?>
                                <br>
                                <small><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small>
                            </div>
                        </div>
                        <span class="item-status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No feedback yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Messages -->
        <div class="recent-section">
            <div class="section-header">
                <h3><i class="fas fa-comment" style="color:#00d4ff;"></i> Recent Messages</h3>
                <a href="messages.php" class="view-all">View All →</a>
            </div>
            
            <?php if ($recent_messages && $recent_messages->num_rows > 0): ?>
                <?php while($row = $recent_messages->fetch_assoc()): ?>
                    <div class="recent-item">
                        <div class="item-content">
                            <div class="item-title">
                                <?php echo htmlspecialchars($row['name'] ?? $row['email']); ?>
                            </div>
                            <div class="item-meta">
                                <?php echo htmlspecialchars(substr($row['message'], 0, 60)) . (strlen($row['message']) > 60 ? '...' : ''); ?>
                                <br>
                                <small><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small>
                            </div>
                        </div>
                        <span class="item-status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No messages yet</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
</div>

<!-- =========================
     JAVASCRIPT
     ========================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh badge count every 30 seconds
    function updateBadge() {
        fetch('get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.quick-action-btn .badge');
                if (badge && data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                }
            })
            .catch(error => console.log('Badge update error:', error));
    }
    
    // Update badge every 30 seconds
    setInterval(updateBadge, 30000);
});

console.log('🚀 Admin dashboard loaded successfully!');
</script>

</body>
</html>