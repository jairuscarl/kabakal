<?php
require_once 'config/config.php';
require_once 'models/Course.php';
require_once 'models/User.php';
require_once 'models/Certificate.php';

// Initialize models
$courseModel = new Course($GLOBALS['db']);
$userModel = new User($GLOBALS['db']);
$certificateModel = new Certificate($GLOBALS['db']);

// Get statistics
$totalUsers = $userModel->getCount();
$totalCourses = $courseModel->getCount();
$totalCertificates = $certificateModel->getCount();
$totalCompletions = $courseModel->getCompletedCount();

$page_title = 'About Us';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 40vh;">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-info-circle"></i> About Us
            </span>
            <h1>About NetSkill</h1>
            <p>Empowering the next generation of networking professionals through quality education</p>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Mission</h2>
            <p class="section-subtitle">Making networking education accessible to everyone</p>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <p style="color: var(--text-secondary); line-height: 1.8; font-size: var(--text-lg);">
                NetSkill is a comprehensive online learning platform dedicated to helping students and professionals 
                master computer networking. From fundamental concepts to advanced Cisco certifications, we provide 
                high-quality video tutorials, hands-on practice materials, and industry-recognized certificates 
                to help you advance your IT career.
            </p>
        </div>
    </div>
</section>

<!-- Demo Reel Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Demo Reel</h2>
            <p class="section-subtitle">See what we're about</p>
        </div>
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <iframe 
                    src="https://www.youtube.com/embed/MR1DRREqkt4" 
                    title="NetSkill Demo Reel" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Students Enrolled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalCourses); ?></div>
                <div class="stat-label">Courses Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalCompletions); ?></div>
                <div class="stat-label">Courses Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalCertificates); ?></div>
                <div class="stat-label">Certificates Issued</div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose Us</h2>
            <p class="section-subtitle">What sets NetSkill apart</p>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-video"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">HD Video Tutorials</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Professionally produced video lessons with clear explanations and real-world demonstrations
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-certificate"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Certificates</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Earn verifiable certificates upon course completion to showcase your skills
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Progress Tracking</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Monitor your learning journey with detailed progress tracking
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-headset"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Community Support</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Join a community of learners and get help when you need it
                </p>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>