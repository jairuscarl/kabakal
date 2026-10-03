<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/User.php';

redirectIfLoggedIn();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    
    if (!checkRateLimit('register', 5, 300)) {
        $errors[] = "Too many registration attempts. Please try again later.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        
        // Validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        } elseif (strlen($full_name) < 2 || strlen($full_name) > 100) {
            $errors[] = "Full name must be between 2 and 100 characters.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (!validatePassword($password)) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        if (empty($errors)) {
            $userModel = new User($GLOBALS['db']);
            
            if ($userModel->usernameExists($username)) {
                $errors[] = "Username already exists. Please choose another.";
            } elseif ($userModel->emailExists($email)) {
                $errors[] = "Email already registered. Please use another email or login.";
            } else {
                $user_id = $userModel->create($username, $email, $password, $full_name);
                
                if ($user_id) {
                    enforceAdminRoleByEmail($GLOBALS['db'], $user_id, $email);
                    
                    // Generate verification token
                    $token = bin2hex(random_bytes(32));
                    $userModel->setVerificationToken($user_id, $token);
                    
                    // Send verification email
                    require_once 'includes/mailer.php';
                    $emailSent = sendVerificationEmail($email, $full_name, $token);
                    
                    logActivity($GLOBALS['db'], $user_id, 'registration', 'User registered successfully');
                    
                    if ($emailSent) {
                        setFlashMessage('success', 'Registration successful! Please check your email to verify your account before logging in.');
                    } else {
                        setFlashMessage('warning', 'Registration successful but we couldn\'t send a verification email. Please contact support or try logging in later. Note: Verify SMTP credentials in .env');
                    }
                    redirect('login.php');
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
    }
}

$page_title = 'Create Account';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="public/images/logo.png" alt="NetSkill" width="48" height="48" class="auth-logo-img" style="object-fit:contain; max-width:100%; max-height:100%;">
            </div>
            <h1>Join NetSkill</h1>
            <p>Create your account to start learning</p>
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
                <label for="full_name">Full Name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                           required placeholder="Enter your full name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <i class="fas fa-at"></i>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required placeholder="Choose a username" pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required placeholder="Enter your email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" 
                           required placeholder="Create a password" minlength="6">
                </div>
                <small class="form-hint">Must be at least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required placeholder="Confirm your password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

            <div style="text-align:center; margin: 14px 0; color:#888; font-size:14px;">or</div>

            <a href="auth/google-login.php" class="google-login-btn" aria-label="Sign up with Google">
                <span class="google-logo" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.23 3.6l6.9-6.9C35.95 2.33 30.37 0 24 0 14.62 0 6.51 5.38 2.56 13.22l8.03 6.24C12.5 13.26 17.78 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.5 24.55c0-1.63-.15-3.2-.41-4.73H24v9.05h12.65c-.56 3.01-2.25 5.56-4.8 7.28l7.37 5.73c4.31-3.97 6.78-9.83 6.78-17.33z"/>
                        <path fill="#FBBC05" d="M10.59 28.54A14.48 14.48 0 0 1 9.5 24c0-1.57.27-3.09.75-4.54l-8.03-6.24A23.96 23.96 0 0 0 0 24c0 3.88.93 7.55 2.58 10.78l8.01-6.24z"/>
                        <path fill="#34A853" d="M24 48c6.37 0 11.72-2.09 15.62-5.69l-7.37-5.73c-2.05 1.37-4.67 2.17-8.25 2.17-6.22 0-11.5-3.76-13.41-9.04l-8.01 6.24C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                </span>
                <span>Sign up with Google</span>
            </a>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <div class="auth-features">
        <div class="feature">
            <i class="fas fa-video"></i>
            <span>HD Video Lessons</span>
        </div>
        <div class="feature">
            <i class="fas fa-certificate"></i>
            <span>Earn Certificates</span>
        </div>
        <div class="feature">
            <i class="fas fa-chart-line"></i>
            <span>Track Progress</span>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>