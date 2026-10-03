/**
 * Collision module - Handles all collision detection for the game.
 */
class CollisionDetector {
  constructor(groundHeight) {
    this.groundHeight = groundHeight;
  }

  /**
   * Check if bird collides with any pipe.
   */
  checkPipeCollision(bird, pipes, canvasHeight) {
    const birdBounds = bird.getBounds();

    for (const pipe of pipes) {
      const pipeBounds = pipe.getBounds(canvasHeight, this.groundHeight);

      // Check top pipe
      if (this.rectCircleCollision(pipeBounds.top, birdBounds)) {
        return true;
      }

      // Check bottom pipe
      if (this.rectCircleCollision(pipeBounds.bottom, birdBounds)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if bird hits the ground.
   */
  checkGroundCollision(bird, canvasHeight) {
    return bird.y + bird.radius >= canvasHeight - this.groundHeight;
  }

  /**
   * Check if bird hits the ceiling.
   */
  checkCeilingCollision(bird) {
    return bird.y - bird.radius <= 0;
  }

  /**
   * Circle vs Rectangle collision detection.
   */
  rectCircleCollision(rect, circle) {
    if (!rect || rect.height <= 0) return false;

    const closestX = Math.max(rect.x, Math.min(circle.x + circle.radius, rect.x + rect.width));
    const closestY = Math.max(rect.y, Math.min(circle.y + circle.radius, rect.y + rect.height));

    const dx = (circle.x + circle.radius) - closestX;
    const dy = (circle.y + circle.radius) - closestY;

    return (dx * dx + dy * dy) < (circle.radius * circle.radius);
  }

  /**
   * Full collision check - returns true if any collision detected.
   */
  checkAll(bird, pipes, canvasHeight) {
    if (this.checkGroundCollision(bird, canvasHeight)) {
      return 'ground';
    }
    if (this.checkCeilingCollision(bird)) {
      return 'ceiling';
    }
    if (this.checkPipeCollision(bird, pipes, canvasHeight)) {
      return 'pipe';
    }
    return null;
  }
}