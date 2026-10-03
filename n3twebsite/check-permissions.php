<!DOCTYPE html>
<html>
<head>
    <title>Check Permissions</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: white; padding: 40px; }
        .container { max-width: 700px; margin: 0 auto; background: #2d2d2d; padding: 30px; border-radius: 10px; }
        h1 { color: #A52A2A; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        a { color: #A52A2A; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Checking Permissions & Configuration</h1>
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "<pre>";
        
        // Check directories
        echo "1. Directory Check:\n";
        $dirs = [
            'public/uploads',
            'public/uploads/avatars',
            'public/uploads/reviews',
            'public/uploads/certificates',
            'logs'
        ];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $writable = is_writable($dir) ? "writable" : "NOT writable";
                echo "   ✓ $dir exists ($writable)\n";
            } else {
                echo "   ✗ $dir does NOT exist\n";
            }
        }
        
        // Test writing to a file
        echo "\n2. Testing file write:\n";
        $testFile = 'logs/test.txt';
        if (file_put_contents($testFile, "Test " . date('Y-m-d H:i:s'))) {
            echo "   ✓ Can write to logs/test.txt\n";
            unlink($testFile);
            echo "   ✓ Test file cleaned up\n";
        } else {
            echo "   ✗ Cannot write to logs/test.txt\n";
            echo "   Fix: Right-click logs folder → Properties → Security → Give Users full control\n";
        }
        
        // Check config files
        echo "\n3. Config Files:\n";
        if (file_exists('config/config.php')) {
            echo "   ✓ config/config.php exists\n";
            if (is_readable('config/config.php')) {
                echo "   ✓ config/config.php is readable\n";
            } else {
                echo "   ✗ config/config.php is NOT readable\n";
            }
        } else {
            echo "   ✗ config/config.php does NOT exist\n";
        }
        
        // Try including config (this might cause error)
        echo "\n4. Testing config inclusion:\n";
        try {
            // Don't actually include to avoid side effects
            echo "   Skipping include test to avoid errors\n";
        } catch (Exception $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
        
        // Check .htaccess
        echo "\n5. .htaccess Check:\n";
        if (file_exists('.htaccess')) {
            echo "   ✓ .htaccess exists\n";
            $content = file_get_contents('.htaccess');
            if (strpos($content, 'RewriteEngine') !== false) {
                echo "   ✓ mod_rewrite rules present\n";
            } else {
                echo "   ⚠ mod_rewrite rules might be missing\n";
            }
        } else {
            echo "   ✗ .htaccess does NOT exist\n";
        }
        
        // PHP info
        echo "\n6. PHP Information:\n";
        echo "   PHP Version: " . phpversion() . "\n";
        echo "   Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
        echo "   Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        echo "   Script: " . __FILE__ . "\n";
        echo "   Current Directory: " . getcwd() . "\n";
        
        // Check if we can connect to database
        echo "\n7. Database Connection:\n";
        $conn = @new mysqli('localhost', 'root', '', 'netskills_db');
        if ($conn->connect_error) {
            echo "   ✗ Connection failed: " . $conn->connect_error . "\n";
        } else {
            echo "   ✓ Connected to database\n";
            $conn->close();
        }
        
        echo "\n</pre>";
        
        echo "<h2>Next Steps:</h2>";
        echo "<ul>";
        echo "<li>If directories are not writable: Right-click folder → Properties → Security → Give 'Users' full control</li>";
        echo "<li>If config files are not readable: Check file permissions</li>";
        echo "<li>If still getting 500 errors: Check <code>C:\\xampp\\apache\\logs\\error.log</code></li>";
        echo "</ul>";
        echo "<p><a href='index.php'>→ Try Homepage</a> | <a href='login.php'>→ Try Login</a></p>";
        ?>
    </div>
</body>
</html>