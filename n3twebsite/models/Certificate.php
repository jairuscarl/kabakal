<?php
// Certificate Model

class Certificate {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Generate certificate for completed course
    public function generateCertificate($user_id, $course_id) {
        // Verify course is completed
        $stmt = $this->conn->prepare("SELECT id FROM completed_courses WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        $stmt->close();
        
        // Get user and course details
        $stmt = $this->conn->prepare("SELECT u.full_name, c.title FROM users u JOIN courses c ON c.id = ? WHERE u.id = ?");
        $stmt->bind_param("ii", $course_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        // Generate unique certificate code
        $certificate_code = generateCertificateCode();
        
        // Check if certificate already exists
        $stmt = $this->conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            return false; // Certificate already exists
        }
        $stmt->close();
        
        // Insert certificate
        $stmt = $this->conn->prepare("INSERT INTO certificates (certificate_code, user_id, course_id, user_name, course_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $certificate_code, $user_id, $course_id, $data['full_name'], $data['title']);
        
        if ($stmt->execute()) {
            $certificate_id = $this->conn->insert_id;
            $stmt->close();
            return [
                'id' => $certificate_id,
                'code' => $certificate_code
            ];
        }
        
        $stmt->close();
        return false;
    }
    
    // Get certificate by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM certificates WHERE id = ? AND is_valid = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $cert = $result->fetch_assoc();
            $stmt->close();
            return $cert;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get certificate by code
    public function getByCode($code) {
        $stmt = $this->conn->prepare("SELECT * FROM certificates WHERE certificate_code = ? AND is_valid = 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $cert = $result->fetch_assoc();
            $stmt->close();
            return $cert;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get all certificates for a user
    public function getUserCertificates($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM certificates WHERE user_id = ? AND is_valid = 1 ORDER BY issued_date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $certificates = [];
        while ($row = $result->fetch_assoc()) {
            $certificates[] = $row;
        }
        
        $stmt->close();
        return $certificates;
    }
    
    // Check if user has certificate for a course
    public function hasCertificate($user_id, $course_id) {
        $stmt = $this->conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ? AND is_valid = 1");
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_cert = $result->num_rows > 0;
        $stmt->close();
        return $has_cert;
    }
    
    // Get certificate count
    public function getCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM certificates WHERE is_valid = 1");
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Get certificates per month
    public function getCertificatesPerMonth($months = 12) {
        $stmt = $this->conn->prepare("SELECT DATE_FORMAT(issued_date, '%Y-%m') as month, COUNT(*) as count 
                                      FROM certificates 
                                      WHERE issued_date >= DATE_SUB(NOW(), INTERVAL ? MONTH) AND is_valid = 1
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
    
    // Verify certificate
    public function verifyCertificate($code) {
        $cert = $this->getByCode($code);
        
        if (!$cert) {
            return [
                'valid' => false,
                'message' => 'Certificate not found or invalid'
            ];
        }
        
        return [
            'valid' => true,
            'certificate' => $cert
        ];
    }
    
    // Invalidate certificate
    public function invalidate($id) {
        $stmt = $this->conn->prepare("UPDATE certificates SET is_valid = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    // Get all certificates (admin)
    public function getAll($limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM certificates WHERE is_valid = 1 ORDER BY issued_date DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $certificates = [];
        while ($row = $result->fetch_assoc()) {
            $certificates[] = $row;
        }
        
        $stmt->close();
        return $certificates;
    }
}
?>