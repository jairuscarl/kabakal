<?php
require_once __DIR__ . '/includes/auth.php';

$user = blockGameRequireAuth(false);
blockGameDb();
$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#061426">
    <title>Block Game - NetSkill</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@500;700;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/block-game.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="bg-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
    <div id="particles" class="particles" aria-hidden="true"></div>

    <header class="game-header">
        <a href="../../index.php" class="brand"><img src="../../public/images/logo.png" onerror="this.style.display='none'" alt="NetSkill"><span>NetSkill</span></a>
        <div class="game-title">
            <span class="eyebrow">Strategic Puzzle Arena</span>
            <h1>Block Game</h1>
        </div>
        <?php $gameAvatar = (!empty($user['profile_image']) && preg_match('#^https?://#', $user['profile_image']))
            ? $user['profile_image']
            : '../../public/uploads/avatars/' . htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>
        <div class="player-chip">
            <img src="<?php echo $gameAvatar; ?>" onerror="this.src='../../public/images/default-avatar.png'" alt="Avatar">
            <div><strong><?php echo htmlspecialchars($user['full_name']); ?></strong><small>@<?php echo htmlspecialchars($user['username']); ?></small></div>
        </div>
    </header>

    <main class="game-shell" data-csrf="<?php echo htmlspecialchars($csrf); ?>">
        <section class="stats-panel glass-card">
            <div class="score-card primary"><span>Score</span><strong id="score">0</strong></div>
            <div class="score-card"><span>Best</span><strong id="bestScore">0</strong></div>
            <div class="score-card"><span>Combo</span><strong id="combo">x1</strong></div>
            <div class="score-card"><span>Lines</span><strong id="lines">0</strong></div>
            <button id="newGameBtn" class="neon-btn"><i class="fas fa-rotate-right"></i> New Game</button>
            <button id="soundBtn" class="icon-btn" aria-label="Toggle sound"><i class="fas fa-volume-high"></i></button>
        </section>

        <section class="board-zone glass-card">
            <div class="board-topline">
                <div><span class="live-dot"></span> Endless Mode</div>
                <div id="saveState">Synced</div>
            </div>
            <div id="board" class="board" aria-label="8 by 8 Block Game board"></div>
            <div id="floatingText" class="floating-text"></div>
        </section>

        <aside class="pieces-panel glass-card">
            <div class="panel-heading"><span>Next Blocks</span><small>Randomized Shapes</small></div>
            <div id="pieceTray" class="piece-tray"></div>
            <p class="hint">Desktop: drag blocks onto the board. Mobile: tap a block, then tap a valid board position. Rotations and mirrored shapes are dealt randomly.</p>
        </aside>

        <aside class="leaderboard glass-card">
            <div class="panel-heading"><span><i class="fas fa-trophy"></i> Global Leaderboard</span><small id="playerRank">Rank —</small></div>
            <div id="leaderboardList" class="leaderboard-list skeleton"></div>
        </aside>
    </main>

    <footer class="game-footer glass-card">
        <div class="footer-brand-game"><img src="../../public/images/logo.png" onerror="this.style.display='none'" alt="NetSkill"><strong>NetSkill Block Game</strong></div>
        <div class="footer-actions">
            <button id="leaderboardBtn" class="mini-btn"><i class="fas fa-ranking-star"></i> Leaderboard</button>
            <button id="themeBtn" class="mini-btn"><i class="fas fa-sun"></i> Light Mode</button>
            <a class="mini-btn footer-home" href="../../index.php"><i class="fas fa-house"></i> NetSkill</a>
        </div>
        <small>Credits: NetSkill Puzzle Lab • &copy; <?php echo date('Y'); ?> NetSkill</small>
    </footer>

    <div id="gameOverModal" class="modal" role="dialog" aria-modal="true">
        <div class="modal-card">
            <button id="closeGameOverBtn" class="modal-close" aria-label="Close game over panel"><i class="fas fa-times"></i></button>
            <div class="modal-glow"></div>
            <h2>Game Over</h2>
            <p>No remaining blocks can fit. Excellent strategy run!</p>
            <div class="modal-stats">
                <div><span>Final Score</span><strong id="finalScore">0</strong></div>
                <div><span>Best Score</span><strong id="modalBest">0</strong></div>
                <div><span>Global Rank</span><strong id="modalRank">—</strong></div>
            </div>
            <button id="retryBtn" class="neon-btn wide"><i class="fas fa-play"></i> Retry</button>
            <button id="modalLeaderboardBtn" class="mini-btn wide"><i class="fas fa-trophy"></i> View Leaderboard</button>
            <a href="../../index.php" class="subtle-link">Return to NetSkill</a>
        </div>
    </div>

    <script>
        window.BLOCK_GAME = {
            csrf: <?php echo json_encode($csrf); ?>,
            apiBase: 'api/',
            user: <?php echo json_encode(['id' => (int)$user['id'], 'username' => $user['username'], 'full_name' => $user['full_name']]); ?>
        };
    </script>
    <script src="assets/js/block-game.js?v=<?php echo time(); ?>"></script>
</body>
</html>