<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/Certificate.php';

// Require authentication
requireAuth();

$certificateModel = new Certificate($GLOBALS['db']);
$user = getCurrentUser();

// Get user's certificates
$certificates = $certificateModel->getUserCertificates($user['id']);

$page_title = 'My Certificates';
include 'includes/header.php';
?>

<section class="hero" style="min-height: 30vh;">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-certificate"></i> Achievements
            </span>
            <h1>My Certificates</h1>
            <p>View and download your earned course certificates</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($certificates)): ?>
            <div class="empty-state">
                <i class="fas fa-certificate"></i>
                <h3>No certificates yet</h3>
                <p>Complete courses to earn certificates and showcase your skills!</p>
                <a href="tutorials.php" class="btn btn-primary" style="margin-top: var(--spacing-lg);">
                    <i class="fas fa-graduation-cap"></i> Browse Courses
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: var(--spacing-xl);">
                <?php foreach ($certificates as $cert): ?>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 4rem; color: var(--warning); margin-bottom: var(--spacing-md);">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cert['course_name']); ?></h3>
                        <p style="color: var(--text-secondary);">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($cert['user_name']); ?>
                        </p>
                        <p style="color: var(--text-muted); font-size: var(--text-sm);">
                            <i class="fas fa-calendar"></i> Issued: <?php echo formatDate($cert['issued_date']); ?>
                        </p>
                        <p style="color: var(--primary-light); font-size: var(--text-xs); font-family: var(--font-mono);">
                            <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($cert['certificate_code']); ?>
                        </p>
                        <hr style="border-color: var(--glass-border); margin: var(--spacing-lg) 0;">
                        <div style="display: flex; gap: var(--spacing-md);">
                            <a href="certificate.php?code=<?php echo urlencode($cert['certificate_code']); ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button onclick="downloadCertificate(<?php echo $cert['id']; ?>)" class="btn btn-secondary btn-sm" style="flex: 1;">
                                <i class="fas fa-download"></i> Download
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>