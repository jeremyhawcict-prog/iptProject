<?php
/**
 * MediQueue — SMS Sender (Stub)
 *
 * This is a placeholder for SMS functionality.
 * To enable real SMS, integrate with a provider like Twilio, Semaphore, or Globe Labs.
 *
 * Usage:
 *   $result = sendSms('+639171234567', 'Your appointment is tomorrow at 9 AM.');
 */

require_once __DIR__ . '/config.php';

// SMS provider credentials (configure when you have an SMS provider)
define('SMS_ENABLED', false);
// define('TWILIO_SID',    'your_twilio_sid');
// define('TWILIO_TOKEN',  'your_twilio_token');
// define('TWILIO_FROM',   '+1234567890');

/**
 * Send an SMS message.
 *
 * @param  string $to      Phone number (E.164 format preferred, e.g. +639XXXXXXXXX)
 * @param  string $message The text message to send
 * @return array  ['success' => bool, 'error' => string|null]
 */
function sendSms(string $to, string $message): array {
    if (!SMS_ENABLED) {
        // Log the SMS that would have been sent
        error_log('[MediQueue SMS] (STUB) To: ' . $to . ' | Message: ' . $message);
        return ['success' => true, 'error' => null, 'stub' => true];
    }

    // ── Twilio example (uncomment and configure when ready) ──
    /*
    try {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
        $data = [
            'From' => TWILIO_FROM,
            'To'   => $to,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'error' => null];
        } else {
            return ['success' => false, 'error' => 'SMS API returned HTTP ' . $httpCode];
        }
    } catch (\Throwable $e) {
        error_log('[MediQueue SMS] Error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
    */

    return ['success' => false, 'error' => 'SMS provider not configured.'];
}

/**
 * Send notification based on user's reminder preference.
 * Sends email, SMS, or both depending on the preference.
 */
function sendReminderByPreference(string $preference, string $email, string $phone, string $subject, string $htmlBody, string $smsText): void {
    if ($preference === 'email' || $preference === 'both') {
        try {
            require_once __DIR__ . '/mailer.php';
            sendMail($email, $subject, $htmlBody);
        } catch (\Throwable $e) {
            error_log('[MediQueue] Email reminder failed: ' . $e->getMessage());
        }
    }

    if ($preference === 'sms' || $preference === 'both') {
        if ($phone) {
            sendSms($phone, $smsText);
        }
    }
}
