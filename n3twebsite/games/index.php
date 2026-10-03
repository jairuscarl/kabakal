<?php
require_once __DIR__ . '/../config/config.php';

$user = isLoggedIn() ? getCurrentUser() : null;
$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0a0e17">
  <title>Mini Games - NetSkill</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@500;700;900&family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: #0a0e17;
      font-family: 'Nunito', sans-serif;
      color: #e8e8e8;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 32px;
      background: rgba(10,14,23,0.9);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    header .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #e8e8e8;
    }
    header .brand img { height: 36px; }
    header .brand span { font-size: 22px; font-weight: 700; }
    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .user-info img {
      width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
      border: 2px solid #f5c842;
    }
    .user-info strong { font-size: 15px; }
    .user-info small { color: #8892a0; font-size: 13px; }
    .login-btn {
      background: #f5c842; color: #1a1a2e; text-decoration: none;
      padding: 10px 24px; border-radius: 30px; font-weight: 700; font-size: 15px;
    }
    main {
      flex: 1;
      max-width: 800px;
      margin: 0 auto;
      padding: 40px 20px;
      width: 100%;
    }
    h1 {
      font-family: 'Press Start 2P', monospace;
      font-size: 20px;
      color: #f5c842;
      text-align: center;
      margin-bottom: 8px;
      letter-spacing: 1px;
    }
    .subtitle {
      text-align: center;
      color: #8892a0;
      font-size: 16px;
      margin-bottom: 36px;
    }
    .games-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    .game-card {
      background: rgba(12,20,35,0.75);
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 20px;
      padding: 32px;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      text-decoration: none;
      color: #e8e8e8;
    }
    .game-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 40px rgba(0,0,0,0.4);
    }
    .game-card .icon {
      font-size: 56px;
      margin-bottom: 16px;
    }
    .game-card h2 {
      font-family: 'Press Start 2P', monospace;
      font-size: 16px;
      margin-bottom: 8px;
    }
    .game-card p {
      color: #8892a0;
      font-size: 14px;
      line-height: 1.5;
    }
    .game-card .play-btn {
      display: inline-block;
      margin-top: 16px;
      background: linear-gradient(135deg, #f5c842, #d4a830);
      color: #1a1a2e;
      border: none;
      padding: 10px 28px;
      border-radius: 30px;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 15px;
      text-decoration: none;
    }
    .game-card.flappy .icon { color: #4dc9f6; }
    .game-card.flappy h2 { color: #4dc9f6; }
    .game-card.block .icon { color: #20e6ff; }
    .game-card.block h2 { color: #20e6ff; }
    @media (max-width: 600px) {
      .games-grid { grid-template-columns: 1fr; }
      header { padding: 12px 16px; }
      header .brand span { display: none; }
    }
  </style>
</head>
<body>
  <header>
    <a href="../index.php" class="brand">
      <img src="../public/images/logo.png" onerror="this.style.display='none'" alt="">
      <span>NetSkill</span>
    </a>
    <div class="user-info">
      <?php if ($user): ?>
        <?php $avatar = (!empty($user['profile_image']) && preg_match('#^https?://#', $user['profile_image']))
            ? $user['profile_image']
            : '../public/uploads/avatars/' . htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>
        <img src="<?php echo $avatar; ?>" onerror="this.src='../public/images/default-avatar.png'" alt="">
        <div>
          <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
          <small>@<?php echo htmlspecialchars($user['username']); ?></small>
        </div>
      <?php else: ?>
        <a href="../login.php" class="login-btn"><i class="bi bi-box-arrow-in-right"></i> Login to Play</a>
      <?php endif; ?>
    </div>
  </header>
  <main>
    <h1>🎮 MINI GAMES</h1>
    <p class="subtitle">Choose a game to play — scores are saved to your account!</p>
    <div class="games-grid">
      <a href="block-game/index.php" class="game-card block">
        <div class="icon">🧩</div>
        <h2>Block Blast</h2>
        <p>Strategic block puzzle. Drag and place pieces to clear lines and achieve the highest combo!</p>
        <span class="play-btn">▶ Play Now</span>
      </a>
      <a href="flappy-bird/index.php" class="game-card flappy">
        <div class="icon">🐤</div>
        <h2>Flappy Bird</h2>
        <p>Classic arcade action. Tap to fly through pipes and beat your high score!</p>
        <span class="play-btn">▶ Play Now</span>
      </a>
    </div>
  </main>
</body>
</html>