/**
 * UI module - Handles all on-screen UI rendering and state management.
 */
class UIManager {
  constructor(canvas, overlay) {
    this.canvas = canvas;
    this.overlay = overlay;
    this.state = 'start'; // 'start' | 'playing' | 'gameover'
    this.onStartCallback = null;
    this.onRestartCallback = null;

    this.createStartScreen();
  }

  createStartScreen() {
    this.clearOverlay();
    this.state = 'start';
  }

  showGameOver(score, bestScore) {
    this.state = 'gameover';
    this.clearOverlay();

    const container = document.createElement('div');
    container.style.cssText = `
      text-align: center;
      background: rgba(0,0,0,0.75);
      padding: 30px 40px;
      border-radius: 16px;
      pointer-events: auto;
      animation: fadeIn 0.3s ease-out;
    `;

    // Game Over title
    const title = document.createElement('h1');
    title.textContent = 'GAME OVER';
    title.style.cssText = `
      color: #e74c3c;
      font-size: 36px;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
      letter-spacing: 2px;
    `;

    // Score display
    const scoreDisplay = document.createElement('div');
    scoreDisplay.style.cssText = `
      margin-bottom: 20px;
    `;

    const currentScore = document.createElement('p');
    currentScore.textContent = `Score: ${score}`;
    currentScore.style.cssText = `
      color: #fff;
      font-size: 24px;
      margin-bottom: 8px;
    `;

    const bestScoreLabel = document.createElement('p');
    const isNewBest = score >= bestScore && score > 0;
    bestScoreLabel.textContent = isNewBest ? `🏆 New Best: ${bestScore}` : `Best: ${bestScore}`;
    bestScoreLabel.style.cssText = `
      color: ${isNewBest ? '#f1c40f' : '#bbb'};
      font-size: 20px;
    `;

    scoreDisplay.appendChild(currentScore);
    scoreDisplay.appendChild(bestScoreLabel);

    // Restart button
    const restartBtn = document.createElement('button');
    restartBtn.textContent = '↻ Play Again';
    restartBtn.style.cssText = `
      background: linear-gradient(135deg, #f5c842, #e8a317);
      color: #333;
      border: none;
      padding: 14px 36px;
      font-size: 20px;
      border-radius: 30px;
      cursor: pointer;
      pointer-events: auto;
      font-weight: bold;
      box-shadow: 0 4px 15px rgba(245,200,66,0.4);
      transition: transform 0.15s, box-shadow 0.15s;
    `;
    restartBtn.addEventListener('mouseenter', () => {
      restartBtn.style.transform = 'scale(1.05)';
      restartBtn.style.boxShadow = '0 6px 20px rgba(245,200,66,0.6)';
    });
    restartBtn.addEventListener('mouseleave', () => {
      restartBtn.style.transform = 'scale(1)';
      restartBtn.style.boxShadow = '0 4px 15px rgba(245,200,66,0.4)';
    });
    restartBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (this.onRestartCallback) this.onRestartCallback();
    });
    // Touch support
    restartBtn.addEventListener('touchend', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (this.onRestartCallback) this.onRestartCallback();
    });

    container.appendChild(title);
    container.appendChild(scoreDisplay);
    container.appendChild(restartBtn);
    this.overlay.appendChild(container);

    // Add fade-in animation
    this.addFadeInStyle();
  }

  showStartScreen() {
    this.state = 'start';
    this.clearOverlay();

    const container = document.createElement('div');
    container.style.cssText = `
      text-align: center;
      pointer-events: auto;
      animation: fadeIn 0.4s ease-out;
    `;

    // Title
    const title = document.createElement('h1');
    title.textContent = 'Flappy Bird';
    title.style.cssText = `
      color: #fff;
      font-size: 48px;
      margin-bottom: 10px;
      text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
      letter-spacing: 3px;
    `;

    // Subtitle
    const subtitle = document.createElement('p');
    subtitle.textContent = 'Tap to Start';
    subtitle.style.cssText = `
      color: #f5c842;
      font-size: 22px;
      margin-bottom: 30px;
      animation: pulse 1.5s ease-in-out infinite;
    `;

    // Instructions
    const instructions = document.createElement('div');
    instructions.style.cssText = `
      color: rgba(255,255,255,0.7);
      font-size: 14px;
      margin-top: 10px;
      line-height: 1.8;
    `;
    instructions.innerHTML = `
      <p>🖱 Click / Tap to Flap</p>
      <p>⌨️ Press Space to Flap</p>
      <p>Avoid the pipes!</p>
    `;

    container.appendChild(title);
    container.appendChild(subtitle);
    container.appendChild(instructions);
    this.overlay.appendChild(container);

    // Make the whole screen clickable to start
    this.overlay.style.pointerEvents = 'auto';
    const startHandler = (e) => {
      e.preventDefault();
      if (this.onStartCallback) this.onStartCallback();
    };
    // Only apply click once
    const clickHandler = (e) => {
      e.stopPropagation();
      startHandler(e);
    };
    container.addEventListener('click', clickHandler);
    container.addEventListener('touchend', clickHandler);

    this.addFadeInStyle();
    this.addPulseAnimation();
  }

  showPlaying() {
    this.state = 'playing';
    this.clearOverlay();
    this.overlay.style.pointerEvents = 'none';
  }

  clearOverlay() {
    this.overlay.innerHTML = '';
    this.overlay.style.pointerEvents = 'none';
  }

  addFadeInStyle() {
    if (!document.getElementById('ui-anim-style')) {
      const style = document.createElement('style');
      style.id = 'ui-anim-style';
      style.textContent = `
        @keyframes fadeIn {
          from { opacity: 0; transform: scale(0.9); }
          to { opacity: 1; transform: scale(1); }
        }
      `;
      document.head.appendChild(style);
    }
  }

  addPulseAnimation() {
    if (!document.getElementById('ui-pulse-style')) {
      const style = document.createElement('style');
      style.id = 'ui-pulse-style';
      style.textContent = `
        @keyframes pulse {
          0%, 100% { opacity: 0.7; transform: scale(1); }
          50% { opacity: 1; transform: scale(1.05); }
        }
      `;
      document.head.appendChild(style);
    }
  }
}