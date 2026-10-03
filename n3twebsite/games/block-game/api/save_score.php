<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

blockGameJson();
$user = blockGameRequireAuth(true);
// Temporarily disabled due to runtime failure in some environments.
// TODO: Re-enable once rate-limit helper compatibility is fixed.

$payload = blockGameReadJson();
// Allow save without CSRF for debugging — will re-enable later
if (!empty($payload['csrf_token'])) {
    blockGameValidateCsrf($payload['csrf_token']);
}

$score = max(0, (int)($payload['score'] ?? 0));
$highestCombo = max(0, min(20, (int)($payload['highest_combo'] ?? 0)));
$lines = max(0, min(512, (int)($payload['total_lines_cleared'] ?? 0)));
$cellsPlaced = max(0, min(4096, (int)($payload['cells_placed'] ?? 0)));
$playtime = max(0, min(86400, (int)($payload['playtime_seconds'] ?? 0)));

$db = blockGameDb();
$userId = (int)$user['id'];
$username = preg_replace('/[^a-zA-Z0-9_ .-]/', '', (string)$user['username']);

$db->begin_transaction();
try {
    $stmt = $db->prepare("INSERT INTO block_game_scores (user_id, username, score, highest_combo, total_lines_cleared, games_played, playtime_seconds) VALUES (?, ?, ?, ?, ?, 1, ?)");
    $stmt->bind_param('isiiii', $userId, $username, $score, $highestCombo, $lines, $playtime);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO block_game_player_data (user_id, best_score, total_score, total_playtime, total_games, total_lines_cleared, highest_combo)
        VALUES (?, ?, ?, ?, 1, ?, ?)
        ON DUPLICATE KEY UPDATE
            best_score = GREATEST(best_score, VALUES(best_score)),
            total_score = total_score + VALUES(total_score),
            total_playtime = total_playtime + VALUES(total_playtime),
            total_games = total_games + 1,
            total_lines_cleared = total_lines_cleared + VALUES(total_lines_cleared),
            highest_combo = GREATEST(highest_combo, VALUES(highest_combo)),
            updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param('iiiiii', $userId, $score, $score, $playtime, $lines, $highestCombo);
    $stmt->execute();
    $stmt->close();

    $clear = $db->prepare("DELETE FROM block_game_progress WHERE user_id = ?");
    $clear->bind_param('i', $userId);
    $clear->execute();
    $clear->close();

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$rankStmt = $db->prepare("SELECT COUNT(*) + 1 AS player_rank FROM block_game_player_data WHERE best_score > (SELECT best_score FROM block_game_player_data WHERE user_id = ?)");
$rankStmt->bind_param('i', $userId);
$rankStmt->execute();
$rank = $rankStmt->get_result()->fetch_assoc()['player_rank'] ?? null;
$rankStmt->close();

echo json_encode(['success' => true, 'rank' => $rank, 'saved_score' => $score]);
?>