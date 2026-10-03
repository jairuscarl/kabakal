<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

flappyBirdJson();
$user = flappyBirdCurrentUser();
$db = flappyBirdDb();
$limit = min(50, max(5, (int)($_GET['limit'] ?? 10)));

$sql = "SELECT pd.user_id, u.username, u.full_name, u.profile_image, pd.best_score AS score, pd.total_games, pd.total_playtime
        FROM flappy_bird_player_data pd
        INNER JOIN users u ON u.id = pd.user_id
        WHERE pd.best_score >= 0
        ORDER BY pd.best_score DESC, pd.updated_at ASC
        LIMIT ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$currentUserId = $user ? (int)$user['id'] : 0;
$leaders = [];
$rank = 1;
while ($row = $result->fetch_assoc()) {
    $row['rank'] = $rank++;
    $row['is_current_user'] = ((int)$row['user_id'] === $currentUserId);
    $leaders[] = $row;
}
$stmt->close();

// Get current player's rank
$playerRank = null;
if ($currentUserId > 0) {
    $rankStmt = $db->prepare("SELECT COUNT(*) + 1 AS player_rank FROM flappy_bird_player_data WHERE best_score > COALESCE((SELECT best_score FROM flappy_bird_player_data WHERE user_id = ?), 0)");
    $rankStmt->bind_param('i', $currentUserId);
    $rankStmt->execute();
    $playerRank = $rankStmt->get_result()->fetch_assoc()['player_rank'] ?? null;
    $rankStmt->close();
}

echo json_encode(['success' => true, 'leaders' => $leaders, 'player_rank' => $playerRank]);