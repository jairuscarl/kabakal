/**
 * Storage module - Handles localStorage for offline best score AND server sync.
 * When user is logged in (via FLAPPY_BIRD global), scores are synced to the server.
 */
class Storage {
  constructor(key = 'flappy_bird_best_score') {
    this.key = key;
    this.serverBest = 0;
    this.serverRank = null;
  }

  getBestScore() {
    // Prefer server best if available
    if (this.serverBest > 0) return this.serverBest;

    try {
      const score = localStorage.getItem(this.key);
      return score !== null ? parseInt(score, 10) : 0;
    } catch (e) {
      return 0;
    }
  }

  saveBestScore(score) {
    try {
      const current = this.getBestScore();
      // Always save to localStorage if it's a new high score
      if (score > current) {
        localStorage.setItem(this.key, score.toString());
      }
      // Always sync to server - even 0 scores, so first-time players appear on leaderboard
      this.syncToServer(score);
      return true;
    } catch (e) {
      return false;
    }
  }

  async syncToServer(score) {
    if (!window.FLAPPY_BIRD) return;

    try {
      const resp = await fetch(window.FLAPPY_BIRD.apiBase + 'save_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: window.FLAPPY_BIRD.csrf,
          score: score,
          playtime_seconds: 0
        }),
        credentials: 'same-origin'
      });
      const data = await resp.json();
      if (data.success) {
        this.serverBest = data.best_score || score;
        this.serverRank = data.rank;
        // Dispatch event so other parts of the UI can update
        document.dispatchEvent(new CustomEvent('score-synced', {
          detail: { best: this.serverBest, rank: data.rank }
        }));
      }
    } catch (e) {
      console.warn('Score sync failed:', e.message);
    }
  }

  async loadServerData() {
    if (!window.FLAPPY_BIRD) return;

    try {
      // Fetch top 10 so we can find current user if they're ranked
      const resp = await fetch(window.FLAPPY_BIRD.apiBase + 'get_leaderboard.php?limit=10', {
        credentials: 'same-origin'
      });
      const data = await resp.json();
      if (data.success) {
        this.serverRank = data.player_rank;
        // Look through all leaders to find current user
        if (data.leaders && data.leaders.length > 0) {
          for (const l of data.leaders) {
            if (l.is_current_user) {
              this.serverBest = l.score;
              break;
            }
          }
        }
        document.dispatchEvent(new CustomEvent('score-synced', {
          detail: { best: this.serverBest, rank: this.serverRank }
        }));
      }
    } catch (e) {
      console.warn('Server data load failed:', e.message);
    }
  }

  getRank() {
    return this.serverRank;
  }

  reset() {
    try {
      localStorage.removeItem(this.key);
    } catch (e) {}
  }
}