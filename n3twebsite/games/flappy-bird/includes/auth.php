<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';

function flappyBirdCurrentUser(): ?array
{
    return isLoggedIn() ? getCurrentUser() : null;
}

function flappyBirdRequireAuth(bool $json = false): array
{
    $user = flappyBirdCurrentUser();
    if ($user) return $user;

    if ($json) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }

    setFlashMessage('error', 'Please log in to play Flappy Bird.');
    redirect(SITE_URL . 'login.php');
}

function flappyBirdJson(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

function flappyBirdReadJson(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function flappyBirdValidateCsrf(?string $token): void
{
    if (!$token || !verifyCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh and try again.']);
        exit;
    }
}