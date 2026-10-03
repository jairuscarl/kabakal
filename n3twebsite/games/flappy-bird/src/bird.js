/**
 * Bird class - Handles bird physics, rendering, and animation (pixel art style).
 */
class Bird {
  constructor(config) {
    this.x = config.startX;
    this.y = config.startY;
    this.radius = config.radius || 15;
    this.velocity = 0;
    this.gravity = config.gravity || 0.5;
    this.jumpForce = config.jumpForce || -8;
    this.rotation = 0;
    this.flapTimer = 0;
    this.isDead = false;
    this.maxVelocity = 12;
  }

  jump() {
    if (this.isDead) return;
    this.velocity = this.jumpForce;
    this.rotation = -0.5;
    this.flapTimer = 8;
  }

  update(dt, canvasHeight, groundHeight) {
    if (this.isDead) return;

    this.velocity += this.gravity * dt * 60;
    if (this.velocity > this.maxVelocity) this.velocity = this.maxVelocity;
    this.y += this.velocity * dt * 60;

    // Rotation based on velocity
    if (this.velocity < 0) {
      this.rotation = Math.max(-0.6, this.velocity * 0.08);
    } else if (this.velocity > 0) {
      this.rotation = Math.min(Math.PI / 3, this.velocity * 0.05);
    }

    // Clamp to ceiling
    if (this.y - this.radius < 0) {
      this.y = this.radius;
      this.velocity = 0;
    }

    if (this.flapTimer > 0) {
      this.flapTimer -= dt * 60;
      if (this.flapTimer < 0) this.flapTimer = 0;
    }
  }

  render(ctx, groundHeight) {
    ctx.save();
    ctx.translate(Math.round(this.x), Math.round(this.y));
    ctx.rotate(this.rotation);
    ctx.imageSmoothingEnabled = false;

    const s = 2; // pixel size multiplier
    const wingUp = this.flapTimer > 4;

    // === BODY (yellow rectangle) ===
    ctx.fillStyle = '#f5c842';
    ctx.fillRect(-7 * s, -5 * s, 14 * s, 10 * s);

    // Body outline
    ctx.strokeStyle = '#c9a227';
    ctx.lineWidth = 1;
    ctx.strokeRect(-7 * s, -5 * s, 14 * s, 10 * s);

    // === WING ===
    ctx.fillStyle = '#e8a317';
    if (wingUp) {
      // Wing up position (flapping)
      ctx.fillRect(-7 * s, -8 * s, 6 * s, 4 * s);
    } else {
      // Wing down position
      ctx.fillRect(-7 * s, 3 * s, 6 * s, 4 * s);
    }
    ctx.strokeStyle = '#c99017';
    ctx.lineWidth = 1;
    if (wingUp) {
      ctx.strokeRect(-7 * s, -8 * s, 6 * s, 4 * s);
    } else {
      ctx.strokeRect(-7 * s, 3 * s, 6 * s, 4 * s);
    }

    // === EYE (white) ===
    ctx.fillStyle = '#fff';
    ctx.fillRect(3 * s, -6 * s, 5 * s, 5 * s);
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 1;
    ctx.strokeRect(3 * s, -6 * s, 5 * s, 5 * s);

    // === PUPIL ===
    ctx.fillStyle = '#222';
    ctx.fillRect(5 * s, -5 * s, 2 * s, 3 * s);

    // === EYE HIGHLIGHT ===
    ctx.fillStyle = '#fff';
    ctx.fillRect(6 * s, -6 * s, 1 * s, 1 * s);

    // === BEAK ===
    ctx.fillStyle = '#e85d04';
    ctx.fillRect(6 * s, -2 * s, 6 * s, 3 * s);
    ctx.strokeStyle = '#c44d00';
    ctx.lineWidth = 1;
    ctx.strokeRect(6 * s, -2 * s, 6 * s, 3 * s);

    ctx.restore();
  }

  getBounds() {
    return {
      x: this.x - this.radius,
      y: this.y - this.radius,
      width: this.radius * 2,
      height: this.radius * 2,
      radius: this.radius
    };
  }

  reset(startY) {
    this.y = startY;
    this.velocity = 0;
    this.rotation = 0;
    this.flapTimer = 0;
    this.isDead = false;
  }

  die() {
    this.isDead = true;
  }
}