<?php
// includes/header.php - Complete website header with navigation

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Check if user is admin
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$admin_username = $_SESSION['admin_username'] ?? '';

// Get pending notification count
$pending_count = 0;
if ($is_admin) {
    include_once __DIR__ . '/../config/database.php';
    if (isset($conn) && $conn) {
        $count_query = "SELECT COUNT(*) as pending FROM feedback WHERE status = 'pending'";
        $count_result = mysqli_query($conn, $count_query);
        if ($count_result) {
            $count_row = mysqli_fetch_assoc($count_result);
            $pending_count = $count_row['pending'] ?? 0;
        }
    }
}

// Page title fallback
$page_title = $page_title ?? 'LGK Tech Solutions - Smart IT Solutions For Modern Businesses';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- ============================================================
         META TAGS
         ============================================================ -->
    <meta name="description" content="LGK Tech Solutions - Smart IT solutions for modern businesses. Web development, cybersecurity, cloud services, computer repair, and data analytics.">
    <meta name="keywords" content="IT solutions, web development, cybersecurity, cloud services, computer repair, data analytics, Nairobi, Kenya">
    <meta name="author" content="LGK Tech Solutions">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="LGK Tech Solutions - Smart IT Solutions">
    <meta property="og:description" content="Professional IT solutions for modern businesses.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://lgktech.com">
    <meta property="og:image" content="https://lgktech.com/assets/og-image.jpg">
    
    <!-- ============================================================
         STYLESHEETS
         ============================================================ -->
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="/-/modern-styles.css">
    
    <!-- ============================================================
         FAVICON
         ============================================================ -->
    <link rel="icon" type="image/png" sizes="32x32" href="/-/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/-/assets/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/-/assets/apple-touch-icon.png">
    
    <!-- ============================================================
         EXTRA STYLES (Header-specific)
         ============================================================ -->
    <style>
        /* =========================
           BASE RESET & GLOBAL
        ========================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0b1224;
            color: #ffffff;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* =========================
           HEADER / NAVBAR
        ========================= */
        .lgk-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(11, 18, 36, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            height: 70px;
        }
        
        .lgk-header.scrolled {
            background: rgba(11, 18, 36, 0.98);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        .logo .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #0b1224;
            transition: transform 0.3s ease;
        }
        
        .logo:hover .logo-icon {
            transform: rotate(-10deg) scale(1.05);
        }
        
        .logo .logo-text {
            background: linear-gradient(135deg, #ffffff, #00d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo .logo-text .highlight {
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Navigation */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }
        
        .nav-links a {
            padding: 8px 16px;
            border-radius: 8px;
            color: #a0aab4;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
        }
        
        .nav-links a:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .nav-links a.active {
            color: #00d4ff;
            background: rgba(0, 212, 255, 0.08);
        }
        
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background: linear-gradient(90deg, #00d4ff, #00ffa6);
            border-radius: 2px;
        }
        
        .nav-links a i {
            margin-right: 6px;
            font-size: 13px;
        }
        
        /* Admin Link */
        .nav-links .admin-link {
            padding: 8px 18px;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 8px;
            color: #00d4ff;
            font-weight: 600;
            position: relative;
        }
        
        .nav-links .admin-link:hover {
            background: rgba(0, 212, 255, 0.15);
            border-color: rgba(0, 212, 255, 0.3);
        }
        
        .nav-links .admin-link .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ff4d4d;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
            line-height: 14px;
            animation: pulse-badge 2s infinite;
        }
        
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* User Info (when logged in) */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-info .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #0b1224;
        }
        
        .user-info .user-name {
            font-size: 13px;
            color: #a0aab4;
        }
        
        .user-info .logout-link {
            padding: 6px 14px;
            border: 1px solid rgba(255, 77, 77, 0.2);
            border-radius: 6px;
            color: #ff4d4d;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .user-info .logout-link:hover {
            background: rgba(255, 77, 77, 0.1);
        }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            z-index: 1001;
        }
        
        .menu-toggle span {
            width: 25px;
            height: 2px;
            background: #ffffff;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        
        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }
        
        /* =========================
           RESPONSIVE NAV
        ========================= */
        @media (max-width: 992px) {
            .menu-toggle {
                display: flex;
            }
            
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 300px;
                height: 100vh;
                background: rgba(11, 18, 36, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 80px 30px 30px;
                gap: 5px;
                transition: right 0.3s ease;
                overflow-y: auto;
                border-left: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            .nav-links.open {
                right: 0;
            }
            
            .nav-links a {
                width: 100%;
                padding: 12px 16px;
                font-size: 15px;
                border-radius: 10px;
            }
            
            .nav-links a.active::after {
                display: none;
            }
            
            .nav-links .admin-link {
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
                padding-top: 10px;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            /* Overlay */
            .nav-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .nav-overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .header-container {
                padding: 0 15px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .logo .logo-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            
            .nav-links {
                width: 100%;
                right: -100%;
            }
            
            .nav-links.open {
                right: 0;
            }
        }
        
        /* =========================
           MAIN CONTENT OFFSET
        ========================= */
        .main-content {
            padding-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        /* =========================
           UTILITY CLASSES
        ========================= */
        .section-tag {
            display: inline-block;
            padding: 4px 16px;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            color: #00d4ff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            color: #0b1224;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.25);
        }
        
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: transparent;
            color: #ffffff;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

<!-- ============================================================
     HEADER
     ============================================================ -->
<header class="lgk-header" id="lgkHeader" role="banner">
    <div class="header-container">
        
        <!-- Logo -->
        <a href="/-/index.php" class="logo" aria-label="LGK Tech Solutions Home">
            <span class="logo-icon">
                <i class="fas fa-cogs"></i>
            </span>
            <span class="logo-text">
                LGK<span class="highlight">Tech</span>
            </span>
        </a>
        
        <!-- Navigation -->
        <nav class="nav-links" id="navLinks" role="navigation" aria-label="Main Navigation">
            <a href="/-/index.php" class="<?php echo ($current_page == 'index.php' && $current_dir == 'lgk') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="/-/about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About
            </a>
            <a href="/-/services.php" class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">
                <i class="fas fa-server"></i> Services
            </a>
            <a href="/-/contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Contact
            </a>
            <a href="/-/feedback.php" class="<?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Feedback
            </a>
            
            <!-- Admin Link -->
            <?php if ($is_admin): ?>
                <a href="/-/admin/admindashboard.php" class="admin-link <?php echo ($current_dir == 'admin') ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> Dashboard
                    <?php if ($pending_count > 0): ?>
                        <span class="badge" id="adminBadge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- User Info (Mobile) -->
                <div class="user-info">
                    <span class="user-avatar"><?php echo strtoupper(substr($admin_username, 0, 1)); ?></span>
                    <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                    <a href="/-/admin/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <a href="/-/admin/login.php" class="admin-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i> Admin
                </a>
            <?php endif; ?>
        </nav>
        
        <!-- Right Side (Desktop) -->
        <div style="display:flex;align-items:center;gap:15px;">
            <?php if ($is_admin): ?>
                <div class="user-info" style="display:none;">
                    <span class="user-avatar"><?php echo strtoupper(substr($admin_username, 0, 1)); ?></span>
                    <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
    </div>
</header>

<!-- Mobile Overlay -->
<div class="nav-overlay" id="navOverlay"></div>

<!-- ============================================================
     MAIN CONTENT WRAPPER
     ============================================================ -->
<main class="main-content">

<!-- ============================================================
     HEADER JAVASCRIPT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ================================
    // MOBILE MENU TOGGLE
    // ================================
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    const navOverlay = document.getElementById('navOverlay');
    
    function toggleMenu() {
        menuToggle.classList.toggle('active');
        navLinks.classList.toggle('open');
        navOverlay.classList.toggle('active');
        document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
    }
    
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMenu);
    }
    
    if (navOverlay) {
        navOverlay.addEventListener('click', toggleMenu);
    }
    
    // Close menu on link click (mobile)
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992 && navLinks.classList.contains('open')) {
                toggleMenu();
            }
        });
    });
    
    // Close menu on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navLinks.classList.contains('open')) {
            toggleMenu();
        }
    });
    
    // ================================
    // HEADER SCROLL EFFECT
    // ================================
    const header = document.getElementById('lgkHeader');
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
    
    // ================================
    // ADMIN BADGE UPDATE (AJAX)
    // ================================
    <?php if ($is_admin): ?>
    function updateAdminBadge() {
        fetch('/lgk/admin/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('adminBadge');
                if (badge) {
                    const count = data.count || 0;
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.log('Badge update error:', error));
    }
    
    // Update badge every 30 seconds
    setInterval(updateAdminBadge, 30000);
    <?php endif; ?>
    
    // ================================
    // ACTIVE LINK DETECTION (Mobile)
    // ================================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-links a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace(/^\.\.\//, ''))) {
            link.classList.add('active');
        }
    });
    
    // ================================
    // TOAST NOTIFICATION SYSTEM
    // ================================
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                            type === 'error' ? 'fa-exclamation-circle' : 
                            'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // Inject toast styles
    const toastStyles = document.createElement('style');
    toastStyles.textContent = `
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 14px 22px;
            border-radius: 12px;
            background: rgba(11, 18, 36, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.4s ease;
            max-width: 400px;
            transition: all 0.3s ease;
        }
        
        .toast-success { border-color: rgba(0, 255, 166, 0.3); }
        .toast-error { border-color: rgba(255, 77, 77, 0.3); }
        .toast-info { border-color: rgba(0, 212, 255, 0.3); }
        
        .toast i {
            font-size: 20px;
        }
        
        .toast-success i { color: #00ffa6; }
        .toast-error i { color: #ff4d4d; }
        .toast-info i { color: #00d4ff; }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            .toast {
                left: 20px;
                right: 20px;
                bottom: 20px;
                max-width: none;
                font-size: 13px;
            }
        }
    `;
    document.head.appendChild(toastStyles);
    
    console.log('🚀 LGK Tech Solutions loaded successfully!');
});
</script>