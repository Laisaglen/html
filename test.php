<?php
echo "Testing KNP Dating Site Setup\n";
echo "============================\n\n";

// Test 1: Database Connection
try {
    require_once 'includes/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Check Tables
$tables = ['users', 'profiles', 'posts', 'friends', 'messages', 'market', 'likes', 'shares', 'admin_reports'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
        echo "✓ Table '$table' exists\n";
    } catch (Exception $e) {
        echo "✗ Table '$table' missing or error: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check Directories
$dirs = ['assets/uploads/profiles/', 'assets/uploads/covers/', 'assets/uploads/posts/', 'assets/uploads/products/', 'assets/reports/'];
foreach ($dirs as $dir) {
    if (file_exists($dir)) {
        echo "✓ Directory '$dir' exists\n";
        if (is_writable($dir)) {
            echo "  ✓ Directory '$dir' is writable\n";
        } else {
            echo "  ✗ Directory '$dir' is not writable\n";
        }
    } else {
        echo "✗ Directory '$dir' missing\n";
    }
}

// Test 4: Check PHP Configuration
echo "\nPHP Configuration:\n";
echo "✓ upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "✓ post_max_size: " . ini_get('post_max_size') . "\n";
echo "✓ max_execution_time: " . ini_get('max_execution_time') . " seconds\n";
echo "✓ memory_limit: " . ini_get('memory_limit') . "\n";
echo "✓ session.save_path: " . ini_get('session.save_path') . "\n";

// Test 5: Check if event scheduler is running
$stmt = $db->query("SHOW VARIABLES LIKE 'event_scheduler'");
$result = $stmt->fetch();
echo "\nEvent Scheduler: " . ($result['Value'] ?? 'OFF') . "\n";
?>