<?php
require_once '../includes/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'register') {
            // Registration
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $department = sanitizeInput($_POST['department']);
            $gender = sanitizeInput($_POST['gender']);
            $hobbies = sanitizeInput($_POST['hobbies']);
            $likes = sanitizeInput($_POST['likes']);
            $dislikes = sanitizeInput($_POST['dislikes']);
            
            // Handle profile photo upload
            $profile_photo = '';
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $target_dir = "../assets/uploads/profiles/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $profile_photo = time() . '_' . basename($_FILES['profile_photo']['name']);
                move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_dir . $profile_photo);
            }
            
            // Handle cover photo upload
            $cover_photo = '';
            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
                $target_dir = "../assets/uploads/covers/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $cover_photo = time() . '_' . basename($_FILES['cover_photo']['name']);
                move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_dir . $cover_photo);
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO users (username, email, password, department, gender, hobbies, likes, dislikes, profile_photo, cover_photo) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password, $department, $gender, $hobbies, $likes, $dislikes, $profile_photo, $cover_photo]);
                
                // Create profile record
                $user_id = $db->lastInsertId();
                $stmt = $db->prepare("INSERT INTO profiles (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } catch(PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        } else if ($_POST['action'] == 'login') {
            // Login
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KNP Dating Site</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #800000, #6d0000); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="login-container">
        <h2><i class="fas fa-heart"></i> KNP Dating</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <ul class="nav nav-tabs" style="display: flex; list-style: none; margin-bottom: 20px; border-bottom: 2px solid #eee;">
            <li style="flex: 1; text-align: center; padding: 10px; cursor: pointer;" class="active" onclick="showTab('login')">Login</li>
            <li style="flex: 1; text-align: center; padding: 10px; cursor: pointer;" onclick="showTab('register')">Register</li>
        </ul>
        
        <!-- Login Form -->
        <div id="login-form" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Login</button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div id="register-form" class="tab-content" style="display: none;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hobbies</label>
                    <textarea name="hobbies" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Things you like</label>
                    <textarea name="likes" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Things you dislike</label>
                    <textarea name="dislikes" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Photo (max 5MB)</label>
                    <input type="file" name="profile_photo" accept="image/*" class="image-input">
                </div>
                <div class="form-group">
                    <label>Cover Photo (max 5MB)</label>
                    <input type="file" name="cover_photo" accept="image/*" class="image-input">
                </div>
                <button type="submit" class="btn-success" style="width: 100%;">Register</button>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            if (tab === 'login') {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('register-form').style.display = 'none';
                document.querySelectorAll('.nav-tabs li')[0].classList.add('active');
                document.querySelectorAll('.nav-tabs li')[1].classList.remove('active');
            } else {
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
                document.querySelectorAll('.nav-tabs li')[0].classList.remove('active');
                document.querySelectorAll('.nav-tabs li')[1].classList.add('active');
            }
        }
    </script>
</body>
</html>