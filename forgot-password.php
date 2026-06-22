<?php
// admin/forgot-password.php - Password reset request

session_start();
include "../config/database.php";

$message = "";
$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Save token
            $insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at, ip_address) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $email, $token, $expires, $ip);
            $insert->execute();
            
            // Send email (simplified - use PHPMailer for production)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/lgk/admin/reset-password.php?token=" . $token;
            $subject = "Password Reset - LGK Tech Solutions";
            $body = "Hello " . $row['username'] . ",\n\n";
            $body .= "You requested a password reset. Click the link below to reset your password:\n\n";
            $body .= $reset_link . "\n\n";
            $body .= "This link will expire in 1 hour.\n\n";
            $body .= "If you didn't request this, please ignore this email.\n\n";
            $body .= "Best regards,\nLGK Tech Solutions Team";
            
            mail($email, $subject, $body, "From: noreply@lgktech.com");
            
            $message = "Password reset link has been sent to your email address.";
        } else {
            // Don't reveal if email exists or not (security)
            $message = "If the email exists, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LGK Admin</title>
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
    </style>
</head>
<body>
<div class="reset-wrapper">
    <div class="reset-card">
        <div class="reset-header">
            <h2><i class="fas fa-key"></i> Forgot Password</h2>
            <p>Enter your email to receive a reset link</p>
        </div>

        <?php if ($message): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</div>
</body>
</html>