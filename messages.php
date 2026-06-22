<?php
// admin/messages.php - Manage all messages and feedback

session_start();
include "../config/database.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($status_filter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query = "SELECT * FROM feedback WHERE $where ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$feedback = $stmt->get_result();

// Get counts for tabs
$counts = [];
$counts['all'] = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$counts['pending'] = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'")->fetch_assoc()['count'];
$counts['read'] = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'read'")->fetch_assoc()['count'];
$counts['replied'] = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'replied'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - LGK Admin</title>
    <link rel="stylesheet" href="../modern-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0b1224;
            color: #fff;
        }
        .admin-header {
            background: rgba(17, 27, 33, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .admin-header .logo {
            font-size: 20px;
            font-weight: 700;
            color: #00d4ff;
            text-decoration: none;
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
        .admin-header .user-info .logout {
            color: #ff4d4d;
            text-decoration: none;
            padding: 6px 15px;
            border: 1px solid rgba(255,77,77,0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .admin-header .user-info .logout:hover {
            background: rgba(255,77,77,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .tabs {
            display: flex;
            gap: 5px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 5px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #a0aab4;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .tab-btn:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .tab-btn.active {
            background: rgba(0,212,255,0.15);
            color: #00d4ff;
        }
        .tab-btn .badge {
            display: inline-block;
            padding: 0 6px;
            border-radius: 50px;
            font-size: 11px;
            background: rgba(255,77,77,0.2);
            color: #ff4d4d;
        }
        .search-box {
            display: flex;
            gap: 10px;
        }
        .search-box input {
            padding: 10px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            width: 250px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        .search-box button {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: rgba(0,212,255,0.1);
            color: #00d4ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-box button:hover {
            background: rgba(0,212,255,0.2);
        }
        .table-wrapper {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            padding: 15px 20px;
            text-align: left;
            color: #a0aab4;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        table tr:hover {
            background: rgba(255,255,255,0.02);
        }
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 500;
        }
        .status-pending {
            background: rgba(255,165,0,0.1);
            color: #ffa500;
        }
        .status-read {
            background: rgba(0,212,255,0.1);
            color: #00d4ff;
        }
        .status-replied {
            background: rgba(0,255,166,0.1);
            color: #00ffa6;
        }
        .action-btn {
            padding: 4px 12px;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn.view {
            background: rgba(0,212,255,0.15);
            color: #00d4ff;
        }
        .action-btn.view:hover {
            background: rgba(0,212,255,0.25);
        }
        .action-btn.reply {
            background: rgba(0,255,166,0.15);
            color: #00ffa6;
        }
        .action-btn.reply:hover {
            background: rgba(0,255,166,0.25);
        }
        .action-btn.delete {
            background: rgba(255,77,77,0.15);
            color: #ff4d4d;
        }
        .action-btn.delete:hover {
            background: rgba(255,77,77,0.25);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #4a5568;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        @media (max-width: 768px) {
            .admin-header {
                padding: 10px 15px;
                flex-wrap: wrap;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box input {
                width: 100%;
            }
            .tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
            .tab-btn {
                white-space: nowrap;
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <a href="admindashboard.php" class="logo">
        <i class="fas fa-cogs"></i> LGK Admin
    </a>
    <div class="user-info">
        <span class="username">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
        </span>
        <a href="logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-envelope"></i> Messages</h1>
        
        <div class="search-box">
            <form method="GET" style="display:flex;gap:10px;width:100%;">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <input type="text" name="search" placeholder="Search messages..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?status=all" class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            All <span class="badge"><?php echo $counts['all']; ?></span>
        </a>
        <a href="?status=pending" class="tab-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
            Pending <span class="badge"><?php echo $counts['pending']; ?></span>
        </a>
        <a href="?status=read" class="tab-btn <?php echo $status_filter === 'read' ? 'active' : ''; ?>">
            Read <span class="badge"><?php echo $counts['read']; ?></span>
        </a>
        <a href="?status=replied" class="tab-btn <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">
            Replied <span class="badge"><?php echo $counts['replied']; ?></span>
        </a>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <?php if ($feedback && $feedback->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $feedback->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['message'], 0, 50)) . (strlen($row['message']) > 50 ? '...' : ''); ?></td>
                    <td><?php echo $row['rating'] > 0 ? str_repeat('⭐', $row['rating']) : '-'; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="view_message.php?id=<?php echo $row['id']; ?>" class="action-btn view">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($row['status'] !== 'replied'): ?>
                            <a href="../reply.php?id=<?php echo $row['id']; ?>" class="action-btn reply">
                                <i class="fas fa-reply"></i> Reply
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No messages found</h3>
            <p style="color:#a0aab4;">Messages will appear here when users contact you.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>