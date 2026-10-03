<?php
require_once __DIR__ . '/config.php';

if (!class_exists('Google\Client')) {
    throw new Exception('Google API Client is not installed. Please run: composer require google/apiclient vlucas/phpdotenv');
}

$clientId = env('GOOGLE_CLIENT_ID');
$clientSecret = env('GOOGLE_CLIENT_SECRET');

if (empty($clientId) || empty($clientSecret)) {
    throw new Exception('Google OAuth environment variables are missing. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Always use the dynamic host to avoid localhost redirect on live server
$baseUrl = $scheme . '://' . $host;
$redirectUri = $baseUrl . '/auth/google-callback.php';

$googleClient = new Google\Client();
$googleClient->setClientId($clientId);
$googleClient->setClientSecret($clientSecret);
$googleClient->setRedirectUri($redirectUri);
$googleClient->setAccessType('online');
$googleClient->setPrompt('select_account');
$googleClient->addScope('email');
$googleClient->addScope('profile');
