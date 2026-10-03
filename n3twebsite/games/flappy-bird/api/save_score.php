<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

flappyBirdJson();
$user = flappyBirdRequireAuth(true);

$payload = flappyBirdReadJson();
if (!empty($payload['csrf_token'])) {
    flappyBirdValidateCsrf($payload['csrf_token']);
}

$score = max(0, (int)($payload['score'] ?? 0));
$playtime = max(0, min(86400, (int)($payload['playtime_seconds'] ?? 0)));

$db = flappyBirdDb();
$userId = (int)$user['id'];
$username = preg_replace('/[^a-zA-Z0-9_ .-]/', '', (string)$user['username']);

$db->begin_transaction();
try {
    // Insert score record
    $stmt = $db->prepare("INSERT INTO flappy_bird_scores (user_id, username, score, best_score, games_played, playtime_seconds) VALUES (?, ?, ?, ?, 1, ?)");
    $stmt->bind_param('isiii', $userId, $username, $score, $score, $playtime);
    $stmt->execute();
    $stmt->close();

    // Upsert player data
    $stmt = $db->prepare("INSERT INTO flappy_bird_player_data (user_id, best_score, total_score, total_playtime, total_games)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            best_score = GREATEST(best_score, VALUES(best_score)),
            total_score = total_score + VALUES(total_score),
            total_playtime = total_playtime + VALUES(total_playtime),
            total_games = total_games + 1,
            updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param('iiii', $userId, $score, $score, $playtime);
    $stmt->execute();
    $stmt->close();

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// Get player rank
$rankStmt = $db->prepare("SELECT COUNT(*) + 1 AS player_rank FROM flappy_bird_player_data WHERE best_score > (SELECT best_score FROM flappy_bird_player_data WHERE user_id = ?)");
$rankStmt->bind_param('i', $userId);
$rankStmt->execute();
$rank = $rankStmt->get_result()->fetch_assoc()['player_rank'] ?? null;
$rankStmt->close();

// Get best score
$bestStmt = $db->prepare("SELECT best_score FROM flappy_bird_player_data WHERE user_id = ?");
$bestStmt->bind_param('i', $userId);
$bestStmt->execute();
$bestResult = $bestStmt->get_result()->fetch_assoc();
$bestScore = $bestResult ? (int)$bestResult['best_score'] : $score;
$bestStmt->close();

echo json_encode([
    'success' => true,
    'rank' => $rank,
    'best_score' => $bestScore,
    'saved_score' => $score
]);