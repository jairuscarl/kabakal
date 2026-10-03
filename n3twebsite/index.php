<?php
require_once 'config/config.php';
require_once 'models/Course.php';
require_once 'models/User.php';
require_once 'models/Certificate.php';
require_once 'models/Review.php';

// Initialize models
$courseModel = new Course($GLOBALS['db']);
$userModel = new User($GLOBALS['db']);
$certificateModel = new Certificate($GLOBALS['db']);
$reviewModel = new Review($GLOBALS['db']);

// Get statistics
$totalUsers = $userModel->getCount();
$totalCourses = $courseModel->getCount();
$totalCertificates = $certificateModel->getCount();
$totalCompletions = $courseModel->getCompletedCount();

// Get featured courses
$featuredCourses = $courseModel->getAll(null, null, null);
$featuredCourses = array_slice($featuredCourses, 0, 6);

// Get user's in-progress courses if logged in
$inProgressCourses = [];
if (isLoggedIn()) {
    $user = getCurrentUser();
    $inProgressCourses = $courseModel->getInProgressCourses($user['id']);
}

// Get recent reviews
$recentReviews = $reviewModel->getAll(null, 3, 0);

$page_title = 'Home';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-network" id="heroNetwork"></div>
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-wifi"></i> Online Learning Platform
            </span>
            <h1>Master Computer Networking with NetSkill</h1>
            <p>Learn networking fundamentals, Cisco certifications, IP addressing, security, and more through hands-on video tutorials. Earn certificates and advance your IT career.</p>
            <div class="hero-buttons">
                <a href="tutorials.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-play-circle"></i> Start Learning
                </a>
                <a href="tutorials.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-graduation-cap"></i> Browse Tutorials
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Total Users</div>
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

<!-- Continue Learning Section (for logged in users) -->
<?php if (isLoggedIn() && !empty($inProgressCourses)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Continue Learning</h2>
            <p class="section-subtitle">Pick up where you left off and complete your courses</p>
        </div>
        <div class="course-grid">
            <?php foreach (array_slice($inProgressCourses, 0, 3) as $course): ?>
            <div class="course-card" data-category="<?php echo htmlspecialchars($course['category']); ?>" data-difficulty="<?php echo htmlspecialchars($course['difficulty']); ?>">
                <div class="course-thumbnail">
                    <img src="public/images/courses/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         onerror="this.onerror=null;this.src='public/images/placeholder.jpg'">
                    <span class="course-badge"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                    <span class="course-duration"><i class="fas fa-clock"></i> <?php echo $course['duration_minutes']; ?> min</span>
                </div>
                <div class="course-content">
                    <h3 class="course-title">
                        <a href="course.php?id=<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </a>
                    </h3>
                    <div class="course-meta">
                        <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                        <span><i class="fas fa-book"></i> <?php echo $course['total_lessons']; ?> lessons</span>
                    </div>
                    <div class="course-progress" id="progress-<?php echo $course['id']; ?>">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                        </div>
                        <span class="progress-text"><?php echo $course['progress_percentage']; ?>% complete</span>
                    </div>
                    <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm" style="margin-top: var(--spacing-md); width: 100%;">
                        <i class="fas fa-play"></i> Continue
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Courses Section -->
<section class="section" id="courses">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured Courses</h2>
            <p class="section-subtitle">Explore our most popular networking courses designed for beginners and professionals</p>
        </div>
        <div class="course-grid">
            <?php foreach ($featuredCourses as $course): ?>
            <div class="course-card" data-category="<?php echo htmlspecialchars($course['category']); ?>" data-difficulty="<?php echo htmlspecialchars($course['difficulty']); ?>">
                <div class="course-thumbnail">
                    <img src="public/images/courses/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         onerror="this.onerror=null;this.src='public/images/placeholder.jpg'">
                    <span class="course-badge"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                    <span class="course-duration"><i class="fas fa-clock"></i> <?php echo $course['duration_minutes']; ?> min</span>
                </div>
                <div class="course-content">
                    <h3 class="course-title">
                        <a href="course.php?id=<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </a>
                    </h3>
                    <div class="course-meta">
                        <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                        <span><i class="fas fa-book"></i> <?php echo $course['total_lessons']; ?> lessons</span>
                        <span><i class="fas fa-users"></i> <?php echo number_format($course['total_completions']); ?> completed</span>
                    </div>
                    <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm" style="margin-top: var(--spacing-md); width: 100%;">
                        <i class="fas fa-arrow-right"></i> Start Course
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center" style="margin-top: var(--spacing-2xl);">
            <a href="tutorials.php" class="btn btn-outline btn-lg">
                <i class="fas fa-th-large"></i> View All Courses
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="section" style="background: var(--darker);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose NetSkill?</h2>
            <p class="section-subtitle">Everything you need to master networking and advance your career</p>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-video"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">HD Video Lessons</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    High-quality video tutorials with clear explanations and practical demonstrations
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-certificate"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Earn Certificates</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Get industry-recognized certificates upon course completion to boost your resume
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Track Progress</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Monitor your learning journey with detailed progress tracking and analytics
                </p>
            </div>
            <div class="stat-card">
                <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: var(--spacing-md);">
                    <i class="fas fa-infinity"></i>
                </div>
                <h4 style="margin-bottom: var(--spacing-sm);">Lifetime Access</h4>
                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                    Learn at your own pace with unlimited access to all course materials
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($recentReviews)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What Our Students Say</h2>
            <p class="section-subtitle">Join thousands of satisfied learners who have advanced their careers</p>
        </div>
        <div class="testimonials-grid">
            <?php foreach ($recentReviews as $review): ?>
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p class="testimonial-text">
                        "<?php echo htmlspecialchars(substr($review['comment'], 0, 200)); ?><?php echo strlen($review['comment']) > 200 ? '...' : ''; ?>"
                    </p>
                    <div class="testimonial-author">
                        <img src="public/uploads/avatars/<?php echo htmlspecialchars($review['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($review['full_name']); ?>"
                             class="testimonial-avatar"
                             onerror="this.onerror=null;this.src='public/images/default-avatar.png'">
                        <div class="testimonial-info">
                            <h4><?php echo htmlspecialchars($review['full_name']); ?></h4>
                            <span><?php echo timeAgo($review['created_at']); ?></span>
                        </div>
                        <div class="testimonial-rating">
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center" style="margin-top: var(--spacing-2xl);">
            <a href="reviews.php" class="btn btn-outline btn-lg">
                <i class="fas fa-star"></i> View All Reviews
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="section" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); text-align: center;">
    <div class="container">
        <h2 style="color: white; margin-bottom: var(--spacing-md);">Ready to Start Your Networking Journey?</h2>
        <p style="color: rgba(255,255,255,0.9); max-width: 600px; margin: 0 auto var(--spacing-xl);">
            Join thousands of students who are already learning with NetSkill. Create your free account today and start mastering computer networking.
        </p>
        <div class="hero-buttons" style="justify-content: center;">
            <a href="register.php" class="btn btn-secondary btn-lg" style="background: white; color: var(--primary);">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="tutorials.php" class="btn btn-outline btn-lg" style="border-color: white; color: white;">
                <i class="fas fa-graduation-cap"></i> Explore Courses
            </a>
        </div>
    </div>
</section>

<!-- Network Animation Script -->
<script>
// Simple network node animation for hero section
document.addEventListener('DOMContentLoaded', function() {
    const heroNetwork = document.getElementById('heroNetwork');
    if (!heroNetwork) return;
    
    // Create network nodes
    for (let i = 0; i < 15; i++) {
        const node = document.createElement('div');
        node.className = 'network-node';
        node.style.left = Math.random() * 100 + '%';
        node.style.top = Math.random() * 100 + '%';
        node.style.animationDelay = Math.random() * 2 + 's';
        node.style.width = (4 + Math.random() * 8) + 'px';
        node.style.height = node.style.width;
        heroNetwork.appendChild(node);
    }
});
</script>

<?php include 'includes/footer.php'; ?>