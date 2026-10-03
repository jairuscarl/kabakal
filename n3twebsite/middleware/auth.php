<?php
// Authentication Middleware

// Require user to be logged in
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to access this page.');
        redirect(SITE_URL . 'login.php');
    }
}

// Require user to be admin
function requireAdmin() {
    requireAuth();
    
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect(SITE_URL . 'index.php');
    }
}

// Redirect if already logged in
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            redirect(SITE_URL . 'admin/dashboard.php');
        } else {
            redirect(SITE_URL . 'index.php');
        }
    }
}

// Verify user owns the resource
function verifyOwnership($user_id) {
    if (!isLoggedIn() || getCurrentUser()['id'] != $user_id) {
        setFlashMessage('error', 'Access denied. You do not have permission to access this resource.');
        redirect(SITE_URL . 'index.php');
    }
}

// Check CSRF token
function verifyCSRF() {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        setFlashMessage('error', 'Invalid request. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
    }
}

// Rate limiting (simple implementation)
function checkRateLimit($action, $limit = 5, $timeframe = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeframe];
    }
    
    $rate = &$_SESSION[$key];
    
    if (time() > $rate['reset_time']) {
        $rate['count'] = 0;
        $rate['reset_time'] = time() + $timeframe;
    }
    
    $rate['count']++;
    
    if ($rate['count'] > $limit) {
        return false;
    }
    
    return true;
}
?>