<?php
// admin/login.php - Complete admin login with security

session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admindashboard.php");
    exit();
}

include "../config/database.php";

$error = "";
$username = "";
$logout_message = "";

// Check for logout success
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $logout_message = "You have been logged out successfully.";
}

// Check remember me cookie
if (isset($_COOKIE['admin_remember']) && !empty($_COOKIE['admin_remember'])) {
    $token = $_COOKIE['admin_remember'];
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE remember_token = ? AND status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_username'] = $row['username'];
        $_SESSION['admin_role'] = $row['role'];
        
        // Update last login
        $update = $conn->prepare("UPDATE admin_users SET last_login = NOW(), last_ip = ? WHERE id = ?");
        $ip = $_SERVER['REMOTE_ADDR'];
        $update->bind_param("si", $ip, $row['id']);
        $update->execute();
        
        // Log activity
        logActivity($row['id'], $row['username'], 'auto_login', 'Auto-login via remember token');
        
        header("Location: admindashboard.php");
        exit();
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    $check = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                             WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $check->bind_param("s", $ip);
    $check->execute();
    $result = $check->get_result();
    $data = $result->fetch_assoc();
    
    if ($data['attempts'] >= 5) {
        $error = "Too many login attempts. Please wait 15 minutes.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;
        
        // CSRF validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Invalid request. Please try again.";
        } elseif (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, role, status FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if ($row['status'] !== 'active') {
                    $error = "Account is inactive. Please contact support.";
                } elseif (password_verify($password, $row['password'])) {
                    // Login successful
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    $_SESSION['admin_role'] = $row['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Update last login
                    $update = $conn->prepare("UPDATE admin_users SET last_login = NOW(), last_ip = ? WHERE id = ?");
                    $update->bind_param("si", $ip, $row['id']);
                    $update->execute();
                    
                    // Remember me
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $update_token = $conn->prepare("UPDATE admin_users SET remember_token = ? WHERE id = ?");
                        $update_token->bind_param("si", $token, $row['id']);
                        $update_token->execute();
                        setcookie('admin_remember', $token, time() + 86400 * 30, '/', '', false, true);
                    }
                    
                    // Clear login attempts
                    $clear = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                    $clear->bind_param("s", $ip);
                    $clear->execute();
                    
                    // Log activity
                    logActivity($row['id'], $row['username'], 'login', 'Successful login');
                    
                    header("Location: admindashboard.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            
            // Log failed attempt
            $log = $conn->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
            $log->bind_param("ss", $username, $ip);
            $log->execute();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LGK Tech Solutions</title>
    <link rel="stylesheet" href="../modern-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0b1224, #1a1a2e);
            padding: 20px;
        }
        .login-wrapper { width: 100%; max-width: 420px; }
        .login-card {
            padding: 40px 35px;
            border-radius: 20px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(0,255,166,0.1));
            border: 1px solid rgba(0,212,255,0.15);
            margin-bottom: 15px;
            font-size: 32px;
            color: #00d4ff;
        }
        .login-header h2 { color: #ffffff; font-size: 24px; margin-bottom: 5px; }
        .login-header p { color: #a0aab4; font-size: 14px; }
        .form-group { margin-bottom: 18px; }
        .input-group { position: relative; }
        .input-group .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 14px;
            color: #4a5568;
        }
        .input-group input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .input-group input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
        }
        .input-group input::placeholder { color: #4a5568; }
        .input-group .toggle-pass {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 14px;
            color: #4a5568;
            cursor: pointer;
        }
        .input-group .toggle-pass:hover { color: #00d4ff; }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a0aab4;
            font-size: 13px;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00d4ff;
            cursor: pointer;
        }
        .forgot-link {
            color: #a0aab4;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .forgot-link:hover { color: #00d4ff; }
        .login-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
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
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,212,255,0.3);
        }
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .error-box {
            background: rgba(255,77,77,0.1);
            border: 1px solid rgba(255,77,77,0.2);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ff4d4d;
            font-size: 13px;
        }
        .success-box {
            background: rgba(0,255,166,0.1);
            border: 1px solid rgba(0,255,166,0.2);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #00ffa6;
            font-size: 13px;
        }
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .login-footer p { color: #4a5568; font-size: 12px; }
        .login-footer a { color: #00d4ff; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .login-card { padding: 30px 20px; }
            .login-logo { width: 60px; height: 60px; font-size: 28px; }
            .form-options { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo"><i class="fas fa-cogs"></i></div>
            <h2>Welcome Back</h2>
            <p>Sign in to your admin dashboard</p>
        </div>

        <?php if ($logout_message): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($logout_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input id="password" name="password" type="password" placeholder="Password" required>
                    <i class="fas fa-eye toggle-pass" onclick="togglePass()"></i>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-group">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot-password.php" class="forgot-link">
                    <i class="fas fa-key"></i> Forgot password?
                </a>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="../index.php">LGK Tech Solutions</a>. All rights reserved.</p>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon = document.querySelector('.toggle-pass');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash toggle-pass';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye toggle-pass';
    }
}
</script>
</body>
</html>