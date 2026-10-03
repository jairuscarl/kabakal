/**
 * Pipe class - Handles pipe creation, scrolling, and rendering.
 */
class Pipe {
  constructor(config) {
    this.x = config.x;
    this.gapY = config.gapY;
    this.gapSize = config.gapSize;
    this.width = config.width || 52;
    this.speed = config.speed || 2;
    this.passed = false;
    this.scored = false;

    // Pipe colors
    this.bodyColor = '#73bf2e';
    this.darkColor = '#5a9e24';
    this.lipColor = '#8cd649';
    this.lipHeight = 20;
    this.lipExtra = 4;
  }

  update(dt) {
    this.x -= this.speed * dt * 60;
  }

  render(ctx, canvasHeight, groundHeight) {
    const topHeight = this.gapY - this.gapSize / 2;
    const bottomY = this.gapY + this.gapSize / 2;
    const bottomHeight = canvasHeight - groundHeight - bottomY;

    // Draw top pipe
    this.drawPipe(ctx, this.x, 0, this.width, topHeight, false);
    // Draw top pipe lip
    this.drawPipeLip(ctx, this.x - this.lipExtra, topHeight - this.lipHeight, this.width + this.lipExtra * 2, this.lipHeight);

    // Draw bottom pipe
    this.drawPipe(ctx, this.x, bottomY, this.width, bottomHeight, true);
    // Draw bottom pipe lip
    this.drawPipeLip(ctx, this.x - this.lipExtra, bottomY, this.width + this.lipExtra * 2, this.lipHeight);
  }

  drawPipe(ctx, x, y, width, height, isBottom) {
    if (height <= 0) return;

    ctx.fillStyle = this.bodyColor;
    ctx.fillRect(x, y, width, height);

    // Darker stripe
    ctx.fillStyle = this.darkColor;
    ctx.fillRect(x + 4, y, 6, height);

    // Highlight stripe
    ctx.fillStyle = 'rgba(255,255,255,0.15)';
    ctx.fillRect(x + width - 10, y, 6, height);

    // Border
    ctx.strokeStyle = '#4a8a1e';
    ctx.lineWidth = 2;
    ctx.strokeRect(x, y, width, height);
  }

  drawPipeLip(ctx, x, y, width, height) {
    if (height <= 0) return;

    ctx.fillStyle = this.lipColor;
    ctx.fillRect(x, y, width, height);

    ctx.strokeStyle = '#4a8a1e';
    ctx.lineWidth = 2;
    ctx.strokeRect(x, y, width, height);
  }

  getBounds(canvasHeight, groundHeight) {
    const topHeight = this.gapY - this.gapSize / 2;
    const bottomY = this.gapY + this.gapSize / 2;
    const bottomHeight = canvasHeight - groundHeight - bottomY;

    return {
      top: {
        x: this.x,
        y: 0,
        width: this.width,
        height: topHeight
      },
      bottom: {
        x: this.x,
        y: bottomY,
        width: this.width,
        height: bottomHeight
      }
    };
  }

  isOffScreen() {
    return this.x + this.width < 0;
  }

  isPastBird(birdX) {
    return this.x + this.width < birdX;
  }
}

/**
 * PipeManager class - Manages pipe generation and lifecycle.
 */
class PipeManager {
  constructor(config) {
    this.pipes = [];
    this.speed = config.pipeSpeed || 2;
    this.gapSize = config.pipeGap || 150;
    this.spacing = config.pipeSpacing || 200;
    this.pipeWidth = config.pipeWidth || 52;
    this.canvasWidth = config.canvasWidth;
    this.canvasHeight = config.canvasHeight;
    this.groundHeight = config.groundHeight || 80;
    this.spawnTimer = 0;
    this.difficulty = config.difficulty || 1;
  }

  update(dt, canvasWidth, canvasHeight, speedMultiplier = 1) {
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;
    this.currentMultiplier = speedMultiplier;

    // Update existing pipes with speed multiplier
    for (let i = this.pipes.length - 1; i >= 0; i--) {
      this.pipes[i].speed = this.speed * speedMultiplier;
      this.pipes[i].update(dt);
      if (this.pipes[i].isOffScreen()) {
        this.pipes.splice(i, 1);
      }
    }

    // Spawn new pipes
    this.spawnTimer += dt * 60 * this.difficulty;
    if (this.spawnTimer >= this.spacing) {
      this.spawnTimer = 0;
      this.spawnPipe();
    }
  }

  spawnPipe() {
    const minGapY = this.gapSize / 2 + 60;
    const maxGapY = this.canvasHeight - this.groundHeight - this.gapSize / 2 - 60;
    const gapY = minGapY + Math.random() * (maxGapY - minGapY);

    this.pipes.push(new Pipe({
      x: this.canvasWidth,
      gapY: gapY,
      gapSize: this.gapSize,
      width: this.pipeWidth,
      speed: this.speed
    }));
  }

  render(ctx) {
    for (const pipe of this.pipes) {
      pipe.render(ctx, this.canvasHeight, this.groundHeight);
    }
  }

  reset() {
    this.pipes = [];
    this.spawnTimer = 0;
  }

  setDifficulty(level) {
    this.difficulty = Math.max(1, Math.min(5, level));
    this.speed = 2 + (this.difficulty - 1) * 0.5;
    this.gapSize = Math.max(120, 150 - (this.difficulty - 1) * 8);
  }
}