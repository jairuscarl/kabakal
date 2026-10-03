/**
 * Input module - Handles keyboard, mouse, and touch input.
 */
class InputManager {
  constructor(canvas) {
    this.canvas = canvas;
    this.onJump = null;

    this.keyDownHandler = this.handleKeyDown.bind(this);
    this.clickHandler = this.handleClick.bind(this);
    this.touchHandler = this.handleTouch.bind(this);

    this.setupListeners();
  }

  setupListeners() {
    document.addEventListener('keydown', this.keyDownHandler);
    this.canvas.addEventListener('click', this.clickHandler);
    this.canvas.addEventListener('touchstart', this.touchHandler, { passive: false });
  }

  handleKeyDown(e) {
    if (e.code === 'Space' || e.key === ' ') {
      e.preventDefault();
      if (this.onJump) this.onJump();
    }
  }

  handleClick(e) {
    e.preventDefault();
    if (this.onJump) this.onJump();
  }

  handleTouch(e) {
    e.preventDefault();
    if (this.onJump) this.onJump();
  }

  destroy() {
    document.removeEventListener('keydown', this.keyDownHandler);
    this.canvas.removeEventListener('click', this.clickHandler);
    this.canvas.removeEventListener('touchstart', this.touchHandler);
  }
}