<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/Course.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('tutorials.php');
}

$courseId = (int)$_GET['id'];
$courseModel = new Course($GLOBALS['db']);
$course = $courseModel->getById($courseId);

if (!$course) {
    setFlashMessage('error', 'Course not found.');
    redirect('tutorials.php');
}

$lessons = $courseModel->getLessons($courseId);
$userProgress = [];
$isCompleted = false;

if (isLoggedIn()) {
    $user = getCurrentUser();
    $userProgress = $courseModel->getUserProgress($user['id'], $courseId);
    $isCompleted = $courseModel->isCourseCompleted($user['id'], $courseId);
}

// Handle mark lesson as complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_lesson']) && isLoggedIn()) {
    $lessonId = (int)$_POST['lesson_id'];
    $courseModel->completeLesson($user['id'], $lessonId);
    redirect('course.php?id=' . $courseId . '&lesson=' . $lessonId);
}

// Get current lesson
$currentLessonId = isset($_GET['lesson']) && is_numeric($_GET['lesson']) ? (int)$_GET['lesson'] : ($lessons[0]['id'] ?? 0);
$currentLesson = null;
foreach ($lessons as $l) {
    if ($l['id'] === $currentLessonId) {
        $currentLesson = $l;
        break;
    }
}
if (!$currentLesson && !empty($lessons)) {
    $currentLesson = $lessons[0];
}

$page_title = htmlspecialchars($course['title']);
include 'includes/header.php';
?>

<style>
.lesson-container {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 20px;
}

.video-section {
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    aspect-ratio: 16 / 9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.video-section video,
.video-section iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.video-placeholder {
    color: #fff;
    text-align: center;
    padding: 40px;
}

.video-placeholder i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.lesson-info {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 24px;
    margin-top: 16px;
}

.lesson-info h2 {
    margin: 0 0 8px;
    font-size: 22px;
}

.lesson-info .meta {
    color: var(--text-secondary);
    font-size: 14px;
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.lesson-info .description {
    line-height: 1.7;
    margin-bottom: 20px;
}

.complete-btn {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 12px 32px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.complete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.complete-btn:disabled {
    opacity: 0.6;
    cursor: default;
    transform: none;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sidebar h3 {
    margin: 0 0 8px;
    font-size: 16px;
    padding: 0 4px;
}

.lesson-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--card-bg);
    border-radius: 10px;
    text-decoration: none;
    color: var(--text);
    transition: all 0.15s;
    border: 1px solid rgba(255,255,255,0.06);
}

.lesson-item:hover {
    background: rgba(255,255,255,0.06);
}

.lesson-item.active {
    background: rgba(59,130,246,0.1);
    border-color: var(--primary);
}

.lesson-item .status {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.lesson-item .status.done {
    background: var(--success);
    color: #fff;
}

.lesson-item .status.pending {
    background: rgba(255,255,255,0.08);
    color: var(--text-secondary);
}

.lesson-item .num {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    min-width: 20px;
}

.lesson-item .title {
    flex: 1;
    font-size: 14px;
}

.lesson-item .duration {
    font-size: 12px;
    color: var(--text-secondary);
    white-space: nowrap;
}

.course-header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px 0;
}

.course-header h1 {
    margin: 0 0 4px;
}

.course-header .breadcrumb {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.course-header .breadcrumb a {
    color: var(--primary);
    text-decoration: none;
}

@media (max-width: 768px) {
    .lesson-container {
        grid-template-columns: 1fr;
    }
    .video-section {
        aspect-ratio: 16 / 9;
    }
}
</style>

<div class="course-header">
    <div class="breadcrumb">
        <a href="tutorials.php">Courses</a> / <?php echo htmlspecialchars($course['title']); ?>
    </div>
    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
    <p style="color: var(--text-secondary); margin: 0;"><?php echo htmlspecialchars($course['category']); ?> &middot; <?php echo $course['total_lessons']; ?> lessons</p>
</div>

<div class="lesson-container">
    <div>
        <!-- Video Player -->
        <div class="video-section">
            <?php if (!empty($currentLesson['video_url'])): 
                $videoUrl = $currentLesson['video_url'];
                // Check if it's a YouTube URL
                if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $videoUrl, $m) || preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $videoUrl, $m)): ?>
                    <iframe src="https://www.youtube.com/embed/<?php echo $m[1]; ?>" allowfullscreen></iframe>
                <?php elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $m)): ?>
                    <iframe src="https://player.vimeo.com/video/<?php echo $m[1]; ?>" allowfullscreen></iframe>
                <?php else: ?>
                    <video controls preload="metadata">
                        <source src="<?php echo htmlspecialchars($videoUrl); ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            <?php else: ?>
                <div class="video-placeholder">
                    <i class="fas fa-video"></i>
                    <h3>No video uploaded yet</h3>
                    <p>This lesson doesn't have a video yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lesson Info -->
        <div class="lesson-info">
            <h2><?php echo htmlspecialchars($currentLesson['title']); ?></h2>
            <div class="meta">
                <span><i class="fas fa-clock"></i> <?php echo $currentLesson['duration_minutes']; ?> min</span>
                <?php 
                $isLessonComplete = false;
                $lessonIdCompleted = false;
                if (isLoggedIn() && !empty($userProgress)) {
                    foreach ($userProgress as $p) {
                        if ($p['lesson_id'] == $currentLesson['id'] && $p['is_completed']) {
                            $isLessonComplete = true;
                            break;
                        }
                    }
                }
                ?>
                <span><?php echo $isLessonComplete ? '<i class="fas fa-check-circle" style="color: var(--success);"></i> Completed' : '<i class="fas fa-circle"></i> Not completed'; ?></span>
            </div>
            <div class="description">
                <?php echo nl2br(htmlspecialchars($currentLesson['description'] ?? 'No description for this lesson.')); ?>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <?php if ($isLessonComplete): ?>
                    <div class="complete-btn" style="background:var(--success);display:inline-flex;align-items:center;gap:8px;padding:12px 32px;border-radius:30px;font-weight:700;font-size:15px;color:#fff;opacity:0.8;cursor:default;">
                        <i class="fas fa-check-circle"></i> Completed
                    </div>
                <?php else: ?>
                    <div id="completion-status" style="color:var(--text-secondary);font-size:14px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-circle" style="color:var(--text-secondary);font-size:10px;"></i>
                        <span id="watch-text">Watch the full video to complete this lesson.</span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary);"><a href="login.php" style="color: var(--primary);">Login</a> to track your progress.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lesson List Sidebar -->
    <div class="sidebar">
        <h3>Course Lessons</h3>
        <?php foreach ($lessons as $index => $lesson): 
            $isActive = $lesson['id'] === $currentLesson['id'];
            $isLessonDone = false;
            if (isLoggedIn() && !empty($userProgress)) {
                foreach ($userProgress as $p) {
                    if ($p['lesson_id'] == $lesson['id'] && $p['is_completed']) {
                        $isLessonDone = true;
                        break;
                    }
                }
            }
        ?>
            <a href="course.php?id=<?php echo $courseId; ?>&lesson=<?php echo $lesson['id']; ?>" 
               class="lesson-item <?php echo $isActive ? 'active' : ''; ?>">
                <span class="status <?php echo $isLessonDone ? 'done' : 'pending'; ?>">
                    <?php echo $isLessonDone ? '<i class="fas fa-check"></i>' : ($index + 1); ?>
                </span>
                <span class="title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                <span class="duration"><?php echo $lesson['duration_minutes']; ?>m</span>
            </a>
        <?php endforeach; ?>
        
        <?php if ($isCompleted): ?>
            <div style="text-align:center;padding:16px;background:rgba(46,213,115,0.1);border-radius:10px;margin-top:8px;">
                <i class="fas fa-trophy" style="color:var(--success);font-size:24px;"></i>
                <p style="margin:4px 0 0;font-weight:700;color:var(--success);">Course Completed!</p>
                <a href="certificate.php?course_id=<?php echo $courseId; ?>" style="color:var(--primary);font-size:14px;">View Certificate</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-mark lesson as complete when video finishes
document.addEventListener('DOMContentLoaded', function() {
    const videoSection = document.querySelector('.video-section');
    const lessonId = <?php echo json_encode($currentLesson['id']); ?>;
    const courseId = <?php echo json_encode($courseId); ?>;
    const isComplete = <?php echo $isLessonComplete ? 'true' : 'false'; ?>;
    
    if (isComplete) return;
    
    // Check for iframe (YouTube)
    const iframe = videoSection?.querySelector('iframe');
    if (iframe) {
        // Try to access YouTube player via postMessage API
        // Listen for messages from YouTube
        window.addEventListener('message', function(event) {
            if (event.origin !== 'https://www.youtube.com' && event.origin !== 'https://www.youtube-nocookie.com') return;
            try {
                const data = JSON.parse(event.data);
                if (data.event === 'onStateChange' && data.info === 0) {
                    // Video ended
                    completeLesson(lessonId, courseId);
                }
            } catch(e) {}
        });
    }
    
    // Check for native HTML5 video
    const video = videoSection?.querySelector('video');
    if (video) {
        video.addEventListener('ended', function() {
            completeLesson(lessonId, courseId);
        });
    }
    
    function completeLesson(lessonId, courseId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'lesson_id';
        input.value = lessonId;
        const btn = document.createElement('input');
        btn.type = 'hidden';
        btn.name = 'complete_lesson';
        btn.value = '1';
        form.appendChild(input);
        form.appendChild(btn);
        document.body.appendChild(form);
        
        // Update UI immediately
        const status = document.getElementById('completion-status');
        if (status) {
            status.innerHTML = '<div class="complete-btn" style="background:var(--success);display:inline-flex;align-items:center;gap:8px;padding:12px 32px;border-radius:30px;font-weight:700;font-size:15px;color:#fff;opacity:0.8;cursor:default;"><i class="fas fa-check-circle"></i> Completed</div>';
        }
        
        // Reload page to show updated lesson list
        setTimeout(() => {
            window.location.href = 'course.php?id=' + courseId + '&lesson=' + lessonId;
        }, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
