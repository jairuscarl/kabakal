<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/User.php';

redirectIfLoggedIn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    
    if (!checkRateLimit('login', 10, 300)) {
        $errors[] = "Too many login attempts. Please try again later.";
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        }
        
        if (empty($errors)) {
            $userModel = new User($GLOBALS['db']);
            $user = $userModel->getByEmail($email);
            
            if ($user && $userModel->verifyPassword($password, $user['password'])) {
                // Check if email is verified
                if (!$user['is_verified']) {
                    $errors[] = "Please verify your email before logging in. Check your inbox for the verification link.";
                } else {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['profile_image'] = $user['profile_image'];
                    
                    // Update last login
                    $userModel->updateLastLogin($user['id']);
                    
                    // Log activity
                    logActivity($GLOBALS['db'], $user['id'], 'login', 'User logged in successfully');
                    
                    // Set remember me cookie if checked
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/');
                        
                        $stmt = $GLOBALS['db']->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                        $stmt->bind_param("is", $user['id'], $token);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
                    
                    if ($user['role'] === 'admin') {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('index.php');
                    }
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="public/images/logo.png" alt="NetSkill" width="48" height="48" class="auth-logo-img" style="object-fit:contain; max-width:100%; max-height:100%;">
            </div>
            <h1>Welcome Back</h1>
            <p>Login to continue learning</p>
        </div>
        
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
        
        <form method="POST" action="" class="auth-form">
            <?php echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">'; ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="Enter your email" autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password">
                </div>
            </div>
            
            <div class="form-group form-check">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span class="checkmark"></span>
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

            <div style="text-align:center; margin: 14px 0; color:#888; font-size:14px;">or</div>

            <a href="auth/google-login.php" class="google-login-btn" aria-label="Login with Google">
                <span class="google-logo" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.23 3.6l6.9-6.9C35.95 2.33 30.37 0 24 0 14.62 0 6.51 5.38 2.56 13.22l8.03 6.24C12.5 13.26 17.78 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.5 24.55c0-1.63-.15-3.2-.41-4.73H24v9.05h12.65c-.56 3.01-2.25 5.56-4.8 7.28l7.37 5.73c4.31-3.97 6.78-9.83 6.78-17.33z"/>
                        <path fill="#FBBC05" d="M10.59 28.54A14.48 14.48 0 0 1 9.5 24c0-1.57.27-3.09.75-4.54l-8.03-6.24A23.96 23.96 0 0 0 0 24c0 3.88.93 7.55 2.58 10.78l8.01-6.24z"/>
                        <path fill="#34A853" d="M24 48c6.37 0 11.72-2.09 15.62-5.69l-7.37-5.73c-2.05 1.37-4.67 2.17-8.25 2.17-6.22 0-11.5-3.76-13.41-9.04l-8.01 6.24C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                </span>
                <span>Login with Google</span>
            </a>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create one</a></p>
        </div>
    </div>
    
    <div class="auth-features">
        <div class="feature">
            <i class="fas fa-shield-alt"></i>
            <span>Secure Login</span>
        </div>
        <div class="feature">
            <i class="fas fa-clock"></i>
            <span>24/7 Access</span>
        </div>
        <div class="feature">
            <i class="fas fa-sync"></i>
            <span>Sync Progress</span>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>