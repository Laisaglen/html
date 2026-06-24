<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>KNP Dating Site - System Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .test-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .test-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #800000; }
        .test-result { margin: 5px 0; padding: 5px 10px; }
    </style>
</head>
<body>
    <div class='test-container'>
        <h1 style='color: #800000;'>KNP Dating Site - System Test</h1>
        <p>Testing environment: " . date('Y-m-d H:i:s') . "</p>
        <hr>";

echo "<div class='test-section'>";
echo "<div class='test-title'>1. PHP Configuration</div>";

// PHP Version
echo "<div class='test-result'>";
echo "PHP Version: " . phpversion() . " ";
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "<span class='pass'>✓ OK</span>";
} else {
    echo "<span class='warning'>⚠ PHP 7.4+ recommended</span>";
}
echo "</div>";

// Required Extensions
$extensions = ['pdo_mysql', 'gd', 'fileinfo', 'json', 'session', 'mbstring'];
foreach ($extensions as $ext) {
    echo "<div class='test-result'>";
    echo "Extension: " . $ext . " ";
    if (extension_loaded($ext)) {
        echo "<span class='pass'>✓ Loaded</span>";
    } else {
        echo "<span class='fail'>✗ Missing</span>";
    }
    echo "</div>";
}

// PHP Settings
echo "<div class='test-result'>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . " ";
if (intval(ini_get('upload_max_filesize')) >= 30) {
    echo "<span class='pass'>✓ OK</span>";
} else {
    echo "<span class='warning'>⚠ Should be 30M</span>";
}
echo "</div>";

echo "<div class='test-result'>";
echo "post_max_size: " . ini_get('post_max_size') . " ";
if (intval(ini_get('post_max_size')) >= 30) {
    echo "<span class='pass'>✓ OK</span>";
} else {
    echo "<span class='warning'>⚠ Should be 30M</span>";
}
echo "</div>";

echo "<div class='test-result'>";
echo "Session save path: " . ini_get('session.save_path') . " ";
if (is_writable(ini_get('session.save_path') ?: session_save_path())) {
    echo "<span class='pass'>✓ Writable</span>";
} else {
    echo "<span class='warning'>⚠ Check permissions</span>";
}
echo "</div>";

echo "</div>"; // End PHP Configuration

// Database Test
echo "<div class='test-section'>";
echo "<div class='test-title'>2. Database Connection</div>";

try {
    require_once 'includes/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<div class='test-result'><span class='pass'>✓ Database connection successful</span></div>";
    
    // Check tables
    $tables = ['users', 'profiles', 'posts', 'friends', 'messages', 'market', 'likes', 'shares', 'admin_reports'];
    $all_exist = true;
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='test-result'><span class='pass'>✓ Table '$table' exists</span></div>";
            } else {
                echo "<div class='test-result'><span class='fail'>✗ Table '$table' missing</span></div>";
                $all_exist = false;
            }
        } catch (Exception $e) {
            echo "<div class='test-result'><span class='fail'>✗ Error checking table '$table': " . $e->getMessage() . "</span></div>";
            $all_exist = false;
        }
    }
    
    // Check event scheduler
    $stmt = $db->query("SHOW VARIABLES LIKE 'event_scheduler'");
    $result = $stmt->fetch();
    echo "<div class='test-result'>";
    echo "Event Scheduler: " . ($result['Value'] ?? 'OFF') . " ";
    if (($result['Value'] ?? '') == 'ON') {
        echo "<span class='pass'>✓ Enabled</span>";
    } else {
        echo "<span class='warning'>⚠ Disabled (run: SET GLOBAL event_scheduler = ON;)</span>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result'><span class='fail'>✗ Database connection failed: " . $e->getMessage() . "</span></div>";
}
echo "</div>"; // End Database Test

// Directory Permissions
echo "<div class='test-section'>";
echo "<div class='test-title'>3. Directory Permissions</div>";

$dirs = [
    'assets/uploads/profiles/',
    'assets/uploads/covers/',
    'assets/uploads/posts/',
    'assets/uploads/products/',
    'assets/reports/'
];

foreach ($dirs as $dir) {
    echo "<div class='test-result'>";
    echo "Directory: " . $dir . " ";
    if (file_exists($dir)) {
        echo "<span class='pass'>✓ Exists</span> ";
        if (is_writable($dir)) {
            echo "<span class='pass'>✓ Writable</span>";
        } else {
            echo "<span class='fail'>✗ Not writable</span>";
        }
    } else {
        echo "<span class='fail'>✗ Missing</span>";
        // Try to create it
        if (mkdir($dir, 0777, true)) {
            echo " <span class='pass'>✓ Created</span>";
        } else {
            echo " <span class='fail'>✗ Cannot create</span>";
        }
    }
    echo "</div>";
}
echo "</div>"; // End Directory Permissions

// File Test
echo "<div class='test-section'>";
echo "<div class='test-title'>4. File Upload Test</div>";

$test_file = 'assets/uploads/test.txt';
if (file_put_contents($test_file, 'Test content')) {
    echo "<div class='test-result'><span class='pass'>✓ Can write files</span></div>";
    unlink($test_file);
} else {
    echo "<div class='test-result'><span class='fail'>✗ Cannot write files</span></div>";
}

// Test session
$_SESSION['test'] = 'test_value';
echo "<div class='test-result'>";
echo "Session: ";
if (isset($_SESSION['test']) && $_SESSION['test'] == 'test_value') {
    echo "<span class='pass'>✓ Working</span>";
} else {
    echo "<span class='fail'>✗ Not working</span>";
}
echo "</div>";
echo "</div>"; // End File Test

// Summary
echo "<div class='test-section'>";
echo "<div class='test-title'>5. Summary</div>";
echo "<div class='test-result'><strong>System Status:</strong> ";
if (isset($all_exist) && $all_exist) {
    echo "<span class='pass'>✓ All systems ready</span>";
} else {
    echo "<span class='warning'>⚠ Some issues found - check above</span>";
}
echo "</div>";

echo "<div class='test-result'>";
echo "<strong>Next Steps:</strong><br>";
echo "1. Create admin user if not exists<br>";
echo "2. Set up cron job for event scheduler if not running<br>";
echo "3. Configure email settings for password recovery<br>";
echo "4. Test user registration and login<br>";
echo "5. Test file uploads (images and videos)<br>";
echo "6. Test chat functionality<br>";
echo "7. Test market place features<br>";
echo "8. <strong>IMPORTANT: Delete this test.php file after testing!</strong>";
echo "</div>";

echo "</div>"; // End Summary

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>KNP Dating Site - " . date('Y') . "</p>";
echo "</div></body></html>";
?>