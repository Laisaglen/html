<?php
// ============================================================
// SETUP / INSTALLATION SCRIPT
// ============================================================
// File: setup/index.php
// Description: First-time setup wizard for creating admin account
// Security: Automatically locks after completion
// ============================================================

// Start session
session_start();

// ============================================================
// INCLUDE CONFIGURATION
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// ============================================================
// CHECK IF SETUP IS ALREADY COMPLETED
// ============================================================
$setup_lock_file = __DIR__ . '/setup.lock';
$setup_completed = file_exists($setup_lock_file);

// Also check database for admin users
$admin_check = $conn->query("SELECT COUNT(*) as count FROM " . TABLE_ADMIN_USERS);
if ($admin_check) {
    $admin_row = $admin_check->fetch_assoc();
    $admin_exists = $admin_row['count'] > 0;
} else {
    $admin_exists = false;
}

// If setup is completed or admin exists, redirect to login
if ($setup_completed || $admin_exists) {
    // Create lock file if it doesn't exist
    if (!$setup_completed) {
        file_put_contents($setup_lock_file, 'Setup completed on ' . date('Y-m-d H:i:s'));
    }
    header("Location: ../admin/login.php?setup=completed");
    exit();
}

// ============================================================
// VARIABLES
// ============================================================
$error = '';
$success = '';
$step = 1;
$username = '';
$email = '';
$full_name = '';

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $agree_terms = isset($_POST['agree_terms']);
    
    // ============================================================
    // VALIDATION
    // ============================================================
    $errors = [];
    
    // 1. Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username cannot exceed 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM " . TABLE_ADMIN_USERS . " WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors[] = "Username already exists. Please choose another.";
        }
        $check_stmt->close();
    }
    
    // 2. Full name validation
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($full_name) > 100) {
        $errors[] = "Full name cannot exceed 100 characters.";
    }
    
    // 3. Email validation
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // 4. Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number.";
    } elseif (!preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)) {
        $errors[] = "Password must contain at least one special character.";
    }
    
    // 5. Confirm password
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
    
    // 6. Terms agreement
    if (!$agree_terms) {
        $errors[] = "You must agree to the terms and conditions.";
    }
    
    // ============================================================
    // IF NO ERRORS - CREATE ADMIN ACCOUNT
    // ============================================================
    if (empty($errors)) {
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        // Get IP address
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert admin user
            $insert_stmt = $conn->prepare("
                INSERT INTO " . TABLE_ADMIN_USERS . " 
                (username, password, email, full_name, role, status, ip_address, created_at) 
                VALUES (?, ?, ?, ?, 'super_admin', 'active', ?, NOW())
            ");
            $insert_stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $ip_address);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to create admin account: " . $insert_stmt->error);
            }
            
            $admin_id = $conn->insert_id;
            $insert_stmt->close();
            
            // Log the setup
            $log_stmt = $conn->prepare("
                INSERT INTO " . TABLE_ADMIN_LOGS . " 
                (admin_id, username, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, 'setup', 'First-time setup completed', ?, ?, NOW())
            ");
            $log_stmt->bind_param("isss", $admin_id, $username, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Create setup lock file
            file_put_contents($setup_lock_file, 'Setup completed on ' . date('Y-m-d H:i:s') . ' by ' . $username);
            
            // Update settings
            $settings_stmt = $conn->prepare("
                INSERT INTO " . TABLE_SETTINGS . " (setting_key, setting_value) 
                VALUES ('setup_completed', 'true'), ('site_name', 'LGK Tech Solutions'), ('site_email', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $settings_stmt->bind_param("s", $email);
            $settings_stmt->execute();
            $settings_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Auto-login the admin
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = 'super_admin';
            $_SESSION['setup_completed'] = true;
            
            // Redirect to dashboard with success
            header("Location: ../admin/admindashboard.php?setup=success");
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors[] = "Setup failed: " . $e->getMessage();
            $error = implode("<br>", $errors);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// ============================================================
// CHECK DATABASE CONNECTION
// ============================================================
$db_connected = false;
try {
    if ($conn && !$conn->connect_error) {
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
}

// ============================================================
// GENERATE CSRF TOKEN
// ============================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - LGK Tech Solutions</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* =========================
           BASE
        ========================= */
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        /* Background Effects */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 212, 255, 0.05), transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(0, 255, 166, 0.05), transparent 50%);
            z-index: -1;
        }
        
        /* Particles */
        .particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(0, 212, 255, 0.3);
            border-radius: 50%;
            animation: float linear infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }
        
        /* =========================
           SETUP CONTAINER
        ========================= */
        .setup-container {
            width: 100%;
            max-width: 520px;
            animation: fadeIn 0.6s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .setup-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .setup-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(0, 212, 255, 0.03), transparent 60%);
            animation: rotateGlow 20s linear infinite;
            z-index: -1;
        }
        
        @keyframes rotateGlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* =========================
           HEADER
        ========================= */
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(0, 255, 166, 0.1));
            border: 1px solid rgba(0, 212, 255, 0.15);
            margin-bottom: 15px;
            font-size: 32px;
            color: #00d4ff;
        }
        
        .setup-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #00d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .setup-header p {
            color: #a0aab4;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .setup-header .step-indicator {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 16px;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.12);
            border-radius: 50px;
            font-size: 12px;
            color: #00d4ff;
            font-weight: 500;
        }
        
        /* =========================
           DATABASE STATUS
        ========================= */
        .db-status {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        
        .db-status.success {
            background: rgba(0, 255, 166, 0.08);
            border: 1px solid rgba(0, 255, 166, 0.15);
            color: #00ffa6;
        }
        
        .db-status.error {
            background: rgba(255, 77, 77, 0.08);
            border: 1px solid rgba(255, 77, 77, 0.15);
            color: #ff4d4d;
        }
        
        .db-status i {
            font-size: 18px;
        }
        
        /* =========================
           MESSAGES
        ========================= */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .alert-error {
            background: rgba(255, 77, 77, 0.08);
            border: 1px solid rgba(255, 77, 77, 0.15);
            color: #ff4d4d;
        }
        
        .alert-success {
            background: rgba(0, 255, 166, 0.08);
            border: 1px solid rgba(0, 255, 166, 0.15);
            color: #00ffa6;
        }
        
        .alert i {
            font-size: 18px;
            margin-top: 1px;
            flex-shrink: 0;
        }
        
        /* =========================
           FORM
        ========================= */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #a0aab4;
            margin-bottom: 5px;
        }
        
        .form-group label .required {
            color: #ff4d4d;
            margin-left: 2px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a5568;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 14px 12px 44px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .input-group input:focus + .input-icon {
            color: #00d4ff;
        }
        
        .input-group input::placeholder {
            color: #4a5568;
        }
        
        .input-group .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a5568;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .input-group .toggle-pass:hover {
            color: #00d4ff;
        }
        
        /* Password Strength */
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar .bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-text {
            display: block;
            font-size: 11px;
            color: #4a5568;
            margin-top: 4px;
            text-align: right;
        }
        
        /* Password Requirements */
        .password-requirements {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 15px;
        }
        
        .password-requirements .req {
            font-size: 11px;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s ease;
        }
        
        .password-requirements .req i {
            font-size: 10px;
            width: 14px;
        }
        
        .password-requirements .req.met {
            color: #00ffa6;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            color: #a0aab4;
            cursor: pointer;
            padding: 5px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: #00d4ff;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .checkbox-group a {
            color: #00d4ff;
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        /* Submit Button */
        .btn-primary {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #00d4ff, #00ffa6);
            color: #0b1224;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.25);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-primary .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(11, 18, 36, 0.1);
            border-top-color: #0b1224;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        .btn-primary.loading .spinner {
            display: inline-block;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* =========================
           SECURITY NOTICE
        ========================= */
        .security-notice {
            margin-top: 20px;
            padding: 14px 16px;
            background: rgba(255, 165, 0, 0.05);
            border: 1px solid rgba(255, 165, 0, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .security-notice i {
            color: #ffa500;
            font-size: 18px;
            margin-top: 1px;
        }
        
        .security-notice .notice-content {
            font-size: 12px;
            color: #a0aab4;
            line-height: 1.6;
        }
        
        .security-notice .notice-content strong {
            color: #ffffff;
        }
        
        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 520px) {
            .setup-card {
                padding: 25px 20px;
            }
            
            .setup-header h1 {
                font-size: 24px;
            }
            
            .password-requirements {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<!-- ============================================================
     PARTICLES BACKGROUND
     ============================================================ -->
<div class="particles" id="particles">
    <?php for($i = 0; $i < 25; $i++): ?>
        <div class="particle" style="
            left: <?php echo rand(0, 100); ?>%;
            animation-delay: <?php echo rand(0, 10); ?>s;
            animation-duration: <?php echo rand(8, 18); ?>s;
            width: <?php echo rand(2, 5); ?>px;
            height: <?php echo rand(2, 5); ?>px;
            opacity: <?php echo rand(1, 5) / 10; ?>;
        "></div>
    <?php endfor; ?>
</div>

<!-- ============================================================
     SETUP CONTAINER
     ============================================================ -->
<div class="setup-container">
    
    <div class="setup-card">
        
        <!-- =========================
             HEADER
             ========================= -->
        <div class="setup-header">
            <div class="setup-logo">
                <i class="fas fa-cogs"></i>
            </div>
            <h1>LGK Tech Setup</h1>
            <p>Create your administrator account</p>
            <span class="step-indicator">
                <i class="fas fa-check-circle"></i> Step 1 of 1
            </span>
        </div>
        
        <!-- =========================
             DATABASE STATUS
             ========================= -->
        <?php if ($db_connected): ?>
            <div class="db-status success">
                <i class="fas fa-check-circle"></i>
                <span>Database connection established successfully.</span>
            </div>
        <?php else: ?>
            <div class="db-status error">
                <i class="fas fa-exclamation-circle"></i>
                <span>Database connection failed. Please check your configuration.</span>
            </div>
        <?php endif; ?>
        
        <!-- =========================
             MESSAGES
             ========================= -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Setup Error:</strong>
                    <br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Setup Complete!</strong>
                    <br>
                    <?php echo $success; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- =========================
             FORM
             ========================= -->
        <form method="POST" id="setupForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($full_name); ?>"
                        required
                    >
                </div>
            </div>
            
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-user-tag input-icon"></i>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Choose a username (min 3 characters)"
                        value="<?php echo htmlspecialchars($username); ?>"
                        required
                        minlength="3"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                    >
                </div>
                <div style="font-size:11px;color:#4a5568;margin-top:4px;">
                    <i class="fas fa-info-circle"></i> Letters, numbers, and underscores only
                </div>
            </div>
            
            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                    >
                </div>
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Choose a strong password"
                        required
                        minlength="8"
                    >
                    <span class="toggle-pass" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </span>
                </div>
                
                <!-- Password Strength -->
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="bar" id="strengthBar"></div>
                    </div>
                    <span class="strength-text" id="strengthText">Weak</span>
                </div>
                
                <!-- Password Requirements -->
                <div class="password-requirements" id="passwordRequirements">
                    <div class="req" id="req-length">
                        <i class="fas fa-circle"></i> At least 8 characters
                    </div>
                    <div class="req" id="req-uppercase">
                        <i class="fas fa-circle"></i> One uppercase letter
                    </div>
                    <div class="req" id="req-lowercase">
                        <i class="fas fa-circle"></i> One lowercase letter
                    </div>
                    <div class="req" id="req-number">
                        <i class="fas fa-circle"></i> One number
                    </div>
                    <div class="req" id="req-special">
                        <i class="fas fa-circle"></i> One special character
                    </div>
                </div>
            </div>
            
            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm">Confirm Password <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="confirm" 
                        name="confirm" 
                        placeholder="Confirm your password"
                        required
                    >
                    <span class="toggle-pass" onclick="togglePassword('confirm')">
                        <i class="fas fa-eye" id="confirm-icon"></i>
                    </span>
                </div>
            </div>
            
            <!-- Terms -->
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="agree_terms" value="1" required>
                    <span>
                        I agree to the 
                        <a href="#" onclick="alert('Terms and conditions will be displayed here.'); return false;">terms and conditions</a>
                        and acknowledge that this account will have full administrative access.
                    </span>
                </label>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn-primary" id="submitBtn">
                <span class="spinner"></span>
                <span class="btn-text">
                    <i class="fas fa-shield-alt"></i> Create Admin Account
                </span>
            </button>
            
        </form>
        
        <!-- =========================
             SECURITY NOTICE
             ========================= -->
        <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            <div class="notice-content">
                <strong>Security Notice:</strong> This is the only time you can create the admin account. 
                Please use a strong password and keep it safe. Once created, this setup page will be disabled.
                <br><br>
                <span style="color:#4a5568;font-size:11px;">
                    <i class="fas fa-info-circle"></i> The setup page will be locked after successful completion.
                </span>
            </div>
        </div>
        
    </div>
    
</div>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
// ============================================================
// TOGGLE PASSWORD VISIBILITY
// ============================================================
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============================================================
// PASSWORD STRENGTH CHECKER
// ============================================================
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    
    // Check requirements
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    // Update requirement indicators
    document.getElementById('req-length').className = 'req' + (hasLength ? ' met' : '');
    document.getElementById('req-uppercase').className = 'req' + (hasUppercase ? ' met' : '');
    document.getElementById('req-lowercase').className = 'req' + (hasLowercase ? ' met' : '');
    document.getElementById('req-number').className = 'req' + (hasNumber ? ' met' : '');
    document.getElementById('req-special').className = 'req' + (hasSpecial ? ' met' : '');
    
    // Calculate strength
    let strength = 0;
    if (hasLength) strength++;
    if (hasUppercase) strength++;
    if (hasLowercase) strength++;
    if (hasNumber) strength++;
    if (hasSpecial) strength++;
    if (password.length >= 12) strength++;
    
    // Update bar
    const percentages = {
        0: 0,
        1: 20,
        2: 40,
        3: 60,
        4: 80,
        5: 95,
        6: 100
    };
    
    const percent = percentages[Math.min(strength, 6)] || 0;
    bar.style.width = percent + '%';
    
    // Update color and text
    const colors = {
        0: '#ff4d4d',
        1: '#ff4d4d',
        2: '#ffa500',
        3: '#ffd700',
        4: '#00d4ff',
        5: '#00ffa6',
        6: '#00ffa6'
    };
    
    const labels = {
        0: 'Very Weak',
        1: 'Weak',
        2: 'Fair',
        3: 'Good',
        4: 'Strong',
        5: 'Very Strong',
        6: 'Excellent'
    };
    
    bar.style.background = colors[Math.min(strength, 6)] || '#ff4d4d';
    text.textContent = labels[Math.min(strength, 6)] || 'Weak';
    text.style.color = colors[Math.min(strength, 6)] || '#ff4d4d';
});

// ============================================================
// CONFIRM PASSWORD MATCH
// ============================================================
document.getElementById('confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if (confirm.length > 0 && password !== confirm) {
        this.style.borderColor = '#ff4d4d';
        this.style.boxShadow = '0 0 0 3px rgba(255,77,77,0.1)';
    } else {
        this.style.borderColor = '';
        this.style.boxShadow = '';
    }
});

// ============================================================
// FORM SUBMISSION HANDLING
// ============================================================
document.getElementById('setupForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;
    const terms = document.querySelector('input[name="agree_terms"]');
    
    // Validate passwords match
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match. Please check and try again.');
        return false;
    }
    
    // Validate terms
    if (!terms.checked) {
        e.preventDefault();
        alert('Please agree to the terms and conditions.');
        return false;
    }
    
    // Disable button and show loading
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.classList.add('loading');
    btn.querySelector('.btn-text').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    
    // Re-enable after 30 seconds (safety)
    setTimeout(() => {
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.querySelector('.btn-text').innerHTML = '<i class="fas fa-shield-alt"></i> Create Admin Account';
    }, 30000);
});

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter to submit
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        document.getElementById('setupForm').dispatchEvent(new Event('submit'));
    }
});

// ============================================================
// CAPSLOCK WARNING
// ============================================================
document.getElementById('password').addEventListener('keydown', function(e) {
    if (e.getModifierState('CapsLock')) {
        const warning = this.parentElement.querySelector('.capslock-warning');
        if (!warning) {
            const el = document.createElement('span');
            el.className = 'capslock-warning';
            el.style.cssText = `
                position: absolute;
                right: 50px;
                top: 50%;
                transform: translateY(-50%);
                color: #ffa500;
                font-size: 10px;
                background: rgba(255,165,0,0.1);
                padding: 2px 8px;
                border-radius: 4px;
            `;
            el.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Caps Lock';
            this.parentElement.appendChild(el);
        }
    } else {
        const warning = this.parentElement.querySelector('.capslock-warning');
        if (warning) warning.remove();
    }
});

// ============================================================
// REMOVE CAPSLOCK WARNING ON BLUR
// ============================================================
document.getElementById('password').addEventListener('blur', function() {
    const warning = this.parentElement.querySelector('.capslock-warning');
    if (warning) warning.remove();
});

console.log('🚀 LGK Tech Solutions Setup loaded successfully!');
</script>

</body>
</html>