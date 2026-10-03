<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function blockGameCurrentUser(): ?array
{
    return isLoggedIn() ? getCurrentUser() : null;
}

function blockGameRequireAuth(bool $json = false): array
{
    $user = blockGameCurrentUser();
    if ($user) {
        return $user;
    }

    if ($json) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }

    setFlashMessage('error', 'Please log in to play Block Game.');
    redirect(SITE_URL . 'login.php');
}

function blockGameJson(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

function blockGameReadJson(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function blockGameValidateCsrf(?string $token): void
{
    if (!$token || !verifyCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh and try again.']);
        exit;
    }
}

function blockGameApiRateLimit(string $action, int $limit = 40, int $timeframe = 60): void
{
    if (!checkRateLimit('block_game_' . $action, $limit, $timeframe)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please slow down.']);
        exit;
    }
}
?>