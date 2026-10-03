<?php
/**
 * Simple test to see what error is occurring
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Testing PHP Execution</h1>";
echo "<pre>";

// Test 1: Basic PHP
echo "1. Basic PHP: OK\n";

// Test 2: Try including config
echo "\n2. Including config/config.php...\n";
try {
    include 'config/config.php';
    echo "   ✓ Config included successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error including config: " . $e->getMessage() . "\n";
}

// Test 3: Check if models can be loaded
echo "\n3. Testing model loading...\n";
if (file_exists('models/User.php')) {
    echo "   ✓ User.php exists\n";
} else {
    echo "   ✗ User.php not found\n";
}

// Test 4: Check includes
echo "\n4. Testing includes...\n";
if (file_exists('includes/header.php')) {
    echo "   ✓ header.php exists\n";
} else {
    echo "   ✗ header.php not found\n";
}

if (file_exists('includes/footer.php')) {
    echo "   ✓ footer.php exists\n";
} else {
    echo "   ✗ footer.php not found\n";
}

// Test 5: Try to access database
echo "\n5. Testing database...\n";
if (isset($GLOBALS['db']) && $GLOBALS['db']) {
    echo "   ✓ Database connection active\n";
    $result = $GLOBALS['db']->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   ✓ Users count: " . $row['count'] . "\n";
    }
} else {
    echo "   ✗ No database connection\n";
}

echo "\n</pre>";

// Test 6: Try loading index.php content
echo "<hr><h2>Testing index.php inclusion:</h2>";
echo "<pre>";
ob_start();
include 'index.php';
$content = ob_get_clean();
if (!empty($content)) {
    echo "✓ index.php executed successfully\n";
    echo "Content length: " . strlen($content) . " bytes\n";
} else {
    echo "✗ index.php returned empty content\n";
}
echo "</pre>";
?>