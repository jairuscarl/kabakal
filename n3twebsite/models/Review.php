<?php
// Review Model

class Review {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->ensureReplySchema();
    }

    private function ensureReplySchema() {
        $sql = "CREATE TABLE IF NOT EXISTS review_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            is_approved BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_review (review_id),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        )";
        $this->conn->query($sql);
    }
    
    // Create new review
    public function create($user_id, $course_id, $rating, $comment, $image_path = null) {
        $stmt = $this->conn->prepare("INSERT INTO reviews (user_id, course_id, rating, comment, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $user_id, $course_id, $rating, $comment, $image_path);
        
        if ($stmt->execute()) {
            $review_id = $this->conn->insert_id;
            $stmt->close();
            return $review_id;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get review by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT r.*, u.username, u.full_name, u.profile_image 
                                      FROM reviews r 
                                      JOIN users u ON r.user_id = u.id 
                                      WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $review = $result->fetch_assoc();
            $stmt->close();
            return $review;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get all approved reviews with like counts
    public function getAll($course_id = null, $limit = 20, $offset = 0) {
        $sql = "SELECT r.*, u.username, u.full_name, u.profile_image, c.title as course_title,
                (SELECT COUNT(*) FROM review_likes WHERE review_id = r.id) as like_count
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN courses c ON r.course_id = c.id 
                WHERE r.is_approved = 1";
        
        if ($course_id) {
            $sql .= " AND r.course_id = ?";
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($course_id) {
            $stmt->bind_param("iii", $course_id, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        $stmt->close();
        return $reviews;
    }

    public function createReply($review_id, $user_id, $comment) {
        $stmt = $this->conn->prepare("INSERT INTO review_replies (review_id, user_id, comment, is_approved) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("iis", $review_id, $user_id, $comment);
        $ok = $stmt->execute();
        $id = $ok ? $this->conn->insert_id : false;
        $stmt->close();
        return $id;
    }

    public function getRepliesByReviewIds($review_ids) {
        if (empty($review_ids)) return [];

        $ids = array_map('intval', $review_ids);
        $in = implode(',', $ids);
        $sql = "SELECT rr.*, u.username, u.full_name, u.profile_image
                FROM review_replies rr
                JOIN users u ON rr.user_id = u.id
                WHERE rr.is_approved = 1 AND rr.review_id IN ($in)
                ORDER BY rr.created_at ASC";

        $result = $this->conn->query($sql);
        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $rid = (int)$row['review_id'];
            if (!isset($grouped[$rid])) $grouped[$rid] = [];
            $grouped[$rid][] = $row;
        }
        return $grouped;
    }

    public function deleteReply($id) {
        $stmt = $this->conn->prepare("DELETE FROM review_replies WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getTodayPostCount($user_id) {
        $count = 0;

        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE user_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $count += (int)$row['cnt'];
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM review_replies WHERE user_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $count += (int)$row['cnt'];
        $stmt->close();

        return $count;
    }
    
    // Get user's reviews
    public function getUserReviews($user_id, $limit = 20) {
        $stmt = $this->conn->prepare("SELECT r.*, c.title as course_title 
                                      FROM reviews r 
                                      LEFT JOIN courses c ON r.course_id = c.id 
                                      WHERE r.user_id = ? 
                                      ORDER BY r.created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        $stmt->close();
        return $reviews;
    }
    
    // Get pending reviews (admin)
    public function getPendingReviews($limit = 50) {
        $stmt = $this->conn->prepare("SELECT r.*, u.username, u.full_name, c.title as course_title 
                                      FROM reviews r 
                                      JOIN users u ON r.user_id = u.id 
                                      LEFT JOIN courses c ON r.course_id = c.id 
                                      WHERE r.is_approved = 0 
                                      ORDER BY r.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        $stmt->close();
        return $reviews;
    }
    
    // Approve review
    public function approve($id) {
        $stmt = $this->conn->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Reject/delete review
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Update review
    public function update($id, $rating, $comment, $image_path = null) {
        $stmt = $this->conn->prepare("UPDATE reviews SET rating = ?, comment = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("issi", $rating, $comment, $image_path, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Get average rating for a course
    public function getAverageRating($course_id) {
        $stmt = $this->conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                                      FROM reviews 
                                      WHERE course_id = ? AND is_approved = 1");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }
        
        $stmt->close();
        return ['avg_rating' => 0, 'total_reviews' => 0];
    }
    
    // Get review count
    public function getCount($approved_only = true) {
        $sql = "SELECT COUNT(*) as count FROM reviews";
        if ($approved_only) {
            $sql .= " WHERE is_approved = 1";
        }
        
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Check if user already reviewed a course
    public function userHasReviewed($user_id, $course_id) {
        $stmt = $this->conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_reviewed = $result->num_rows > 0;
        $stmt->close();
        return $has_reviewed;
    }
    
    // Like a review
    public function like($review_id, $user_id) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO review_likes (review_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $review_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Unlike a review
    public function unlike($review_id, $user_id) {
        $stmt = $this->conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $review_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Check if user liked a review
    public function userHasLiked($review_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT id FROM review_likes WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $review_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_liked = $result->num_rows > 0;
        $stmt->close();
        return $has_liked;
    }
    
    // Get review like count
    public function getLikeCount($review_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'];
    }
    
    // Get rating distribution for a course
    public function getRatingDistribution($course_id) {
        $stmt = $this->conn->prepare("SELECT rating, COUNT(*) as count 
                                      FROM reviews 
                                      WHERE course_id = ? AND is_approved = 1
                                      GROUP BY rating ORDER BY rating DESC");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        while ($row = $result->fetch_assoc()) {
            $distribution[$row['rating']] = $row['count'];
        }
        
        $stmt->close();
        return $distribution;
    }
}
?>