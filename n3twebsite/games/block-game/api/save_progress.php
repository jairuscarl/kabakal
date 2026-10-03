<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

blockGameJson();
$user = blockGameRequireAuth(true);
blockGameApiRateLimit('progress', 80, 60);

$payload = blockGameReadJson();
blockGameValidateCsrf($payload['csrf_token'] ?? null);

$progress = $payload['progress'] ?? [];
if (!is_array($progress)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid progress payload.']);
    exit;
}

$allowed = ['board', 'pieces', 'score', 'combo', 'linesCleared', 'startedAt'];
$clean = array_intersect_key($progress, array_flip($allowed));
$json = json_encode($clean, JSON_UNESCAPED_SLASHES);

if (!$json || strlen($json) > 60000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Progress payload too large.']);
    exit;
}

$checksum = hash_hmac('sha256', $json, session_id());
$db = blockGameDb();
$userId = (int)$user['id'];

$stmt = $db->prepare("INSERT INTO block_game_progress (user_id, progress_json, checksum) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE progress_json = VALUES(progress_json), checksum = VALUES(checksum), updated_at = CURRENT_TIMESTAMP");
$stmt->bind_param('iss', $userId, $json, $checksum);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
?>