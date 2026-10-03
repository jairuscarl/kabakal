<?php
// Database Configuration for NetSkill
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'netskills_db');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Initialize database (run this once to create tables)
function initializeDatabase() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!$conn->query($sql)) {
        die("Error creating database: " . $conn->error);
    }
    
    // Select database
    $conn->select_db(DB_NAME);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        INDEX idx_email (email),
        INDEX idx_username (username),
        INDEX idx_role (role)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating users table: " . $conn->error);
    }
    
    // Create courses table
    $sql = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category ENUM('Networking Basics', 'IP Addressing', 'Cisco Fundamentals', 'Routing & Switching', 'Network Security', 'Troubleshooting') NOT NULL,
        difficulty ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
        thumbnail VARCHAR(255),
        duration_minutes INT DEFAULT 0,
        order_index INT DEFAULT 0,
        is_published BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_difficulty (difficulty),
        INDEX idx_published (is_published)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating courses table: " . $conn->error);
    }
    
    // Create lessons table
    $sql = "CREATE TABLE IF NOT EXISTS lessons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        video_url VARCHAR(500),
        duration_minutes INT DEFAULT 0,
        order_index INT DEFAULT 0,
        is_required BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        INDEX idx_course (course_id),
        INDEX idx_order (order_index)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating lessons table: " . $conn->error);
    }
    
    // Create user_progress table
    $sql = "CREATE TABLE IF NOT EXISTS user_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        lesson_id INT NOT NULL,
        is_completed BOOLEAN DEFAULT FALSE,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_lesson (user_id, lesson_id),
        INDEX idx_user (user_id),
        INDEX idx_course (course_id),
        INDEX idx_completed (is_completed)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating user_progress table: " . $conn->error);
    }
    
    // Create completed_courses table
    $sql = "CREATE TABLE IF NOT EXISTS completed_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        grade FLOAT DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_course (user_id, course_id),
        INDEX idx_user (user_id),
        INDEX idx_course (course_id),
        INDEX idx_completed_at (completed_at)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating completed_courses table: " . $conn->error);
    }
    
    // Create certificates table
    $sql = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_code VARCHAR(20) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        course_name VARCHAR(200) NOT NULL,
        issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        pdf_path VARCHAR(255),
        is_valid BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        INDEX idx_code (certificate_code),
        INDEX idx_user (user_id),
        INDEX idx_valid (is_valid)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating certificates table: " . $conn->error);
    }
    
    // Create review_likes table
    $sql = "CREATE TABLE IF NOT EXISTS review_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review_user (review_id, user_id),
        INDEX idx_review (review_id),
        INDEX idx_user (user_id)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating review_likes table: " . $conn->error);
    }
    
    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT DEFAULT NULL,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        image_path VARCHAR(255),
        is_approved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_course (course_id),
        INDEX idx_approved (is_approved),
        INDEX idx_rating (rating),
        INDEX idx_created (created_at)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating reviews table: " . $conn->error);
    }
    
    // Create activity_logs table
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_type (activity_type),
        INDEX idx_created (created_at)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating activity_logs table: " . $conn->error);
    }
    
    // Create sessions table
    $sql = "CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (session_token),
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating sessions table: " . $conn->error);
    }
    
    // Insert default admin account
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@netskill.com', '$admin_password', 'Admin User', 'admin')";
    $conn->query($sql);
    
    // Insert sample courses
    $sample_courses = [
        ['Introduction to Networking', 'Learn the fundamentals of computer networking', 'Networking Basics', 'Beginner', 'intro-networking.jpg', 120, 1],
        ['Understanding IP Addresses', 'Master IPv4 and IPv6 addressing', 'IP Addressing', 'Beginner', 'ip-addressing.jpg', 90, 2],
        ['Cisco CCNA Fundamentals', 'Prepare for Cisco CCNA certification', 'Cisco Fundamentals', 'Intermediate', 'cisco-fundamentals.jpg', 180, 3],
        ['Routing Protocols Deep Dive', 'Advanced routing protocols and configuration', 'Routing & Switching', 'Advanced', 'routing-switching.jpg', 150, 4],
        ['Network Security Essentials', 'Protect your networks from threats', 'Network Security', 'Intermediate', 'network-security.jpg', 120, 5],
        ['Network Troubleshooting Guide', 'Diagnose and fix common network issues', 'Troubleshooting', 'Intermediate', 'troubleshooting.jpg', 100, 6]
    ];
    
    foreach ($sample_courses as $course) {
        $sql = "INSERT IGNORE INTO courses (title, description, category, difficulty, thumbnail, duration_minutes, order_index) 
                VALUES ('{$course[0]}', '{$course[1]}', '{$course[2]}', '{$course[3]}', '{$course[4]}', {$course[5]}, {$course[6]})";
        $conn->query($sql);
    }
    
    $conn->close();
    return true;
}

// Log activity
function logActivity($conn, $user_id, $activity_type, $description = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $activity_type, $description, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Generate unique certificate code
function generateCertificateCode() {
    return 'NS-' . strtoupper(uniqid() . substr(md5(time() . rand()), 0, 6));
}
?>