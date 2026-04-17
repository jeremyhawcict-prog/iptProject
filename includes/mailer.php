<?php
/**
 * MediQueue — PHPMailer SMTP Wrapper
 *
 * Usage:
 *   $result = sendMail('to@example.com', 'Subject', '<h1>HTML body</h1>');
 *   if (!$result['success']) { error_log($result['error']); }
 */

require_once __DIR__ . '/config.php';

// PHPMailer (3 core files — no Composer autoloader)
require_once ROOT_PATH . '/vendor/phpmailer/Exception.php';
require_once ROOT_PATH . '/vendor/phpmailer/PHPMailer.php';
require_once ROOT_PATH . '/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── SMTP Configuration ───────────────────────────────────
// Credentials come from MAIL_USER / MAIL_PASS in config.php.
//
// ByetHost free tier may block outbound SMTP on port 587.
// If TLS/587 fails, switch to SSL/465:
//   define('SMTP_PORT', 465);
//   define('SMTP_SECURE', 'ssl');
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      465);
define('SMTP_SECURE',    'ssl');
define('MAIL_FROM_NAME', 'MediQueue');

/**
 * Send an HTML email via PHPMailer.
 *
 * @param  string $to       Recipient email
 * @param  string $subject  Email subject
 * @param  string $htmlBody HTML content
 * @return array  ['success' => bool, 'error' => string|null]
 */
function sendMail(string $to, string $subject, string $htmlBody): array {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Sender / recipient
        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        error_log('[MediQueue Mail] ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
