<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';

function blockGameDb(): mysqli
{
    if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof mysqli)) {
        $GLOBALS['db'] = getDBConnection();
    }

    if (!$GLOBALS['db']) {
        http_response_code(500);
        die('Database connection unavailable.');
    }

    blockGameEnsureTables($GLOBALS['db']);
    return $GLOBALS['db'];
}

function blockGameEnsureTables(mysqli $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->query("CREATE TABLE IF NOT EXISTS block_game_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        score INT NOT NULL DEFAULT 0,
        highest_combo INT NOT NULL DEFAULT 0,
        total_lines_cleared INT NOT NULL DEFAULT 0,
        games_played INT NOT NULL DEFAULT 1,
        playtime_seconds INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_score (score),
        INDEX idx_user (user_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS block_game_player_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        best_score INT NOT NULL DEFAULT 0,
        total_score BIGINT NOT NULL DEFAULT 0,
        total_playtime INT NOT NULL DEFAULT 0,
        total_games INT NOT NULL DEFAULT 0,
        total_lines_cleared INT NOT NULL DEFAULT 0,
        highest_combo INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_best_score (best_score)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS block_game_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        progress_json MEDIUMTEXT NOT NULL,
        checksum VARCHAR(64) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ready = true;
}
?>