<?php
/**
 * NetSkill Debug Script
 * Run this to check what's wrong with the installation
 * Access via: http://localhost/N3tSkills/debug.php
 */

echo "<h1>NetSkill Debug Report</h1>";
echo "<pre>";

// 1. Check if we can access config files
echo "1. Checking file access...\n";
if (file_exists('config/config.php')) {
    echo "   ✓ config/config.php exists\n";
} else {
    echo "   ✗ config/config.php NOT found\n";
}

if (file_exists('config/database.php')) {
    echo "   ✓ config/database.php exists\n";
} else {
    echo "   ✗ config/database.php NOT found\n";
}

// 2. Check PHP version
echo "\n2. PHP Version: " . phpversion() . "\n";

// 3. Check extensions
echo "\n3. Checking PHP extensions:\n";
$extensions = ['mysqli', 'json', 'session', 'fileinfo'];
foreach ($extensions as $ext) {
    echo "   " . (extension_loaded($ext) ? "✓" : "✗") . " $ext\n";
}

// 4. Try to connect to MySQL
echo "\n4. Testing MySQL connection:\n";
$conn = @new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    echo "   ✗ Connection failed: " . $conn->connect_error . "\n";
    echo "   Make sure MySQL is running in XAMPP!\n";
} else {
    echo "   ✓ Connected to MySQL successfully\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE 'netskills_db'");
    if ($result->num_rows > 0) {
        echo "   ✓ Database 'netskills_db' exists\n";
        
        // Check tables
        $conn->select_db('netskills_db');
        $tables = ['users', 'courses', 'lessons', 'user_progress', 'completed_courses', 'certificates', 'reviews', 'activity_logs', 'sessions'];
        echo "\n5. Checking tables:\n";
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "   " . ($result->num_rows > 0 ? "✓" : "✗") . " $table\n";
        }
        
        // Check if admin user exists
        echo "\n6. Checking admin user:\n";
        $result = $conn->query("SELECT id, username, email, role FROM users WHERE role = 'admin' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            echo "   ✓ Admin user exists:\n";
            echo "     - ID: " . $admin['id'] . "\n";
            echo "     - Username: " . $admin['username'] . "\n";
            echo "     - Email: " . $admin['email'] . "\n";
        } else {
            echo "   ✗ No admin user found\n";
        }
        
        // Check courses
        echo "\n7. Checking courses:\n";
        $result = $conn->query("SELECT COUNT(*) as count FROM courses");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "   Found " . $row['count'] . " courses\n";
        }
        
    } else {
        echo "   ✗ Database 'netskills_db' does NOT exist\n";
        echo "   Run setup.php to create it!\n";
    }
}

// 5. Check directory permissions
echo "\n8. Checking directory permissions:\n";
$dirs = [
    'public/uploads',
    'public/uploads/avatars',
    'public/uploads/reviews',
    'public/uploads/certificates',
    'logs'
];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "   ✓ $dir exists\n";
    } else {
        echo "   ✗ $dir does NOT exist\n";
    }
}

// 6. Check if .htaccess exists
echo "\n9. Checking .htaccess:\n";
if (file_exists('.htaccess')) {
    echo "   ✓ .htaccess exists\n";
} else {
    echo "   ✗ .htaccess does NOT exist\n";
}

// 7. Check error logs
echo "\n10. Checking for PHP errors:\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include config
echo "\n11. Testing config inclusion:\n";
if (file_exists('config/config.php')) {
    echo "   Attempting to include config/config.php...\n";
    try {
        // Don't actually include it to avoid side effects
        echo "   ✓ config/config.php is readable\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n</pre>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li>If MySQL connection failed: Start MySQL in XAMPP</li>";
echo "<li>If database doesn't exist: Run <a href='setup.php'>setup.php</a></li>";
echo "<li>If tables are missing: Run <a href='setup.php'>setup.php</a></li>";
echo "<li>If directories are missing: Run <a href='setup.php'>setup.php</a> (it creates them)</li>";
echo "<li>If .htaccess is missing: Download it again or create manually</li>";
echo "<li>To test the site: <a href='index.php'>Go to Homepage</a></li>";
echo "<li>To login: <a href='login.php'>Login Page</a> (admin@netskill.com / admin123)</li>";
echo "</ul>";
?>