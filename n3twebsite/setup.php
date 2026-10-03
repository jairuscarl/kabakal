<?php
/**
 * NetSkill Database Setup Script
 * Run this file once to create the database and tables
 * Access via: http://localhost/N3tSkills/setup.php
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'netskills_db');

// Check if already installed (commented out for debugging)
// $installLock = __DIR__ . '/.installed';
// if (file_exists($installLock)) {
//     die('NetSkill is already installed. Delete .installed file to reinstall.');
// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetSkill - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a1a;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #2d2d2d;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 {
            color: #A52A2A;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .subtitle {
            color: #b0b0b0;
            margin-bottom: 30px;
        }
        .step {
            background: #333;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #A52A2A;
        }
        .step h3 {
            color: #A52A2A;
            margin-bottom: 10px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #8B0000;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #A52A2A;
        }
        .credentials {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .credentials table {
            width: 100%;
            border-collapse: collapse;
        }
        .credentials td {
            padding: 10px;
            border-bottom: 1px solid #444;
        }
        .credentials td:last-child {
            font-family: monospace;
            color: #A52A2A;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🌐</div>
        <h1>NetSkill Installation</h1>
        <p class="subtitle">Setting up your networking tutorial platform...</p>
        
        <?php
        $errors = [];
        $success = [];
        
        // Step 1: Check PHP version
        echo '<div class="step">';
        echo '<h3>Step 1: Checking PHP Version</h3>';
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo '<p class="success">✓ PHP version ' . $phpVersion . ' is compatible</p>';
        } else {
            echo '<p class="error">✗ PHP version ' . $phpVersion . ' is too old. Need PHP 7.4+</p>';
            $errors[] = 'PHP version too old';
        }
        echo '</div>';
        
        // Step 2: Check required extensions
        echo '<div class="step">';
        echo '<h3>Step 2: Checking PHP Extensions</h3>';
        $required = ['mysqli', 'json', 'session', 'fileinfo'];
        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                echo '<p class="success">✓ Extension ' . $ext . ' is loaded</p>';
            } else {
                echo '<p class="error">✗ Extension ' . $ext . ' is NOT loaded</p>';
                $errors[] = 'Missing extension: ' . $ext;
            }
        }
        echo '</div>';
        
        // Step 3: Connect to MySQL
        echo '<div class="step">';
        echo '<h3>Step 3: Connecting to MySQL</h3>';
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
        if ($conn->connect_error) {
            echo '<p class="error">✗ MySQL connection failed: ' . $conn->connect_error . '</p>';
            echo '<p class="warning">Make sure MySQL is running in XAMPP</p>';
            $errors[] = 'Cannot connect to MySQL';
        } else {
            echo '<p class="success">✓ Connected to MySQL successfully</p>';
        }
        echo '</div>';
        
        // Step 4: Create database
        if (!isset($errors[0])) {
            echo '<div class="step">';
            echo '<h3>Step 4: Creating Database</h3>';
            $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($conn->query($sql)) {
                echo '<p class="success">✓ Database "' . DB_NAME . '" created successfully</p>';
            } else {
                echo '<p class="error">✗ Failed to create database: ' . $conn->error . '</p>';
                $errors[] = 'Cannot create database';
            }
            echo '</div>';
        }
        
        // Step 5: Create tables
        if (!isset($errors[0])) {
            echo '<div class="step">';
            echo '<h3>Step 5: Creating Database Tables</h3>';
            
            $conn->select_db(DB_NAME);
            
            // Users table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "users" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create users table'; }
            
            // Courses table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "courses" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create courses table'; }
            
            // Lessons table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "lessons" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create lessons table'; }
            
            // User progress table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "user_progress" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create user_progress table'; }
            
            // Completed courses table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "completed_courses" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create completed_courses table'; }
            
            // Certificates table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "certificates" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create certificates table'; }
            
            // Reviews table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "reviews" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create reviews table'; }
            
            // Activity logs table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "activity_logs" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create activity_logs table'; }
            
            // Sessions table
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
            if ($conn->query($sql)) echo '<p class="success">✓ Table "sessions" created</p>';
            else { echo '<p class="error">✗ ' . $conn->error . '</p>'; $errors[] = 'Cannot create sessions table'; }
            
            echo '</div>';
            
            // Step 6: Insert default data
            echo '<div class="step">';
            echo '<h3>Step 6: Inserting Default Data</h3>';
            
            // Create admin account
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT IGNORE INTO users (username, email, password, full_name, role) 
                    VALUES ('admin', 'admin@netskill.com', '$admin_password', 'Admin User', 'admin')";
            if ($conn->query($sql)) echo '<p class="success">✓ Admin account created</p>';
            else echo '<p class="warning">⚠ Admin account may already exist</p>';
            
            // Insert sample courses
            $sample_courses = [
                ['Introduction to Networking', 'Learn the fundamentals of computer networking including OSI model, TCP/IP, network topologies, and basic concepts.', 'Networking Basics', 'Beginner', 'intro-networking.jpg', 120, 1],
                ['Understanding IP Addresses', 'Master IPv4 and IPv6 addressing, subnetting, CIDR notation, and IP configuration.', 'IP Addressing', 'Beginner', 'ip-addressing.jpg', 90, 2],
                ['Cisco CCNA Fundamentals', 'Prepare for Cisco CCNA certification with hands-on router and switch configuration.', 'Cisco Fundamentals', 'Intermediate', 'cisco-fundamentals.jpg', 180, 3],
                ['Routing Protocols Deep Dive', 'Advanced routing protocols including OSPF, EIGRP, and BGP configuration.', 'Routing & Switching', 'Advanced', 'routing-switching.jpg', 150, 4],
                ['Network Security Essentials', 'Protect your networks from threats with firewalls, VPNs, and security best practices.', 'Network Security', 'Intermediate', 'network-security.jpg', 120, 5],
                ['Network Troubleshooting Guide', 'Diagnose and fix common network issues using industry-standard tools and methodologies.', 'Troubleshooting', 'Intermediate', 'troubleshooting.jpg', 100, 6]
            ];
            
            foreach ($sample_courses as $course) {
                $sql = "INSERT IGNORE INTO courses (title, description, category, difficulty, thumbnail, duration_minutes, order_index) 
                        VALUES ('{$course[0]}', '{$course[1]}', '{$course[2]}', '{$course[3]}', '{$course[4]}', {$course[5]}, {$course[6]})";
                if ($conn->query($sql)) {
                    echo '<p class="success">✓ Course inserted: ' . $course[0] . '</p>';
                    
                    // Get course ID and insert sample lessons
                    $result = $conn->query("SELECT id FROM courses WHERE title = '{$course[0]}' LIMIT 1");
                    if ($result && $result->num_rows > 0) {
                        $course_id = $result->fetch_assoc()['id'];
                        
                        // Insert 3 sample lessons for each course
                        for ($i = 1; $i <= 3; $i++) {
                            $lesson_title = "Lesson $i: " . $course[0] . " - Part $i";
                            $sql = "INSERT IGNORE INTO lessons (course_id, title, description, video_url, duration_minutes, order_index, is_required) 
                                    VALUES ($course_id, '$lesson_title', 'This is lesson $i of {$course[0]}', '', " . ($course[5] / 3) . ", $i, 1)";
                            $conn->query($sql);
                        }
                    }
                } else {
                    echo '<p class="warning">⚠ Course may already exist: ' . $course[0] . '</p>';
                }
            }
            
            echo '</div>';
        }
        
        // Create upload directories
        echo '<div class="step">';
        echo '<h3>Step 7: Creating Upload Directories</h3>';
        $dirs = [
            __DIR__ . '/public/uploads/avatars',
            __DIR__ . '/public/uploads/reviews',
            __DIR__ . '/public/uploads/certificates',
            __DIR__ . '/logs'
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    echo '<p class="success">✓ Created directory: ' . basename($dir) . '</p>';
                } else {
                    echo '<p class="warning">⚠ Could not create: ' . $dir . '</p>';
                }
            } else {
                echo '<p class="success">✓ Directory exists: ' . basename($dir) . '</p>';
            }
        }
        echo '</div>';
        
        // Final status
        if (empty($errors)) {
            // Create install lock file
            file_put_contents($installLock, date('Y-m-d H:i:s'));
            
            echo '<div class="step" style="border-left-color: #28a745;">';
            echo '<h3 style="color: #28a745;">✓ Installation Complete!</h3>';
            echo '<p class="success">NetSkill has been successfully installed.</p>';
            
            echo '<div class="credentials">';
            echo '<h4>Admin Login Credentials:</h4>';
            echo '<table>';
            echo '<tr><td>Email:</td><td>admin@netskill.com</td></tr>';
            echo '<tr><td>Password:</td><td>admin123</td></tr>';
            echo '<tr><td>Dashboard:</td><td>admin/dashboard.php</td></tr>';
            echo '</table>';
            echo '</div>';
            
            echo '<p class="warning">⚠️ <strong>IMPORTANT:</strong> Change your admin password immediately after first login!</p>';
            
            echo '<a href="index.php" class="btn">Go to Homepage →</a>';
            echo '<a href="login.php" class="btn">Login as Admin →</a>';
            echo '</div>';
        } else {
            echo '<div class="step" style="border-left-color: #dc3545;">';
            echo '<h3 style="color: #dc3545;">✗ Installation Failed</h3>';
            echo '<p class="error">The following errors occurred:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li class="error">' . $error . '</li>';
            }
            echo '</ul>';
            echo '<p>Please fix these issues and refresh the page.</p>';
            echo '</div>';
        }
        
        $conn->close();
        ?>
        
        <div class="step" style="margin-top: 30px;">
            <h3>Need Help?</h3>
            <p>If you encounter any issues:</p>
            <ul>
                <li>Make sure XAMPP Apache and MySQL are both running (green)</li>
                <li>Check that the N3tSkills folder is in <code>C:\xampp\htdocs\N3tSkills\</code></li>
                <li>Verify that mod_rewrite is enabled in Apache (usually enabled by default)</li>
                <li>Check the error logs in <code>C:\xampp\apache\logs\error.log</code></li>
            </ul>
        </div>
    </div>
</body>
</html>