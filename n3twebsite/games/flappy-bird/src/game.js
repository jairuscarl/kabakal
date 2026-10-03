/**
 * Game class - Main game engine. Manages game loop, state, and coordinates all systems.
 *
 * CONFIGURATION — Customize game feel here:
 * ==========================================
 * GRAVITY:     Increase for faster falling, decrease for floatier feel (default: 0.45)
 * JUMP_FORCE:  More negative = higher jumps (default: -7.5)
 * PIPE_SPEED:  Higher = faster scrolling (default: 2.5)
 * PIPE_GAP:    Smaller = harder gaps (default: 145)
 * DIFFICULTY:  1=easy, 5=hard (default: 1)
 */
class Game {
  constructor() {
    this.config = {
      gravity: 0.45,
      jumpForce: -7.5,
      birdStartX: 80,
      birdRadius: 15,
      pipeSpeed: 2.5,
      pipeGap: 145,
      pipeSpacing: 200,
      pipeWidth: 52,
      groundHeight: 80,
      difficulty: 1,
    };

    this.canvas = document.getElementById('game-canvas');
    this.overlay = document.getElementById('ui-overlay');
    this.ctx = this.canvas.getContext('2d');

    this.state = 'loading';
    this.lastTime = 0;
    this.animFrameId = null;

    // Night mode (activates at 20 points)
    this.nightMode = false;
    this.nightTransition = 0;
    this.nightScore = 20;

    // Logo cloud
    this.logoCloud = { x: 120, y: 60 };

    // Background scrolling (clouds and hills)
    this.bgOffset = 0;
    this.groundOffset = 0;
    this.bgSpeed = 0.3;
    this.groundSpeed = 2;

    // Death effect
    this.deathTimer = 0;
    this.deathParticles = [];

    // Cloud positions for smooth, non-glitching scrolling
    this.clouds = [];
    this.initClouds();

    // Systems
    this.storage = new Storage();
    this.scoreManager = new ScoreManager(this.storage);
    this.collisionDetector = new CollisionDetector(this.config.groundHeight);
    this.audioManager = new AudioManager();
    this.leaderboard = new Leaderboard();

    // Score tracking for death
    this.lastSavedScore = 0;

    this.createGroundPattern();
    this.setupCanvas();
    this.setupUI();
    this.setupInput();
    this.initGame();
  }

  initClouds() {
    this.clouds = [];
    // Pre-generate clouds at fixed positions for smooth parallax
    const cloudDefs = [
      { x: 40, y: 70, w: 80, h: 30 },
      { x: 200, y: 100, w: 65, h: 25 },
      { x: 340, y: 55, w: 75, h: 28 },
      { x: 100, y: 130, w: 55, h: 22 },
      { x: 420, y: 85, w: 90, h: 32 },
      { x: 550, y: 110, w: 60, h: 24 },
      { x: 680, y: 65, w: 70, h: 26 },
      { x: 800, y: 120, w: 85, h: 30 },
    ];
    for (const c of cloudDefs) {
      this.clouds.push({ ...c, speed: 0.15 + Math.random() * 0.2, alpha: 0.4 + Math.random() * 0.3 });
    }
  }

  createGroundPattern() {
    this.groundCanvas = document.createElement('canvas');
    this.groundCanvas.width = 24;
    this.groundCanvas.height = 24;
    const gCtx = this.groundCanvas.getContext('2d');

    // Pixel-style dirt
    gCtx.fillStyle = '#7a5230';
    gCtx.fillRect(0, 0, 24, 24);

    // Grass top edge
    gCtx.fillStyle = '#5a9e24';
    gCtx.fillRect(0, 0, 24, 4);

    // Pixel dirt details
    gCtx.fillStyle = '#6a4220';
    gCtx.fillRect(2, 8, 4, 4);
    gCtx.fillRect(14, 6, 4, 4);
    gCtx.fillRect(8, 16, 4, 4);
    gCtx.fillRect(18, 14, 4, 4);
  }

  setupCanvas() {
    this.resizeCanvas();
    window.addEventListener('resize', () => this.resizeCanvas());
  }

  resizeCanvas() {
    const container = document.getElementById('game-container-inner');
    if (!container) return;

    const rect = container.getBoundingClientRect();
    const containerWidth = rect.width;
    const containerHeight = rect.height;

    // Maintain 3:4 aspect ratio (480x720)
    const aspectRatio = 480 / 720;
    let displayWidth = containerWidth;
    let displayHeight = containerWidth / aspectRatio;

    if (displayHeight > containerHeight) {
      displayHeight = containerHeight;
      displayWidth = displayHeight * aspectRatio;
    }

    this.canvas.style.width = `${Math.floor(displayWidth)}px`;
    this.canvas.style.height = `${Math.floor(displayHeight)}px`;

    this.canvas.width = 480;
    this.canvas.height = 720;
    this.width = 480;
    this.height = 720;
  }

  setupUI() {
    // Wire up DOM elements
    this.newGameBtn = document.getElementById('newGameBtn');
    if (this.newGameBtn) this.newGameBtn.addEventListener('click', () => this.handleUserStart());

    this.soundBtn = document.getElementById('soundBtn');
    if (this.soundBtn) {
      this.soundBtn.addEventListener('click', () => {
        this.audioManager.toggleMute();
        this.soundBtn.innerHTML = this.audioManager.muted
          ? '<i class="fas fa-volume-xmark"></i>'
          : '<i class="fas fa-volume-high"></i>';
      });
    }

    // Modal elements
    this.gameOverModal = document.getElementById('gameOverModal');
    this.finalScoreEl = document.getElementById('finalScore');
    this.modalBestEl = document.getElementById('modalBest');
    this.modalRankEl = document.getElementById('modalRank');
    this.retryBtn = document.getElementById('retryBtn');
    this.closeModalBtn = document.getElementById('closeGameOverBtn');
    this.leaderboardBtn = document.getElementById('leaderboardBtn');
    this.modalLeaderboardBtn = document.getElementById('modalLeaderboardBtn');

    if (this.retryBtn) this.retryBtn.addEventListener('click', () => this.restartGame());
    if (this.closeModalBtn) this.closeModalBtn.addEventListener('click', () => this.hideGameOverModal());
    if (this.leaderboardBtn || this.modalLeaderboardBtn) {
      const toggleLB = () => this.toggleLeaderboard();
      if (this.leaderboardBtn) this.leaderboardBtn.addEventListener('click', toggleLB);
      if (this.modalLeaderboardBtn) this.modalLeaderboardBtn.addEventListener('click', toggleLB);
    }

    // Listen for score sync from server - update stats AND reload leaderboard
    document.addEventListener('score-synced', (e) => {
      this.updateUIStats(e.detail.best, e.detail.rank);
      this.leaderboard.load();
    });

    // Load server data on start - populate best score, rank, and leaderboard
    setTimeout(() => {
      this.storage.loadServerData();
      this.leaderboard.load();
    }, 500);
  }

  updateUIStats(bestScore, rank) {
    const bestEl = document.getElementById('bestScore');
    const rankEl = document.getElementById('playerRank');
    const rankLabel = document.getElementById('playerRankLabel');

    if (bestEl) bestEl.textContent = bestScore || this.scoreManager.getBestScore();
    if (rankEl) rankEl.textContent = rank ? `#${rank}` : '—';
    if (rankLabel) rankLabel.textContent = rank ? `Rank #${rank}` : 'Rank —';
  }

  toggleLeaderboard() {
    const lb = document.querySelector('.leaderboard');
    if (!lb) return;
    lb.classList.toggle('active');
    if (lb.classList.contains('active')) {
      this.leaderboard.load();
    }
  }

  setupInput() {
    this.inputManager = new InputManager(this.canvas);
    this.inputManager.onJump = () => this.handleJump();
  }

  initGame() {
    this.bird = new Bird({
      startX: this.config.birdStartX,
      startY: this.height / 2,
      radius: this.config.birdRadius,
      gravity: this.config.gravity,
      jumpForce: this.config.jumpForce,
    });

    this.pipeManager = new PipeManager({
      pipeSpeed: this.config.pipeSpeed,
      pipeGap: this.config.pipeGap,
      pipeSpacing: this.config.pipeSpacing,
      pipeWidth: this.config.pipeWidth,
      canvasWidth: this.width,
      canvasHeight: this.height,
      groundHeight: this.config.groundHeight,
      difficulty: this.config.difficulty,
    });

    this.state = 'start';
    this.lastSavedScore = 0;
  }

  handleUserStart() {
    if (this.state === 'start') {
      this.startGame();
    } else if (this.state === 'gameover') {
      this.restartGame();
    }
  }

  startGame() {
    this.state = 'playing';
    this.audioManager.playFlap();
    this.bird.jump();
    this.startLoop();
  }

  restartGame() {
    this.hideGameOverModal();
    this.scoreManager.reset();
    this.pipeManager.reset();
    this.bird.reset(this.height / 2);
    this.deathParticles = [];
    this.deathTimer = 0;
    this.lastSavedScore = 0;
    this.state = 'start';

    // Reset UI
    const scoreEl = document.getElementById('score');
    const bestEl = document.getElementById('bestScore');
    if (scoreEl) scoreEl.textContent = '0';
    if (bestEl) bestEl.textContent = this.scoreManager.getBestScore();

    this.stopLoop();

    // Small delay then auto-show start hint via render
  }

  handleJump() {
    if (this.state === 'start') {
      this.startGame();
      return;
    }
    if (this.state === 'playing') {
      this.bird.jump();
      this.audioManager.playFlap();
    }
  }

  startLoop() {
    if (this.animFrameId) return;
    this.lastTime = performance.now();
    this.loop(this.lastTime);
  }

  stopLoop() {
    if (this.animFrameId) {
      cancelAnimationFrame(this.animFrameId);
      this.animFrameId = null;
    }
  }

  loop = (currentTime) => {
    this.animFrameId = requestAnimationFrame(this.loop);

    const rawDt = (currentTime - this.lastTime) / 1000;
    this.lastTime = currentTime;
    const dt = Math.min(rawDt, 0.05);

    if (this.state === 'playing') {
      this.update(dt);
    }

    if (this.state === 'gameover') {
      this.updateDeathEffect(dt);
    }

    this.render();
  };

  update(dt) {
    this.bird.update(dt, this.height, this.config.groundHeight);

    // Gradual speed increase - gets faster and faster over time
    // Every 5 points = +3% speed. At 100 points = +60% speed.
    const score = this.scoreManager.getScore();
    const speedScale = 1 + Math.floor(score / 5) * 0.03;

    this.pipeManager.update(dt, this.width, this.height, speedScale);

    // Night mode check - activate at 20 points, revert on restart
    if (!this.nightMode && score >= this.nightScore) {
      this.nightMode = true;
      this.nightTransition = 0;
    }
    if (this.nightMode) {
      this.nightTransition = Math.min(1, this.nightTransition + dt * 0.5);
    } else {
      this.nightTransition = Math.max(0, this.nightTransition - dt * 0.5);
    }

    // Apply speed: base + night bonus + gradual scaling
    const nightBonus = 1 + this.nightTransition * 0.15;
    this.bgSpeed = 0.3 * nightBonus * speedScale;
    this.groundSpeed = 2 * nightBonus * speedScale;

    // Scrolling backgrounds
    this.bgOffset = (this.bgOffset + this.bgSpeed * dt * 60) % this.width;
    this.groundOffset = (this.groundOffset + this.groundSpeed * dt * 60) % 24;

    // Update cloud positions for smooth scrolling
    for (const cloud of this.clouds) {
      cloud.x -= cloud.speed * dt * 60;
      if (cloud.x + cloud.w < -50) {
        // Wrap cloud to right side with new random properties
        cloud.x = this.width + 20 + Math.random() * 100;
        cloud.y = 40 + Math.random() * 100;
        cloud.w = 50 + Math.random() * 50;
        cloud.h = 20 + Math.random() * 14;
        cloud.speed = 0.15 + Math.random() * 0.2;
        cloud.alpha = 0.4 + Math.random() * 0.3;
      }
    }

    this.checkScoring();

    const collision = this.collisionDetector.checkAll(
      this.bird,
      this.pipeManager.pipes,
      this.height
    );

    if (collision) {
      this.handleDeath(collision);
    }

    this.scoreManager.update(dt);
    this.audioManager.update(dt);
  }

  checkScoring() {
    for (const pipe of this.pipeManager.pipes) {
      if (!pipe.scored && pipe.isPastBird(this.bird.x)) {
        pipe.scored = true;
        this.scoreManager.increment();
        this.audioManager.playScore();

        // Update DOM score
        const scoreEl = document.getElementById('score');
        if (scoreEl) scoreEl.textContent = this.scoreManager.getScore();
      }
    }
  }

  handleDeath(collisionType) {
    this.state = 'gameover';
    this.bird.die();
    this.audioManager.playHit();

    this.createDeathParticles();

    if (collisionType === 'ground') {
      this.deathTimer = 20;
    } else {
      this.deathTimer = 60;
    }

    // Save current score to server
    const currentScore = this.scoreManager.getScore();
    if (currentScore > this.lastSavedScore) {
      this.lastSavedScore = currentScore;
      this.storage.saveBestScore(currentScore);
    }

    // Update best score in DOM
    const bestEl = document.getElementById('bestScore');
    if (bestEl) bestEl.textContent = this.scoreManager.getBestScore();

    // Show game over modal after delay
    setTimeout(() => {
      this.showGameOverModal();
    }, 600);
  }

  showGameOverModal() {
    if (!this.gameOverModal) return;

    if (this.finalScoreEl) this.finalScoreEl.textContent = this.scoreManager.getScore();
    if (this.modalBestEl) this.modalBestEl.textContent = this.scoreManager.getBestScore();
    if (this.modalRankEl) {
      const rank = this.storage.getRank();
      this.modalRankEl.textContent = rank ? `#${rank}` : '—';
    }

    this.gameOverModal.classList.add('active');

    // Load leaderboard after showing modal
    setTimeout(() => this.leaderboard.load(), 100);
  }

  hideGameOverModal() {
    if (this.gameOverModal) {
      this.gameOverModal.classList.remove('active');
    }
  }

  createDeathParticles() {
    for (let i = 0; i < 12; i++) {
      this.deathParticles.push({
        x: this.bird.x,
        y: this.bird.y,
        vx: (Math.random() - 0.5) * 10,
        vy: (Math.random() - 0.5) * 10 - 3,
        life: 30 + Math.random() * 30,
        size: 3 + Math.random() * 4,
        color: ['#f5c842', '#e8a317', '#ff4757', '#fff'][Math.floor(Math.random() * 4)],
      });
    }
  }

  updateDeathEffect(dt) {
    if (this.deathTimer > 0) {
      this.deathTimer -= dt * 60;
      if (this.deathTimer < 0) this.deathTimer = 0;
    }

    for (let i = this.deathParticles.length - 1; i >= 0; i--) {
      const p = this.deathParticles[i];
      p.x += p.vx * dt * 60;
      p.y += p.vy * dt * 60;
      p.vy += 0.3 * dt * 60;
      p.life -= dt * 60;
      if (p.life <= 0) {
        this.deathParticles.splice(i, 1);
      }
    }
  }

  render() {
    const ctx = this.ctx;
    ctx.save();

    ctx.imageSmoothingEnabled = false;
    ctx.clearRect(0, 0, this.width, this.height);

    this.renderBackground(ctx);
    this.renderClouds(ctx);
    this.renderLogoCloud(ctx);
    this.renderScrollingBg(ctx);
    this.pipeManager.render(ctx);
    this.renderGround(ctx);
    this.bird.render(ctx, this.config.groundHeight);
    this.renderDeathParticles(ctx);

    // Night mode overlay
    if (this.nightTransition > 0) {
      ctx.fillStyle = `rgba(10, 14, 35, ${this.nightTransition * 0.5})`;
      ctx.fillRect(0, 0, this.width, this.height);
      // Stars in night mode
      if (this.nightTransition > 0.3) {
        ctx.fillStyle = `rgba(255, 255, 255, ${this.nightTransition * 0.6})`;
        const starSeed = 12345;
        for (let i = 0; i < 30; i++) {
          const sx = ((i * 137 + starSeed) % this.width);
          const sy = ((i * 89 + starSeed * 3) % (this.height * 0.5));
          ctx.fillRect(sx, sy, 2, 2);
        }
      }
    }

    // Score
    if (this.state === 'playing' || this.state === 'gameover') {
      this.scoreManager.render(ctx, this.width);
    }

    if (this.state === 'start') {
      this.renderStartHint(ctx);
    }

    ctx.restore();
  }

  renderBackground(ctx) {
    const groundY = this.height - this.config.groundHeight;
    const t = this.nightTransition;
    // Day sky
    const r1 = Math.round(77 + (10 - 77) * t);
    const g1 = Math.round(201 + (14 - 201) * t);
    const b1 = Math.round(246 + (35 - 246) * t);
    const r2 = Math.round(112 + (10 - 112) * t);
    const g2 = Math.round(197 + (14 - 197) * t);
    const b2 = Math.round(206 + (35 - 206) * t);
    const r3 = Math.round(168 + (20 - 168) * t);
    const g3 = Math.round(230 + (30 - 230) * t);
    const b3 = Math.round(207 + (50 - 207) * t);

    const gradient = ctx.createLinearGradient(0, 0, 0, groundY);
    gradient.addColorStop(0, `rgb(${r1},${g1},${b1})`);
    gradient.addColorStop(0.5, `rgb(${r2},${g2},${b2})`);
    gradient.addColorStop(1, `rgb(${r3},${g3},${b3})`);
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, this.width, groundY);
  }

  renderLogoCloud(ctx) {
    // Scrolling N3TSKILL logo cloud - appears in the sky
    this.logoCloud.x -= 0.4;
    if (this.logoCloud.x + 200 < 0) {
      this.logoCloud.x = this.width + 20;
      this.logoCloud.y = 40 + Math.random() * 60;
    }
    const lx = Math.round(this.logoCloud.x);
    const ly = Math.round(this.logoCloud.y);

    // White cloud background
    ctx.globalAlpha = 0.35;
    ctx.fillStyle = '#fff';
    ctx.fillRect(lx, ly, 180, 28);
    ctx.fillRect(lx + 10, ly - 6, 160, 40);
    ctx.fillRect(lx + 20, ly - 12, 140, 50);

    // "N3TSKILL" text on the cloud
    ctx.globalAlpha = 0.7;
    ctx.font = '18px "Press Start 2P", monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#1a1a3e';
    ctx.fillText('N3TSKILL', lx + 90, ly + 14);
    ctx.globalAlpha = 1;
  }

  renderClouds(ctx) {
    for (const cloud of this.clouds) {
      ctx.globalAlpha = cloud.alpha * (1 - this.nightTransition * 0.4);
      ctx.fillStyle = this.nightTransition > 0.5 ? '#556' : '#fff';

      const x = cloud.x;
      const y = cloud.y;
      const w = cloud.w;
      const h = cloud.h;

      ctx.fillRect(x, y + h * 0.4, w * 0.2, h * 0.4);
      ctx.fillRect(x + w * 0.15, y + h * 0.2, w * 0.2, h * 0.6);
      ctx.fillRect(x + w * 0.3, y, w * 0.2, h * 0.8);
      ctx.fillRect(x + w * 0.45, y + h * 0.15, w * 0.25, h * 0.65);
      ctx.fillRect(x + w * 0.65, y + h * 0.3, w * 0.2, h * 0.5);
      ctx.fillRect(x + w * 0.8, y + h * 0.4, w * 0.2, h * 0.4);
    }
    ctx.globalAlpha = 1;
  }

  renderScrollingBg(ctx) {
    // Distant hills - pixel style
    const groundY = this.height - this.config.groundHeight;
    ctx.fillStyle = '#8ec87c';

    const hillPositions = [0, 100, 200, 300, 400, 500, 600];
    for (const pos of hillPositions) {
      const hx = pos - (this.bgOffset * 0.4) % 100;
      if (hx > -80 && hx < this.width + 80) {
        // Pixel hill using rectangles
        ctx.fillRect(hx, groundY - 40, 10, 40);
        ctx.fillRect(hx + 10, groundY - 60, 10, 60);
        ctx.fillRect(hx + 20, groundY - 72, 10, 72);
        ctx.fillRect(hx + 30, groundY - 80, 10, 80);
        ctx.fillRect(hx + 40, groundY - 72, 10, 72);
        ctx.fillRect(hx + 50, groundY - 60, 10, 60);
        ctx.fillRect(hx + 60, groundY - 40, 10, 40);
      }
    }
  }

  renderGround(ctx) {
    const groundY = this.height - this.config.groundHeight;

    // Ground base
    ctx.fillStyle = '#7a5230';
    ctx.fillRect(0, groundY, this.width, this.config.groundHeight);

    // Pixel ground pattern
    const pattern = ctx.createPattern(this.groundCanvas, 'repeat');
    ctx.fillStyle = pattern;
    ctx.save();
    ctx.translate(-Math.floor(this.groundOffset), 0);
    ctx.fillRect(0, groundY, this.width + 48, this.config.groundHeight);
    ctx.restore();

    // Grass line - pixel perfect
    ctx.fillStyle = '#5a9e24';
    ctx.fillRect(0, groundY, this.width, 4);

    // Darker grass edge
    ctx.fillStyle = '#4a8a1e';
    ctx.fillRect(0, groundY, this.width, 1);

    // Light grass highlight
    ctx.fillStyle = '#6ab82a';
    ctx.fillRect(0, groundY, this.width, 2);
  }

  renderDeathParticles(ctx) {
    for (const p of this.deathParticles) {
      const alpha = Math.max(0, p.life / 35);
      ctx.globalAlpha = alpha;
      ctx.fillStyle = p.color;
      // Pixel particles
      const s = Math.round(p.size * alpha);
      ctx.fillRect(Math.round(p.x - s/2), Math.round(p.y - s/2), s, s);
    }
    ctx.globalAlpha = 1;
  }

  renderStartHint(ctx) {
    ctx.save();

    // Background bar
    ctx.fillStyle = 'rgba(0,0,0,0.3)';
    ctx.fillRect(0, this.height - this.config.groundHeight - 60, this.width, 50);

    // Pixel font text
    const text = 'TAP / SPACE TO FLAP';
    ctx.font = '14px "Press Start 2P", monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    const x = this.width / 2;
    const y = this.height - this.config.groundHeight - 35;

    // Shadow
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.fillText(text, x + 2, y + 2);

    // Main
    ctx.fillStyle = '#fff';
    ctx.fillText(text, x, y);

    ctx.restore();
  }
}

// =============== AUDIO MANAGER ===============
class AudioManager {
  constructor() {
    this.ctx = null;
    this.muted = false;
    this.volume = 0.3;
    this.cooldown = { flap: 0, hit: 0, score: 0 };
  }

  getContext() {
    if (!this.ctx) {
      try {
        this.ctx = new (window.AudioContext || window.webkitAudioContext)();
      } catch (e) {
        return null;
      }
    }
    if (this.ctx.state === 'suspended') {
      this.ctx.resume();
    }
    return this.ctx;
  }

  update(dt) {
    for (const key in this.cooldown) {
      if (this.cooldown[key] > 0) {
        this.cooldown[key] -= dt;
        if (this.cooldown[key] < 0) this.cooldown[key] = 0;
      }
    }
  }

  playFlap() {
    if (this.muted || this.cooldown.flap > 0) return;
    this.cooldown.flap = 0.1;
    const ctx = this.getContext();
    if (!ctx) return;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.type = 'sine';
    osc.frequency.setValueAtTime(400, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(600, ctx.currentTime + 0.05);
    osc.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + 0.1);

    gain.gain.setValueAtTime(this.volume * 0.5, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);

    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.12);
  }

  playHit() {
    if (this.muted || this.cooldown.hit > 0) return;
    this.cooldown.hit = 0.3;
    const ctx = this.getContext();
    if (!ctx) return;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(200, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(50, ctx.currentTime + 0.3);

    gain.gain.setValueAtTime(this.volume * 0.4, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.3);
  }

  playScore() {
    if (this.muted || this.cooldown.score > 0) return;
    this.cooldown.score = 0.15;
    const ctx = this.getContext();
    if (!ctx) return;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.type = 'sine';
    osc.frequency.setValueAtTime(800, ctx.currentTime);
    osc.frequency.setValueAtTime(1000, ctx.currentTime + 0.05);

    gain.gain.setValueAtTime(this.volume * 0.4, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);

    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.2);
  }

  toggleMute() {
    this.muted = !this.muted;
    return this.muted;
  }
}

// =============== LEADERBOARD ===============
class Leaderboard {
  constructor() {
    this.listEl = document.getElementById('leaderboardList');
    this.loading = false;
  }

  async load() {
    if (this.loading) return;
    this.loading = true;
    this.showSkeleton();

    try {
      const resp = await fetch(
        (window.FLAPPY_BIRD ? window.FLAPPY_BIRD.apiBase : 'api/') + 'get_leaderboard.php?limit=10',
        { credentials: 'same-origin' }
      );
      const data = await resp.json();

      if (data.success && data.leaders) {
        this.render(data.leaders, data.player_rank);
      } else {
        this.showEmpty();
      }
    } catch (e) {
      this.showEmpty();
    }

    this.loading = false;
  }

  showSkeleton() {
    if (!this.listEl) return;
    this.listEl.innerHTML = '';
    this.listEl.classList.add('skeleton');
  }

  showEmpty() {
    if (!this.listEl) return;
    this.listEl.classList.remove('skeleton');
    this.listEl.innerHTML = '<div style="text-align:center;padding:20px;color:#8892a0;font-size:16px;">No scores yet.<br>Be the first!</div>';
  }

  render(leaders, playerRank) {
    if (!this.listEl) return;
    this.listEl.classList.remove('skeleton');
    this.listEl.innerHTML = '';

    if (leaders.length === 0) {
      this.showEmpty();
      return;
    }

    for (const leader of leaders) {
      const item = document.createElement('div');
      item.className = 'leader-item' + (leader.is_current_user ? ' is-current' : '');

      const rankSpan = document.createElement('span');
      rankSpan.className = 'leader-rank' + (leader.rank <= 3 ? ` top-${leader.rank}` : '');
      rankSpan.textContent = `#${leader.rank}`;

      let avatarUrl = leader.profile_image || 'default-avatar.png';
      if (!avatarUrl.match(/^https?:\/\//)) {
        avatarUrl = '../../public/uploads/avatars/' + avatarUrl;
      }

      const avatarImg = document.createElement('img');
      avatarImg.className = 'leader-avatar';
      avatarImg.src = avatarUrl;
      avatarImg.onerror = function() { this.src = '../../public/images/default-avatar.png'; };
      avatarImg.alt = '';

      const nameSpan = document.createElement('span');
      nameSpan.className = 'leader-name';
      nameSpan.textContent = leader.full_name || leader.username;

      const scoreSpan = document.createElement('span');
      scoreSpan.className = 'leader-score';
      scoreSpan.textContent = leader.score;

      item.appendChild(rankSpan);
      item.appendChild(avatarImg);
      item.appendChild(nameSpan);
      item.appendChild(scoreSpan);
      this.listEl.appendChild(item);
    }

    // Update rank labels
    const rankLabel = document.getElementById('playerRankLabel');
    if (rankLabel) rankLabel.textContent = playerRank ? `Rank #${playerRank}` : 'Rank —';
  }
}

// Initialize game when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const game = new Game();
});