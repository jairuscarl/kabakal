<?php
// User Model

class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new user
    public function create($username, $email, $password, $full_name) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get user by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT id, username, email, full_name, role, profile_image, created_at, last_login FROM users WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get user by email
    public function getByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get user by username
    public function getByUsername($username) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        
        $stmt->close();
        return false;
    }
    
    // Check if email exists
    public function emailExists($email, $exclude_id = null) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?" . ($exclude_id ? " AND id != ?" : ""));
        if ($exclude_id) {
            $stmt->bind_param("si", $email, $exclude_id);
        } else {
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    // Check if username exists
    public function usernameExists($username, $exclude_id = null) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?" . ($exclude_id ? " AND id != ?" : ""));
        if ($exclude_id) {
            $stmt->bind_param("si", $username, $exclude_id);
        } else {
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    // Verify password
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Update user profile
    public function updateProfile($user_id, $full_name, $username = null) {
        $stmt = $this->conn->prepare("UPDATE users SET full_name = ?" . ($username ? ", username = ?" : "") . " WHERE id = ?");
        if ($username) {
            $stmt->bind_param("ssi", $full_name, $username, $user_id);
        } else {
            $stmt->bind_param("si", $full_name, $user_id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Update user password
    public function updatePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Update profile image
    public function updateProfileImage($user_id, $image_path) {
        $stmt = $this->conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $user_id);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Update last login
    public function updateLastLogin($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Get all users (admin only)
    public function getAll($limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT id, username, email, full_name, role, profile_image, created_at, last_login FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
    
    // Get user count
    public function getCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Get user count by role
    public function getCountByRole($role) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'];
    }
    
    // Get registered users per month
    public function getUsersPerMonth($months = 12) {
        $stmt = $this->conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) GROUP BY month ORDER BY month ASC");
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
    
    // Check if user is email verified
    public function isVerified($user_id) {
        $stmt = $this->conn->prepare("SELECT is_verified FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? (bool)$row['is_verified'] : false;
    }

    // Set verification token for a user
    public function setVerificationToken($user_id, $token) {
        $expiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        $stmt = $this->conn->prepare("UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expiry, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Verify user by token
    public function verifyByToken($token) {
        $stmt = $this->conn->prepare("SELECT id, token_expiry FROM users WHERE verification_token = ? AND is_verified = 0 LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows !== 1) {
            $stmt->close();
            return ['success' => false, 'message' => 'Invalid verification token.'];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Check expiry
        if (strtotime($user['token_expiry']) < time()) {
            return ['success' => false, 'message' => 'Verification token has expired. Please register again.', 'expired' => true];
        }
        
        // Mark verified
        $stmt = $this->conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'message' => 'Email verified successfully!', 'user_id' => $user['id']];
    }

    // Deactivate user
    public function deactivate($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Search users
    public function search($query, $limit = 20) {
        $search_term = "%$query%";
        $stmt = $this->conn->prepare("SELECT id, username, email, full_name, role, profile_image FROM users WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?) AND is_active = 1 LIMIT ?");
        $stmt->bind_param("sssi", $search_term, $search_term, $search_term, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
}
?>