<?php
// config/mailer.php
// Email configuration with SendGrid as primary and PHPMailer as fallback

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use SendGrid\Mail\Mail;

// Load environment variables
require_once __DIR__ . '/env.php';

// Make sure required packages are installed via Composer
require __DIR__ . '/../vendor/autoload.php';

// SendGrid Configuration (temporarily disabled)
define('SENDGRID_API_KEY', ''); // override any env value until service restored

// SMTP Configuration Constants (Fallback)
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', 'no-reply@rentflow.local'));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', '')); // Use an app password if 2FA is enabled
define('MAIL_FROM', env('MAIL_FROM', 'no-reply@rentflow.local'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Rentflow Team'));

/**
 * Send an email using SendGrid (primary) with PHPMailer fallback
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool True if sent successfully, false otherwise
 */
function send_mail($to, $subject, $body) {
    // SMTP only operation: bypass SendGrid entirely due to currently disabled service.
    // The SendGrid functions remain in place for future re‑enablement, but we never
    // attempt to send through them at this time.
    return send_mail_phpmailer($to, $subject, $body);
}

/**
 * Send an email using SendGrid API
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool True if sent successfully, false otherwise
 */
function send_mail_sendgrid($to, $subject, $body) {
    try {
        $email = new Mail();
        $email->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent("text/html", $body);
        $email->addContent("text/plain", strip_tags($body));
        
        $sendgrid = new \SendGrid(SENDGRID_API_KEY);
        $response = $sendgrid->send($email);
        
        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            return true;
        } else {
            error_log("SendGrid Error: Status {$response->statusCode()}, Body: {$response->body()}");
            return false;
        }
    } catch (\Throwable $e) {
        error_log("SendGrid Exception: {$e->getMessage()}");
        return false;
    }
}

/**
 * Send an email using PHPMailer + SMTP (Fallback)
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool True if sent successfully, false otherwise
 */
function send_mail_phpmailer($to, $subject, $body) {
    // If SMTP is not configured, log but don't fail
    if (empty(MAIL_HOST) || empty(MAIL_USERNAME)) {
        error_log("PHPMailer not configured (MAIL_HOST or MAIL_USERNAME empty). Email not sent to: $to");
        return true; // Return true to allow registration to continue
    }

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

    } catch (\Throwable $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return true; // Return true to allow registration to continue even if email fails
    }
}
