<?php
require_once '../config/config.php';
require_once '../middleware/auth.php';
require_once '../models/Review.php';

// Only allow logged in users
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to like reviews.']);
    exit();
}

$reviewModel = new Review($GLOBALS['db']);
$user = getCurrentUser();
$review_id = intval($_POST['review_id'] ?? $_GET['review_id'] ?? 0);
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if (!$review_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid review.']);
    exit();
}

if ($action === 'like') {
    $reviewModel->like($review_id, $user['id']);
} elseif ($action === 'unlike') {
    $reviewModel->unlike($review_id, $user['id']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

// Return updated like count and status
$liked = $reviewModel->userHasLiked($review_id, $user['id']);
$count = $reviewModel->getLikeCount($review_id);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $count
]);