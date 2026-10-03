<?php
/**
 * Mailer helper - Sends emails via PHPMailer using Gmail SMTP.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($toEmail, $toName, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)env('SMTP_PORT', 587);

        $mail->setFrom(env('SMTP_FROM_EMAIL', env('SMTP_USERNAME')), env('SMTP_FROM_NAME', 'NetSkill'));
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
        $verifyLink = $baseUrl . '/verify.php?token=' . urlencode($token);

        $mail->Subject = 'Verify your email - NetSkill';
        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f7fc;padding:30px;">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:36px;text-align:center;">
    <h2 style="color:#1a1a2e;margin-bottom:8px;">Welcome to NetSkill!</h2>
    <p style="color:#555;font-size:15px;margin-bottom:24px;">Hi <strong>{$toName}</strong>, please verify your email to get started.</p>
    <a href="{$verifyLink}" style="display:inline-block;background:#2563eb;color:#fff;padding:14px 40px;border-radius:30px;text-decoration:none;font-weight:bold;font-size:16px;">Verify Email</a>
    <p style="color:#999;font-size:13px;margin-top:24px;">This link expires in 24 hours. If you didn't create an account, ignore this email.</p>
</div>
</body>
</html>
HTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendVerificationSuccessEmail($toEmail, $toName) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)env('SMTP_PORT', 587);

        $mail->setFrom(env('SMTP_FROM_EMAIL', env('SMTP_USERNAME')), env('SMTP_FROM_NAME', 'NetSkill'));
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;

        $mail->Subject = 'Email verified - NetSkill';
        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f7fc;padding:30px;">
<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:36px;text-align:center;">
    <div style="font-size:56px;margin-bottom:12px;">✅</div>
    <h2 style="color:#1a1a2e;margin-bottom:8px;">Email Verified!</h2>
    <p style="color:#555;font-size:15px;margin-bottom:24px;">Your email <strong>{$toEmail}</strong> is now verified. You can now log in to NetSkill.</p>
    <a href="{$baseUrl}/login.php" style="display:inline-block;background:#2563eb;color:#fff;padding:14px 40px;border-radius:30px;text-decoration:none;font-weight:bold;font-size:16px;">Login Now</a>
</div>
</body>
</html>
HTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer success email error: ' . $mail->ErrorInfo);
        return false;
    }
}