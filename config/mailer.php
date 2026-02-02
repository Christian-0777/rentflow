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

// SendGrid Configuration
define('SENDGRID_API_KEY', env('SENDGRID_API_KEY', ''));

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
    // Try SendGrid first if API key is configured
    if (!empty(SENDGRID_API_KEY)) {
        if (send_mail_sendgrid($to, $subject, $body)) {
            return true;
        }
        error_log("SendGrid email failed, falling back to PHPMailer");
    }
    
    // Fallback to PHPMailer
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
    } catch (Exception $e) {
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
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
