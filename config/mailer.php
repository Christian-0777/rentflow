<?php
// config/mailer.php
// PHPMailer SMTP configuration for sending real emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
require_once __DIR__ . '/env.php';

// Make sure PHPMailer is installed via Composer:
//   composer require phpmailer/phpmailer
require __DIR__ . '/../vendor/autoload.php';

// SMTP Configuration Constants
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', 'no-reply@rentflow.local'));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', '')); // Use an app password if 2FA is enabled
define('MAIL_FROM', env('MAIL_FROM', 'no-reply@rentflow.local'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Rentflow Team'));

/**
 * Send an email using PHPMailer + SMTP
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool True if sent successfully, false otherwise
 */
function send_mail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                      // Use SMTP
        $mail->Host       = MAIL_HOST;                        // SMTP server (e.g., smtp.gmail.com)
        $mail->SMTPAuth   = true;                             // Enable authentication
        $mail->Username   = MAIL_USERNAME;                    // SMTP username
        $mail->Password   = MAIL_PASSWORD;                    // SMTP password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Encryption (TLS recommended)
        $mail->Port       = MAIL_PORT;                        // TCP port (587 for TLS, 465 for SSL)

        // Sender info
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        // Recipient
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);                   // Fallback plain text

        // Send email
        return $mail->send();

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
