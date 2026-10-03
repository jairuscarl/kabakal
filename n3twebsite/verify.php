<?php
/**
 * Email verification endpoint.
 * User clicks the link from their email: /verify.php?token=XXXX
 */
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/User.php';
require_once 'includes/mailer.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$message = '';
$messageType = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    $userModel = new User($GLOBALS['db']);
    $result = $userModel->verifyByToken($token);
    
    if ($result['success']) {
        // Send success notification email
        $user = $userModel->getById($result['user_id']);
        if ($user) {
            sendVerificationSuccessEmail($user['email'], $user['full_name']);
        }
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
} else {
    $message = 'No verification token provided.';
    $messageType = 'error';
}

$page_title = 'Email Verification';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card" style="text-align:center;">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="public/images/logo.png" alt="NetSkill" width="48" height="48" class="auth-logo-img" style="object-fit:contain;">
            </div>
            <h1>Email Verification</h1>
        </div>
        
        <div style="padding:30px 0;">
            <?php if ($messageType === 'success'): ?>
                <div style="font-size:72px;margin-bottom:16px;">✅</div>
                <h2 style="color:var(--success);margin-bottom:12px;">Verified!</h2>
                <p style="color:var(--text-secondary);font-size:16px;margin-bottom:24px;"><?php echo htmlspecialchars($message); ?></p>
                <a href="login.php" class="btn btn-primary" style="text-decoration:none;">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            <?php elseif ($messageType === 'error'): ?>
                <div style="font-size:72px;margin-bottom:16px;">❌</div>
                <h2 style="color:var(--error);margin-bottom:12px;">Verification Failed</h2>
                <p style="color:var(--text-secondary);font-size:16px;margin-bottom:24px;"><?php echo htmlspecialchars($message); ?></p>
                <a href="register.php" class="btn btn-primary" style="text-decoration:none;">
                    <i class="fas fa-user-plus"></i> Register Again
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>