<?php
// Course Model

class Course {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all published courses
    public function getAll($category = null, $difficulty = null, $search = null) {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                (SELECT COUNT(*) FROM completed_courses WHERE course_id = c.id) as total_completions
                FROM courses c WHERE c.is_published = 1";
        
        $params = [];
        $types = "";
        
        if ($category) {
            $sql .= " AND c.category = ?";
            $params[] = $category;
            $types .= "s";
        }
        
        if ($difficulty) {
            $sql .= " AND c.difficulty = ?";
            $params[] = $difficulty;
            $types .= "s";
        }
        
        if ($search) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY c.order_index ASC, c.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $stmt->close();
        return $courses;
    }
    
    // Get course by ID
    public function getById($id) {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons
                FROM courses c WHERE c.id = ? AND c.is_published = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $course = $result->fetch_assoc();
            $stmt->close();
            return $course;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get course lessons
    public function getLessons($course_id) {
        $stmt = $this->conn->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_index ASC");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $lessons = [];
        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }
        
        $stmt->close();
        return $lessons;
    }
    
    // Get lesson by ID
    public function getLessonById($lesson_id) {
        $stmt = $this->conn->prepare("SELECT l.*, c.title as course_title FROM lessons l 
                                      JOIN courses c ON l.course_id = c.id 
                                      WHERE l.id = ?");
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $lesson = $result->fetch_assoc();
            $stmt->close();
            return $lesson;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get user progress for a course
    public function getUserProgress($user_id, $course_id) {
        $sql = "SELECT l.id as lesson_id, l.title, l.order_index, 
                COALESCE(up.is_completed, 0) as is_completed,
                up.completed_at
                FROM lessons l
                LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                WHERE l.course_id = ?
                ORDER BY l.order_index ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $progress = [];
        while ($row = $result->fetch_assoc()) {
            $progress[] = $row;
        }
        
        $stmt->close();
        return $progress;
    }
    
    // Mark lesson as complete
    public function completeLesson($user_id, $lesson_id) {
        // Check if lesson exists and is required
        $stmt = $this->conn->prepare("SELECT is_required FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $lesson = $result->fetch_assoc();
        $stmt->close();
        
        // Insert or update progress
        $stmt = $this->conn->prepare("INSERT INTO user_progress (user_id, course_id, lesson_id, is_completed, completed_at) 
                                      SELECT ?, course_id, ?, 1, NOW() FROM lessons WHERE id = ?
                                      ON DUPLICATE KEY UPDATE is_completed = 1, completed_at = NOW()");
        $stmt->bind_param("iii", $user_id, $lesson_id, $lesson_id);
        $result = $stmt->execute();
        $stmt->close();
        
        // Check if course is complete
        if ($lesson['is_required']) {
            $this->checkCourseCompletion($user_id, $lesson_id);
        }
        
        return $result;
    }
    
    // Check if course is complete
    private function checkCourseCompletion($user_id, $lesson_id) {
        // Get course_id from lesson
        $stmt = $this->conn->prepare("SELECT course_id FROM lessons WHERE id = ?");
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $lesson = $result->fetch_assoc();
        $course_id = $lesson['course_id'];
        $stmt->close();
        
        // Check if all required lessons are complete
        $sql = "SELECT COUNT(*) as total_required FROM lessons WHERE course_id = ? AND is_required = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total_required'];
        $stmt->close();
        
        $sql = "SELECT COUNT(*) as completed FROM user_progress up 
                JOIN lessons l ON up.lesson_id = l.id 
                WHERE up.user_id = ? AND l.course_id = ? AND l.is_required = 1 AND up.is_completed = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $completed = $result->fetch_assoc()['completed'];
        $stmt->close();
        
        // If all lessons are complete, mark course as completed
        if ($completed >= $total) {
            // Check if already completed
            $stmt = $this->conn->prepare("SELECT id FROM completed_courses WHERE user_id = ? AND course_id = ?");
            $stmt->bind_param("ii", $user_id, $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Insert completion record
                $stmt = $this->conn->prepare("INSERT INTO completed_courses (user_id, course_id, completed_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("ii", $user_id, $course_id);
                $stmt->execute();
                $stmt->close();
                
                return true; // Course just completed
            }
            $stmt->close();
        }
        
        return false;
    }
    
    // Check if user has completed a course
    public function isCourseCompleted($user_id, $course_id) {
        $stmt = $this->conn->prepare("SELECT id FROM completed_courses WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_completed = $result->num_rows > 0;
        $stmt->close();
        return $is_completed;
    }
    
    // Get user's incomplete courses with progress
    public function getInProgressCourses($user_id) {
        $sql = "SELECT c.*, 
                COUNT(up.id) as completed_lessons,
                COUNT(l.id) as total_lessons,
                ROUND((COUNT(up.id) / COUNT(l.id)) * 100, 2) as progress_percentage
                FROM courses c
                JOIN lessons l ON c.id = l.course_id
                LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ? AND up.is_completed = 1
                WHERE c.is_published = 1
                AND EXISTS (SELECT 1 FROM user_progress WHERE user_id = ? AND lesson_id = l.id)
                AND NOT EXISTS (SELECT 1 FROM completed_courses WHERE user_id = ? AND course_id = c.id)
                GROUP BY c.id
                ORDER BY MAX(up.completed_at) DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $stmt->close();
        return $courses;
    }
    
    // Get completed courses for a user
    public function getCompletedCourses($user_id) {
        $sql = "SELECT c.*, cc.completed_at
                FROM completed_courses cc
                JOIN courses c ON cc.course_id = c.id
                WHERE cc.user_id = ?
                ORDER BY cc.completed_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $stmt->close();
        return $courses;
    }
    
    // Get course categories
    public function getCategories() {
        $result = $this->conn->query("SELECT DISTINCT category FROM courses WHERE is_published = 1 ORDER BY category ASC");
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
    
    // Get course count
    public function getCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM courses WHERE is_published = 1");
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Get completed course count
    public function getCompletedCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM completed_courses");
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Get completions per month
    public function getCompletionsPerMonth($months = 12) {
        $stmt = $this->conn->prepare("SELECT DATE_FORMAT(completed_at, '%Y-%m') as month, COUNT(*) as count 
                                      FROM completed_courses 
                                      WHERE completed_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) 
                                      GROUP BY month ORDER BY month ASC");
        $stmt->bind_param("i", $months);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }
}
?>