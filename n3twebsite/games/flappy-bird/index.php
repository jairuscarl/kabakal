<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

$user = flappyBirdRequireAuth(false);
flappyBirdDb();
$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#2c1810">
  <title>Flappy Bird - NetSkill</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
  <div class="bg-glitch" aria-hidden="true"></div>

  <header class="game-header">
    <a href="../../index.php" class="brand"><img src="../../public/images/logo.png" onerror="this.style.display='none'" alt="NetSkill"><span>NetSkill</span></a>
    <div class="game-title">
      <span class="eyebrow">Classic Arcade</span>
      <h1>Flappy Bird</h1>
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
      <div class="score-card"><span>Rank</span><strong id="playerRank">—</strong></div>
      <button id="newGameBtn" class="neon-btn"><i class="fas fa-play"></i> Start Game</button>
      <button id="soundBtn" class="icon-btn" aria-label="Toggle sound"><i class="fas fa-volume-high"></i></button>
    </section>

    <section class="game-zone glass-card">
      <div class="game-topline">
        <div><span class="live-dot"></span> Playing</div>
        <div id="saveState">Ready</div>
      </div>
      <div id="game-container-inner">
        <canvas id="game-canvas"></canvas>
        <div id="ui-overlay"></div>
      </div>
    </section>

    <aside class="leaderboard glass-card">
      <div class="panel-heading"><span><i class="fas fa-trophy"></i> Global Leaderboard</span><small id="playerRankLabel">Rank —</small></div>
      <div id="leaderboardList" class="leaderboard-list skeleton"></div>
    </aside>
  </main>

  <footer class="game-footer glass-card">
    <div class="footer-brand-game"><img src="../../public/images/logo.png" onerror="this.style.display='none'" alt="NetSkill"><strong>NetSkill Flappy Bird</strong></div>
    <div class="footer-actions">
      <button id="leaderboardBtn" class="mini-btn"><i class="fas fa-ranking-star"></i> Leaderboard</button>
      <a class="mini-btn footer-home" href="../../index.php"><i class="fas fa-house"></i> NetSkill</a>
    </div>
    <small>&copy; <?php echo date('Y'); ?> NetSkill</small>
  </footer>

  <div id="gameOverModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-card">
      <button id="closeGameOverBtn" class="modal-close" aria-label="Close"><i class="fas fa-times"></i></button>
      <div class="modal-glow"></div>
      <h2>GAME OVER</h2>
      <p>Nice try! Keep flapping!</p>
      <div class="modal-stats">
        <div><span>Score</span><strong id="finalScore">0</strong></div>
        <div><span>Best</span><strong id="modalBest">0</strong></div>
        <div><span>Rank</span><strong id="modalRank">—</strong></div>
      </div>
      <button id="retryBtn" class="neon-btn wide"><i class="fas fa-play"></i> Retry</button>
      <button id="modalLeaderboardBtn" class="mini-btn wide"><i class="fas fa-trophy"></i> Leaderboard</button>
      <a href="../../index.php" class="subtle-link">Return to NetSkill</a>
    </div>
  </div>

  <script>
    window.FLAPPY_BIRD = {
      csrf: <?php echo json_encode($csrf); ?>,
      apiBase: 'api/',
      user: <?php echo json_encode(['id' => (int)$user['id'], 'username' => $user['username'], 'full_name' => $user['full_name']]); ?>
    };
  </script>
  <script src="src/storage.js?v=<?php echo time(); ?>"></script>
  <script src="src/bird.js?v=<?php echo time(); ?>"></script>
  <script src="src/pipe.js?v=<?php echo time(); ?>"></script>
  <script src="src/collision.js?v=<?php echo time(); ?>"></script>
  <script src="src/score.js?v=<?php echo time(); ?>"></script>
  <script src="src/ui.js?v=<?php echo time(); ?>"></script>
  <script src="src/input.js?v=<?php echo time(); ?>"></script>
  <script src="src/game.js?v=<?php echo time(); ?>"></script>
</body>
</html>