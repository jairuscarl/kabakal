<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

blockGameJson();
$user = blockGameRequireAuth(true);
$db = blockGameDb();
$userId = (int)$user['id'];

$stmt = $db->prepare("SELECT best_score, total_score, total_playtime, total_games, total_lines_cleared, highest_combo FROM block_game_player_data WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stats) {
    $stmt = $db->prepare("INSERT IGNORE INTO block_game_player_data (user_id) VALUES (?)");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $stats = ['best_score' => 0, 'total_score' => 0, 'total_playtime' => 0, 'total_games' => 0, 'total_lines_cleared' => 0, 'highest_combo' => 0];
}

$rankStmt = $db->prepare("SELECT COUNT(*) + 1 AS player_rank FROM block_game_player_data WHERE best_score > (SELECT best_score FROM block_game_player_data WHERE user_id = ?)");
$rankStmt->bind_param('i', $userId);
$rankStmt->execute();
$rank = $rankStmt->get_result()->fetch_assoc()['player_rank'] ?? null;
$rankStmt->close();

$progressStmt = $db->prepare("SELECT progress_json FROM block_game_progress WHERE user_id = ?");
$progressStmt->bind_param('i', $userId);
$progressStmt->execute();
$progressRow = $progressStmt->get_result()->fetch_assoc();
$progressStmt->close();

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $userId,
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'profile_image' => $user['profile_image'] ?? 'default-avatar.png',
    ],
    'stats' => $stats,
    'rank' => $rank,
    'progress' => $progressRow ? json_decode($progressRow['progress_json'], true) : null,
]);
?>