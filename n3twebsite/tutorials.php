<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/Course.php';

// Initialize models
$courseModel = new Course($GLOBALS['db']);

// Get filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
$difficulty = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

// Get filtered courses
$courses = $courseModel->getAll($category, $difficulty, $search);

// Get categories for filter
$categories = $courseModel->getCategories();

// Get user's progress if logged in
$userProgress = [];
if (isLoggedIn()) {
    $user = getCurrentUser();
}

$page_title = 'Tutorials';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 40vh;">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-graduation-cap"></i> Course Library
            </span>
            <h1>Explore Our Networking Courses</h1>
            <p>Choose from our comprehensive collection of networking tutorials, from basics to advanced topics</p>
        </div>
    </div>
</section>

<!-- Filters Section -->
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="card" style="padding: var(--spacing-lg); margin-bottom: var(--spacing-2xl);">
            <form method="GET" action="tutorials.php" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <!-- Search Input -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="searchInput">Search Courses</label>
                        <div class="input-icon">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" name="search" 
                                   class="form-control" 
                                   placeholder="Search by title or description..."
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="categoryFilter">Category</label>
                        <select id="categoryFilter" name="category" class="form-control form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Difficulty Filter -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="difficultyFilter">Difficulty</label>
                        <select id="difficultyFilter" name="difficulty" class="form-control form-select">
                            <option value="">All Levels</option>
                            <option value="Beginner" <?php echo ($difficulty === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($difficulty === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($difficulty === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Count -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
            <p style="margin: 0; color: var(--text-secondary);">
                Showing <strong><?php echo count($courses); ?></strong> course<?php echo count($courses) !== 1 ? 's' : ''; ?>
                <?php if ($category || $difficulty || $search): ?>
                    with filters applied
                    <a href="tutorials.php" style="margin-left: var(--spacing-sm);">
                        <i class="fas fa-times"></i> Clear all
                    </a>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Course Grid -->
        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No courses found</h3>
                <p>Try adjusting your search terms or filters</p>
                <a href="tutorials.php" class="btn btn-primary" style="margin-top: var(--spacing-lg);">
                    <i class="fas fa-redo"></i> Reset Filters
                </a>
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): 
                    $isCompleted = false;
                    $progress = 0;
                    if (isLoggedIn() && isset($user)) {
                        $isCompleted = $courseModel->isCourseCompleted($user['id'], $course['id']);
                        $userProgressData = $courseModel->getUserProgress($user['id'], $course['id']);
                        if (!empty($userProgressData)) {
                            $completedLessons = count(array_filter($userProgressData, fn($l) => $l['is_completed']));
                            $progress = round(($completedLessons / count($userProgressData)) * 100);
                        }
                    }
                ?>
                <div class="course-card" 
                     data-category="<?php echo htmlspecialchars($course['category']); ?>" 
                     data-difficulty="<?php echo htmlspecialchars($course['difficulty']); ?>">
                    <div class="course-thumbnail">
                        <img src="public/images/courses/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                         onerror="this.onerror=null;this.src='public/images/placeholder.jpg'">
                        <span class="course-badge"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                        <span class="course-duration"><i class="fas fa-clock"></i> <?php echo $course['duration_minutes']; ?> min</span>
                        <?php if ($isCompleted): ?>
                            <span style="position: absolute; top: var(--spacing-md); right: var(--spacing-md); background: var(--success); padding: var(--spacing-xs) var(--spacing-sm); border-radius: var(--radius-sm); font-size: var(--text-xs);">
                                <i class="fas fa-check"></i> Completed
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="course-content">
                        <h3 class="course-title">
                            <a href="course.php?id=<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </a>
                        </h3>
                        <p class="card-text" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($course['description']); ?>
                        </p>
                        <div class="course-meta">
                            <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                            <span><i class="fas fa-book"></i> <?php echo $course['total_lessons']; ?> lessons</span>
                            <span><i class="fas fa-users"></i> <?php echo number_format($course['total_completions']); ?></span>
                        </div>
                        
                        <?php if (isLoggedIn() && isset($user)): ?>
                            <div class="course-progress" id="progress-<?php echo $course['id']; ?>">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo $progress; ?>% complete</span>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md);">
                            <?php if ($isCompleted): ?>
                                <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm" style="flex: 1;">
                                    <i class="fas fa-certificate"></i> View Certificate
                                </a>
                            <?php else: ?>
                                <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                                    <i class="fas fa-play"></i> <?php echo $progress > 0 ? 'Continue' : 'Start'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>