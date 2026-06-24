<?php
require_once '../includes/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - KNP Dating Site</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="text-align: center; padding: 40px;">
        <div style="font-size: 100px; color: #800000;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 style="color: #800000;">Oops! Something went wrong</h1>
        <p style="color: #666; font-size: 18px; margin: 20px 0;">
            <?php 
            $error_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'The page you are looking for could not be found.';
            echo $error_message;
            ?>
        </p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="<?php echo BASE_URL; ?>pages/index.php" class="btn-primary" style="text-decoration: none;">
                <i class="fas fa-home"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn-primary" style="text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</body>
</html>