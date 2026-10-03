# Flappy Bird Clone

A complete Flappy Bird clone built from scratch using HTML, CSS, and Vanilla JavaScript with Canvas API.

## 🎮 Play the Game

Open `index.html` in any modern web browser to play. No build tools or dependencies required.

### Controls
- **Space bar** — Flap
- **Mouse click** — Flap
- **Touch** — Flap (mobile)

## 📁 Project Structure

```
flappy-bird/
├── index.html          # Entry point, canvas setup
├── style.css           # Responsive layout, centering
├── assets/
│   ├── images/         # (Optional) Place custom image assets here
│   └── sounds/         # (Optional) Place custom audio files here
├── src/
│   ├── game.js         # Main engine: game loop, state, audio, initialization
│   ├── bird.js         # Bird class: physics, rendering, animation
│   ├── pipe.js         # Pipe + PipeManager: generation, scrolling
│   ├── collision.js    # CollisionDetector: bird vs pipes/ground/ceiling
│   ├── score.js        # ScoreManager: tracking, animation, persistence
│   ├── ui.js           # UIManager: start screen, game over screen
│   ├── input.js        # InputManager: keyboard, mouse, touch
│   └── storage.js      # Storage: localStorage wrapper for high scores
└── README.md           # This file
```

## 🏗 Architecture

The game follows a clean, modular, object-oriented architecture:

### Responsibility Separation
| Module | Class | Responsibility |
|--------|-------|---------------|
| `game.js` | `Game` | Game loop, state machine, coordinates all systems, rendering pipeline |
| `game.js` | `AudioManager` | Programmatic sound generation via Web Audio API |
| `bird.js` | `Bird` | Bird physics, rotation, wing animation, rendering |
| `pipe.js` | `Pipe` | Individual pipe rendering and bounds |
| `pipe.js` | `PipeManager` | Pipe generation, cleanup, difficulty scaling |
| `collision.js` | `CollisionDetector` | Circle-Rect collision detection |
| `score.js` | `ScoreManager` | Score tracking, pop animation, best score |
| `ui.js` | `UIManager` | DOM-based UI overlays (start, game over) |
| `input.js` | `InputManager` | Unified input handling |
| `storage.js` | `Storage` | localStorage abstraction with error handling |

### Data Flow
1. **Input** (`InputManager`) captures user actions → calls `Game.handleJump()`
2. **Game** delegates: `Bird.jump()`, state transitions
3. **Update Loop**: Bird physics → Pipe scrolling → Score check → Collision check → Audio
4. **Render Loop**: Background → Pipes → Ground → Bird → Particles → Score overlay
5. **Collision** → `Game.handleDeath()` → Audio → Particles → UI Game Over screen
6. **Game Over** → Score saved to `localStorage` via `Storage`

### State Machine
```
LOADING → START → PLAYING → GAMEOVER → START (restart)
```

## ⚙️ Configuration

All gameplay parameters are configurable in `src/game.js` inside the `Game` constructor's `this.config` object:

```javascript
this.config = {
  gravity: 0.45,        // Falling acceleration (higher = faster fall)
  jumpForce: -7.5,      // Jump velocity (more negative = higher jump)
  birdStartX: 80,       // Bird horizontal position
  birdRadius: 15,       // Bird collision radius
  pipeSpeed: 2.5,       // Pipe scroll speed (higher = faster)
  pipeGap: 145,         // Gap between top/bottom pipes (smaller = harder)
  pipeSpacing: 200,     // Horizontal distance between pipe pairs
  pipeWidth: 52,        // Pipe width in pixels
  groundHeight: 80,     // Ground height in pixels
  difficulty: 1,        // Difficulty level 1-5
};
```

### Customization Guide

| Parameter | Effect | Recommended Range |
|-----------|--------|-------------------|
| `gravity` | How fast bird falls | 0.3 (floaty) — 0.7 (heavy) |
| `jumpForce` | Jump height | -10 (high) — -5 (low) |
| `pipeSpeed` | Game speed / difficulty | 1.5 (easy) — 4.5 (fast) |
| `pipeGap` | Gap difficulty | 170 (easy) — 100 (tight) |
| `difficulty` | Combined difficulty preset | 1 (easy) — 5 (expert) |

When using `difficulty`, it automatically adjusts `pipeSpeed` and `pipeGap`. To override, set values directly after calling `setDifficulty()`.

### Asset Replacement

To use custom images/sprites instead of the drawn graphics:
1. Place images in `assets/images/`
2. Load them in `game.js` using `new Image()`
3. Draw them in the respective `render()` methods

To use custom sound files:
1. Place `.wav` or `.mp3` files in `assets/sounds/`
2. Replace `AudioManager` methods with `new Audio('assets/sounds/filename.wav')`

## ✨ Features

- ✅ 60 FPS game loop with delta time
- ✅ Responsive design (mobile + desktop)
- ✅ Procedural pipe generation with random gaps
- ✅ Infinite scrolling background (clouds + hills)
- ✅ Bird rotation animation (tilt up/down based on velocity)
- ✅ Wing flap animation on jump
- ✅ Particle death effect
- ✅ Score pop animation on point
- ✅ Best score saved to localStorage
- ✅ Programmatic sound effects (flap, hit, score)
- ✅ Collision detection (circle vs rectangle)
- ✅ Clean restart flow
- ✅ Difficulty system (easy to expert)

## 🚀 Setup

No build tools or dependencies required!

1. Clone or download the project
2. Open `flappy-bird/index.html` in your browser
3. Start playing!

### Running locally with a server (optional)
```bash
# Using Python
python -m http.server 8000
# Then open http://localhost:8000

# Using Node
npx serve flappy-bird
```

## 🧪 Browser Support

Works on all modern browsers:
- Chrome / Edge
- Firefox
- Safari
- Mobile browsers (iOS Safari, Android Chrome)

## 📝 License

MIT — Free to use, modify, and distribute.