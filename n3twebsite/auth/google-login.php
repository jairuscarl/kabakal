<?php
require_once __DIR__ . '/../config/google.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$googleClient->setState($state);

$authUrl = $googleClient->createAuthUrl();
header('Location: ' . $authUrl);
exit();
