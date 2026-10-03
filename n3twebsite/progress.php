<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/Course.php';

// Require authentication
requireAuth();

$courseModel = new Course($GLOBALS['db']);
$user = getCurrentUser();

// Get user's in-progress and completed courses
$inProgressCourses = $courseModel->getInProgressCourses($user['id']);
$completedCourses = $courseModel->getCompletedCourses($user['id']);

$page_title = 'My Progress';
include 'includes/header.php';
?>

<section class="hero" style="min-height: 30vh;">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-chart-line"></i> Learning Journey
            </span>
            <h1>My Progress</h1>
            <p>Track your learning progress across all courses</p>
        </div>
    </div>
</section>

<!-- In Progress Courses -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">In Progress</h2>
            <p class="section-subtitle">Continue where you left off</p>
        </div>
        
        <?php if (empty($inProgressCourses)): ?>
            <div class="empty-state">
                <i class="fas fa-play-circle"></i>
                <h3>No courses in progress</h3>
                <p>Start learning today by enrolling in a course!</p>
                <a href="tutorials.php" class="btn btn-primary" style="margin-top: var(--spacing-lg);">
                    <i class="fas fa-graduation-cap"></i> Browse Courses
                </a>
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($inProgressCourses as $course): ?>
                <div class="course-card">
                    <div class="course-thumbnail">
                        <img src="public/images/courses/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                             onerror="this.onerror=null;this.src='public/images/placeholder.jpg'">
                        <span class="course-badge"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                    </div>
                    <div class="course-content">
                        <h3 class="course-title">
                            <a href="course.php?id=<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </a>
                        </h3>
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
        <?php endif; ?>
    </div>
</section>

<!-- Completed Courses -->
<?php if (!empty($completedCourses)): ?>
<section class="section" style="background: var(--darker);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Completed Courses</h2>
            <p class="section-subtitle">Courses you've successfully finished</p>
        </div>
        <div class="course-grid">
            <?php foreach ($completedCourses as $course): ?>
            <div class="course-card">
                <div class="course-thumbnail">
                    <img src="public/images/courses/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         onerror="this.onerror=null;this.src='public/images/placeholder.jpg'">
                    <span class="course-badge" style="background: var(--success);">Completed</span>
                </div>
                <div class="course-content">
                    <h3 class="course-title">
                        <a href="course.php?id=<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </a>
                    </h3>
                    <p style="color: var(--text-muted); font-size: var(--text-sm);">
                        <i class="fas fa-calendar-check"></i> Completed: <?php echo formatDate($course['completed_at']); ?>
                    </p>
                    <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm" style="margin-top: var(--spacing-md); width: 100%;">
                        <i class="fas fa-certificate"></i> View Certificate
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>