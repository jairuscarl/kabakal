/**
 * Score module - Manages score tracking and display with pixel font.
 */
class ScoreManager {
  constructor(storage) {
    this.score = 0;
    this.bestScore = 0;
    this.storage = storage;
    this.scorePopTimer = 0;
    this.scorePopScale = 1;
    this.isNewBest = false;

    this.loadBestScore();
  }

  loadBestScore() {
    this.bestScore = this.storage.getBestScore();
  }

  increment() {
    this.score++;
    this.scorePopTimer = 15;
    this.scorePopScale = 1.5;
    this.isNewBest = false;

    if (this.score > this.bestScore) {
      this.bestScore = this.score;
      this.isNewBest = true;
      this.storage.saveBestScore(this.score);
    }
  }

  update(dt) {
    if (this.scorePopTimer > 0) {
      this.scorePopTimer -= dt * 60;
      if (this.scorePopTimer > 10) {
        this.scorePopScale += (1 - this.scorePopScale) * 0.3;
      } else {
        this.scorePopScale += (1 - this.scorePopScale) * 0.15;
      }
      if (this.scorePopTimer <= 0) {
        this.scorePopTimer = 0;
        this.scorePopScale = 1;
      }
    }
  }

  render(ctx, canvasWidth) {
    if (!ctx) return;

    const scale = this.scorePopScale;

    ctx.save();

    // Calculate font size - pixel font is small so we use a good size
    const baseSize = 36;
    const fontSize = Math.round(baseSize * scale);
    ctx.font = `${fontSize}px "Press Start 2P", monospace`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';

    const x = canvasWidth / 2;
    const y = 18;

    // Shadow layers (pixel shadow)
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.fillText(this.score.toString(), x + 3, y + 3);
    ctx.fillText(this.score.toString(), x + 2, y + 2);

    // Stroke (outline)
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 4;
    ctx.strokeText(this.score.toString(), x, y);

    // Main text
    ctx.fillStyle = '#fff';
    ctx.fillText(this.score.toString(), x, y);

    // New best indicator
    if (this.isNewBest && this.scorePopTimer > 5) {
      ctx.font = '12px "Press Start 2P", monospace';
      ctx.fillStyle = '#f5c842';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'top';
      ctx.fillText('BEST!', x, y + fontSize + 6);
    }

    ctx.restore();
  }

  reset() {
    this.score = 0;
    this.scorePopTimer = 0;
    this.scorePopScale = 1;
    this.isNewBest = false;
  }

  getScore() {
    return this.score;
  }

  getBestScore() {
    return this.bestScore;
  }
}