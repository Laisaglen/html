<?php
// admin/reset-password.php - Reset password with token

session_start();
include "../config/database.php";

$token = $_GET['token'] ?? '';
$error = "";
$message = "";
$valid = false;
$email = "";

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $valid = true;
        $email = $row['email'];
    } else {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
        $error = "Password must contain uppercase, lowercase, and number.";
    } else {
        // Update password
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hash, $email);
        $update->execute();
        
        // Mark token as used
        $update_token = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $update_token->bind_param("s", $token);
        $update_token->execute();
        
        $message = "Password reset successfully! You can now login.";
        $valid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LGK Admin</title>
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
        .reset-wrapper { width: 100%; max-width: 420px; }
        .reset-card {
            padding: 40px 35px;
            border-radius: 20px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .reset-header { text-align: center; margin-bottom: 30px; }
        .reset-header h2 { color: #ffffff; font-size: 24px; margin-bottom: 5px; }
        .reset-header p { color: #a0aab4; font-size: 14px; }
        .form-group { margin-bottom: 18px; }
        .form-group input {
            width: 100%;
            padding: 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
        }
        .form-group input::placeholder { color: #4a5568; }
        .btn-primary {
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
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,212,255,0.3);
        }
        .success-box {
            background: rgba(0,255,166,0.1);
            border: 1px solid rgba(0,255,166,0.2);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #00ffa6;
            font-size: 13px;
        }
        .error-box {
            background: rgba(255,77,77,0.1);
            border: 1px solid rgba(255,77,77,0.2);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #ff4d4d;
            font-size: 13px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #a0aab4;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover { color: #00d4ff; }
        .password-requirements {
            font-size: 12px;
            color: #4a5568;
            margin-top: 5px;
        }
        .password-requirements .req { margin: 2px 0; }
        .password-requirements .req.met { color: #00ffa6; }
    </style>
</head>
<body>
<div class="reset-wrapper">
    <div class="reset-card">
        <div class="reset-header">
            <h2><i class="fas fa-lock"></i> Reset Password</h2>
            <p>Enter your new password</p>
        </div>

        <?php if ($message): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
                <br><br>
                <a href="login.php" style="color:#00d4ff;">Click here to login</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($valid && !$message): ?>
            <form method="POST">
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder="New password" required minlength="8">
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>
                </div>
                <div class="password-requirements">
                    <div class="req" id="req-length"><i class="fas fa-circle"></i> At least 8 characters</div>
                    <div class="req" id="req-uppercase"><i class="fas fa-circle"></i> One uppercase letter</div>
                    <div class="req" id="req-lowercase"><i class="fas fa-circle"></i> One lowercase letter</div>
                    <div class="req" id="req-number"><i class="fas fa-circle"></i> One number</div>
                </div>
                <br>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    document.getElementById('req-length').className = 'req' + (password.length >= 8 ? ' met' : '');
    document.getElementById('req-uppercase').className = 'req' + (/[A-Z]/.test(password) ? ' met' : '');
    document.getElementById('req-lowercase').className = 'req' + (/[a-z]/.test(password) ? ' met' : '');
    document.getElementById('req-number').className = 'req' + (/[0-9]/.test(password) ? ' met' : '');
});
</script>
</body>
</html>