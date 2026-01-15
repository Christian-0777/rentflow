<?php
// config/mailer.php
// PHPMailer SMTP configuration for sending real emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure PHPMailer is installed via Composer:
//   composer require phpmailer/phpmailer
require __DIR__ . '/../vendor/autoload.php';

// SMTP Configuration Constants
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@gmail.com');
define('MAIL_PASSWORD', 'your_app_password'); // Use an app password if 2FA is enabled
define('MAIL_FROM', 'no-reply@rentflow.local');

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
        $mail->Host       = 'smtp.gmail.com';        // SMTP server (e.g., smtp.gmail.com)
        $mail->SMTPAuth   = true;                             // Enable authentication
        $mail->Username   = 'your_email@gmail.com';         // SMTP username
        $mail->Password   = 'your_app_password';  // SMTP password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Encryption (TLS recommended)
        $mail->Port       = 587;                              // TCP port (587 for TLS, 465 for SSL)

        // Sender info
        $mail->setFrom('no-reply@rentflow.local', 'Rentflow Team');

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
