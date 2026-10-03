<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/User.php';

// Require authentication
requireAuth();

$userModel = new User($GLOBALS['db']);
$user = getCurrentUser();
$errors = [];
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        $user_data = $userModel->getByEmail($user['email']);
        if (!$userModel->verifyPassword($current_password, $user_data['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (!validatePassword($new_password)) {
            $errors[] = "New password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }
        
        if (empty($errors)) {
            if ($userModel->updatePassword($user['id'], $new_password)) {
                $success = "Password updated successfully!";
            } else {
                $errors[] = "Failed to update password.";
            }
        }
    } elseif ($action === 'account') {
        // Account settings (delete account etc.)
        $success = "Account settings updated.";
    }
}

$page_title = 'Settings';
include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="max-width: 600px; margin: 0 auto;">
            <!-- Change Password -->
            <div class="card" style="margin-bottom: var(--spacing-xl);">
                <div class="card-body">
                    <h3><i class="fas fa-lock" style="color: var(--primary-light);"></i> Change Password</h3>
                    <form method="POST" action="">
                        <?php echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">'; ?>
                        <input type="hidden" name="action" value="password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Account Info -->
            <div class="card">
                <div class="card-body">
                    <h3><i class="fas fa-user-cog" style="color: var(--primary-light);"></i> Account Information</h3>
                    <div style="background: var(--glass-bg); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                    </div>
                    <p style="color: var(--text-muted); font-size: var(--text-sm);">
                        <i class="fas fa-info-circle"></i> 
                        Visit your <a href="profile.php">profile page</a> to update your name and profile picture.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>