<?php
/**
 * MediQueue — Inline-CSS HTML Email Templates
 *
 * Every function returns an HTML string.
 * All styling is inline so it renders in major email clients.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/* ──────────────────────────────────────────────────────────
   Shared layout wrapper
   ────────────────────────────────────────────────────────── */

function emailLayout(string $title, string $body): string {
    return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>' . sanitize($title) . '</title></head>
<body style="margin:0;padding:0;background:#f4f7fa;font-family:Nunito,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fa;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#2a5c7b,#3d8ab0);padding:28px 32px;text-align:center;">
        <h1 style="margin:0;color:#ffffff;font-family:Inter,Arial,sans-serif;font-size:22px;letter-spacing:0.5px;">✚ MediQueue</h1>
      </td></tr>
      <!-- Body -->
      <tr><td style="padding:32px 32px 24px;">' . $body . '</td></tr>
      <!-- Footer -->
      <tr><td style="padding:16px 32px 24px;text-align:center;color:#8c9bab;font-size:12px;">
        <p style="margin:0;">© ' . date('Y') . ' MediQueue Healthcare. All rights reserved.</p>
        <p style="margin:4px 0 0;">This is an automated message — please do not reply.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';
}

/* ──────────────────────────────────────────────────────────
   Templates
   ────────────────────────────────────────────────────────── */

/**
 * Appointment booked / confirmed notification.
 */
function emailAppointmentConfirmation(array $appointment, array $patient, array $doctor, string $status = 'confirmed'): string {
    $statusLabel = ucfirst($status);
    $statusColor = $status === 'confirmed' ? '#059669' : '#3d8ab0';
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Appointment ' . $statusLabel . '</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Your appointment has been <strong style="color:' . $statusColor . ';">' . strtolower($statusLabel) . '</strong>.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafb;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Patient</td><td>' . sanitize($patient['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Doctor</td><td>' . sanitize($doctor['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Date</td><td>' . formatDate($appointment['appointment_date']) . '</td></tr>
          <tr><td style="font-weight:600;">Time</td><td>' . formatTime($appointment['start_time']) . '</td></tr>
          <tr><td style="font-weight:600;">Status</td><td><span style="color:' . $statusColor . ';font-weight:700;">' . $statusLabel . '</span></td></tr>
        </table>
        <p style="color:#5a6a7a;font-size:13px;margin:20px 0 0;">If you need to reschedule or cancel, please log in to your MediQueue account.</p>';
    return emailLayout('Appointment ' . $statusLabel, $body);
}

/**
 * Appointment reminder notification.
 */
function emailAppointmentReminder(array $appointment, array $patient, array $doctor): string {
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Appointment Reminder</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Hi <strong>' . sanitize($patient['full_name']) . '</strong>, this is a friendly reminder about your upcoming appointment.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f0fdf4;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Doctor</td><td>Dr. ' . sanitize($doctor['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Date</td><td>' . formatDate($appointment['appointment_date']) . '</td></tr>
          <tr><td style="font-weight:600;">Time</td><td>' . formatTime($appointment['start_time']) . '</td></tr>
          <tr><td style="font-weight:600;">Status</td><td><span style="color:#059669;font-weight:700;">' . ucfirst($appointment['status'] ?? 'confirmed') . '</span></td></tr>
        </table>
        <p style="color:#5a6a7a;font-size:13px;margin:20px 0 0;">Please arrive 10 minutes early. If you need to reschedule or cancel, log in to your MediQueue account.</p>';
    return emailLayout('Appointment Reminder', $body);
}

/**
 * Appointment cancelled notification.
 */
function emailAppointmentCancellation(array $appointment, array $patient, array $doctor): string {
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Appointment Cancelled</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          The following appointment has been <strong style="color:#dc2626;">cancelled</strong>.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#fef2f2;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Patient</td><td>' . sanitize($patient['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Doctor</td><td>' . sanitize($doctor['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Date</td><td>' . formatDate($appointment['appointment_date']) . '</td></tr>
          <tr><td style="font-weight:600;">Time</td><td>' . formatTime($appointment['start_time']) . '</td></tr>
        </table>
        <p style="color:#5a6a7a;font-size:13px;margin:20px 0 0;">The time slot is now available for other patients.</p>';
    return emailLayout('Appointment Cancelled', $body);
}

/**
 * Appointment rescheduled notification.
 */
function emailAppointmentRescheduled(array $appointment, array $patient, array $doctor, string $oldDate, string $oldTime): string {
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Appointment Rescheduled</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          An appointment has been <strong style="color:#d97706;">rescheduled</strong>.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#fffbeb;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Patient</td><td>' . sanitize($patient['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Doctor</td><td>' . sanitize($doctor['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Previous</td><td><s>' . formatDate($oldDate) . ' at ' . formatTime($oldTime) . '</s></td></tr>
          <tr><td style="font-weight:600;">New Date</td><td style="color:#059669;font-weight:700;">' . formatDate($appointment['appointment_date']) . '</td></tr>
          <tr><td style="font-weight:600;">New Time</td><td style="color:#059669;font-weight:700;">' . formatTime($appointment['start_time']) . '</td></tr>
        </table>';
    return emailLayout('Appointment Rescheduled', $body);
}

/**
 * Welcome email after registration.
 */
function emailWelcome(array $user): string {
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Welcome to MediQueue!</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Hi <strong>' . sanitize($user['full_name']) . '</strong>, your account has been created successfully.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafb;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Email</td><td>' . sanitize($user['email']) . '</td></tr>
          <tr><td style="font-weight:600;">Role</td><td>' . ucfirst($user['role']) . '</td></tr>
        </table>
        <p style="color:#5a6a7a;font-size:13px;margin:20px 0 0;">You can now log in and start using MediQueue.</p>';
    return emailLayout('Welcome to MediQueue', $body);
}

/**
 * Email verification link.
 */
function emailVerification(array $user, string $token): string {
    $verifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
               . BASE_URL . '/api/auth/verify-email.php?token=' . urlencode($token) . '&email=' . urlencode($user['email']);
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Verify Your Email</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Hi <strong>' . sanitize($user['full_name']) . '</strong>, please verify your email address to complete your registration.
        </p>
        <p style="text-align:center;margin:24px 0;">
          <a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '"
             style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#2a5c7b,#3d8ab0);
                    color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;">
            Verify Email
          </a>
        </p>
        <p style="color:#8c9bab;font-size:12px;margin:16px 0 0;">If you did not create this account, please ignore this email.</p>';
    return emailLayout('Verify Your Email', $body);
}

/**
 * No-show notification email.
 */
function emailNoShow(array $appointment, array $patient, array $doctor): string {
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Missed Appointment</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Hi <strong>' . sanitize($patient['full_name']) . '</strong>, you were marked as a <strong style="color:#dc2626;">no-show</strong> for your appointment.
        </p>
        <table width="100%" cellpadding="8" cellspacing="0" style="background:#fef2f2;border-radius:8px;font-size:14px;color:#2a3b4c;">
          <tr><td style="font-weight:600;width:140px;">Doctor</td><td>' . sanitize($doctor['full_name']) . '</td></tr>
          <tr><td style="font-weight:600;">Date</td><td>' . formatDate($appointment['appointment_date']) . '</td></tr>
          <tr><td style="font-weight:600;">Time</td><td>' . formatTime($appointment['start_time']) . '</td></tr>
        </table>
        <p style="color:#5a6a7a;font-size:13px;margin:20px 0 0;">If this was a mistake, please contact the clinic or rebook through your MediQueue account.</p>';
    return emailLayout('Missed Appointment', $body);
}

/**
 * Password reset email with token link.
 */
function emailPasswordReset(string $email, string $token): string {
    $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
              . BASE_URL . '/pages/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
    $body = '
        <h2 style="margin:0 0 8px;color:#1a2b3c;font-size:20px;">Password Reset Request</h2>
        <p style="color:#5a6a7a;font-size:14px;line-height:1.6;margin:0 0 20px;">
          Click the button below to reset your password. This link expires in 1 hour.
        </p>
        <p style="text-align:center;margin:24px 0;">
          <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '"
             style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#2a5c7b,#3d8ab0);
                    color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;">
            Reset Password
          </a>
        </p>
        <p style="color:#8c9bab;font-size:12px;margin:16px 0 0;">If you did not request this, please ignore this email.</p>';
    return emailLayout('Password Reset', $body);
}
