<?php
// Main Configuration File for NetSkill
session_start();

// Load environment variables from .env (if available)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;

    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site Configuration
define('SITE_NAME', 'NetSkill');
define('SITE_URL', '/');
define('SITE_EMAIL', 'ephraimpalma100@gmail.com');

if (!function_exists('env')) {
    function env($key, $default = null) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Security Configuration
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('PASSWORD_MIN_LENGTH', 6);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('Asia/Manila');

// Helper Functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function asset_url($path) {
    $path = ltrim($path, '/');
    if (preg_match('#^(https?:)?//#', $path) || strpos($path, 'data:') === 0 || strpos($path, '#') === 0) {
        return $path;
    }

    $siteUrl = rtrim(SITE_URL, '/');
    $url = ($siteUrl === '' ? '' : $siteUrl) . '/' . $path;
    $file = __DIR__ . '/../' . $path;
    $version = file_exists($file) ? filemtime($file) : time();
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . 'v=' . $version;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getAdminEmails() {
    return [
        'jairuscarlesteban@gmail.com',
        'ephraimpalma100@gmail.com'
    ];
}

function enforceAdminRoleByEmail($conn, $userId, $email) {
    if (!$conn || !$userId || !$email) {
        return;
    }

    $normalized = strtolower(trim($email));
    if (!in_array($normalized, getAdminEmails(), true)) {
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'profile_image' => $_SESSION['profile_image'] ?? 'default-avatar.png'
    ];
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatDateTime($date) {
    return date('F j, Y g:i A', strtotime($date));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

// Initialize database if not exists
// Uncomment the line below to initialize the database on first run
// initializeDatabase();

// Get database connection
$GLOBALS['db'] = getDBConnection();

if (!$GLOBALS['db']) {
    die("Database connection failed. Please check your configuration.");
}
?>