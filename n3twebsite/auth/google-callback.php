<?php
require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/../models/User.php';

function shouldRefreshGooglePhoto($currentPhoto, $newPhoto) {
    if (empty($newPhoto)) {
        return false;
    }
    if (empty($currentPhoto) || $currentPhoto === 'default-avatar.png') {
        return true;
    }
    if (preg_match('#^https?://#', $currentPhoto) && $currentPhoto !== $newPhoto) {
        return true;
    }
    return false;
}

if (isset($_GET['error'])) {
    setFlashMessage('error', 'Google login was cancelled or failed.');
    redirect('/login.php');
    exit();
}

if (!isset($_GET['code'])) {
    setFlashMessage('error', 'Invalid Google callback request.');
    redirect('/login.php');
    exit();
}

$requestState = $_GET['state'] ?? '';
$sessionState = $_SESSION['google_oauth_state'] ?? '';
if (empty($requestState) || empty($sessionState) || !hash_equals($sessionState, $requestState)) {
    unset($_SESSION['google_oauth_state']);
    setFlashMessage('error', 'Invalid OAuth state. Please try logging in again.');
    redirect('/login.php');
    exit();
}

unset($_SESSION['google_oauth_state']);

try {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception('Failed to fetch Google access token.');
    }

    $googleClient->setAccessToken($token);
    $oauth = new Google\Service\Oauth2($googleClient);
    $googleUser = $oauth->userinfo->get();

    $email = $googleUser->email ?? '';
    $fullName = $googleUser->name ?? '';
    $picture = $googleUser->picture ?? 'default-avatar.png';

    if (empty($email)) {
        throw new Exception('Google account email is missing.');
    }

    $userModel = new User($GLOBALS['db']);
    $user = $userModel->getByEmail($email);

    if (!$user) {
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', strstr($email, '@', true) ?: 'googleuser');
        $baseUsername = strtolower(substr($baseUsername, 0, 30));
        if ($baseUsername === '') {
            $baseUsername = 'googleuser';
        }

        $username = $baseUsername;
        $i = 1;
        while ($userModel->usernameExists($username)) {
            $username = substr($baseUsername, 0, 25) . $i;
            $i++;
        }

        $randomPassword = bin2hex(random_bytes(16));
        $newUserId = $userModel->create($username, $email, $randomPassword, $fullName ?: $username);

        if (!$newUserId) {
            throw new Exception('Failed to create local user account.');
        }

        if (!empty($picture)) {
            $userModel->updateProfileImage($newUserId, $picture);
        } else {
            $userModel->updateProfileImage($newUserId, 'default-avatar.png');
        }
        $userModel->updateLastLogin($newUserId);
        $user = $userModel->getById($newUserId);
    } else {
        enforceAdminRoleByEmail($GLOBALS['db'], $user['id'], $email);
        $userModel->updateProfile($user['id'], $fullName ?: $user['full_name']);
        if (shouldRefreshGooglePhoto($user['profile_image'] ?? '', $picture)) {
            $userModel->updateProfileImage($user['id'], $picture);
        }
        $userModel->updateLastLogin($user['id']);
        $user = $userModel->getById($user['id']);
    }

    enforceAdminRoleByEmail($GLOBALS['db'], $user['id'], $user['email']);
    $user = $userModel->getById($user['id']);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_image'] = $user['profile_image'];

    logActivity($GLOBALS['db'], $user['id'], 'login_google', 'User logged in with Google OAuth');
    setFlashMessage('success', 'Welcome, ' . htmlspecialchars($user['full_name']) . '!');
    redirect('/index.php');
    exit();
} catch (Throwable $e) {
    error_log('Google OAuth callback error: ' . $e->getMessage());
    setFlashMessage('error', 'Google login failed. Please try again.');
    redirect('/login.php');
    exit();
}
