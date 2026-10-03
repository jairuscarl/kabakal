<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/User.php';
require_once 'models/Course.php';
require_once 'models/Certificate.php';

// Require authentication
requireAuth();

$userModel = new User($GLOBALS['db']);
$courseModel = new Course($GLOBALS['db']);
$certificateModel = new Certificate($GLOBALS['db']);

$user = getCurrentUser();
$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (!validateEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Handle avatar upload
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, ALLOWED_IMAGE_TYPES)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = "Image must be less than 5MB.";
        } else {
            if (!file_exists(UPLOAD_DIR . 'avatars/')) {
                mkdir(UPLOAD_DIR . 'avatars/', 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $avatar_path = 'avatar_' . $user['id'] . '_' . uniqid() . '.' . $extension;
            
            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . 'avatars/' . $avatar_path)) {
                // Update session
                $_SESSION['profile_image'] = $avatar_path;
            } else {
                $errors[] = "Failed to upload avatar.";
                $avatar_path = null;
            }
        }
    }
    
    if (empty($errors)) {
        $update_success = $userModel->updateProfile($user['id'], $full_name);
        if ($avatar_path) {
            $userModel->updateProfileImage($user['id'], $avatar_path);
        }
        if ($update_success) {
            // Update session
            $_SESSION['full_name'] = $full_name;
            $user = getCurrentUser();
            if ($avatar_path) {
                $_SESSION['profile_image'] = $avatar_path;
            }
            $success = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile.";
        }
    }
}

$userCertificates = $certificateModel->getUserCertificates($user['id']);
$inProgressCourses = $courseModel->getInProgressCourses($user['id']);

$page_title = 'My Profile';
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
        
        <div class="profile-layout">
            <!-- Profile Sidebar -->
            <div>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <img src="public/uploads/avatars/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: var(--spacing-md);"
                             onerror="this.onerror=null;this.src='public/images/default-avatar.png'">
                        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p style="color: var(--text-secondary);">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p style="color: var(--text-muted); font-size: var(--text-sm);"><?php echo htmlspecialchars($user['email']); ?></p>
                        <hr style="border-color: var(--glass-border); margin: var(--spacing-lg) 0;">
                        <div style="display: flex; justify-content: space-around;">
                            <div>
                                <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary-light);"><?php echo count($userCertificates); ?></div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Certificates</div>
                            </div>
                            <div>
                                <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--primary-light);"><?php echo count($inProgressCourses); ?></div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">In Progress</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Form -->
            <div>
                <div class="card">
                    <div class="card-body">
                        <h3>Edit Profile</h3>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?php echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">'; ?>
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small style="color: var(--text-muted);">Username cannot be changed.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="avatar">Profile Picture</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="form-control" style="padding: var(--spacing-sm);">
                                <img id="avatarPreview" style="display: none; width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-top: var(--spacing-sm); border: 2px solid var(--primary);">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                avatarPreview.style.display = 'none';
            }
        });
    }
});
</script>
<?php include 'includes/footer.php'; ?>
