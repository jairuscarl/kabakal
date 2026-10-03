<?php
require_once 'config/config.php';
require_once 'models/User.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Log activity
    logActivity($GLOBALS['db'], $user_id, 'logout', 'User logged out');
    
    // Delete remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Delete from sessions table
        $stmt = $GLOBALS['db']->prepare("DELETE FROM sessions WHERE session_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
        
        // Delete cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Clear all session data (including OAuth-related session values)
    $_SESSION = [];

    // Destroy session
    session_destroy();
}

// Redirect to home
redirect('index.php');
?>