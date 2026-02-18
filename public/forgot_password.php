<?php
// public/forgot_password.php
// OTP-based password reset request page

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = '';
$otp_sent = false;
$otp_request_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'request';
    $email = trim($_POST['email'] ?? '');

    if ($action === 'request' || $action === 'resend') {
        // Check if email exists in the system
        $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, password_reset_requested_at FROM users WHERE email=? AND role='tenant' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check cooldown for resend
            if ($action === 'resend' && $user['password_reset_requested_at']) {
                $last_request_time = strtotime($user['password_reset_requested_at']);
                $current_time = time();
                $time_elapsed = $current_time - $last_request_time;
                $cooldown_period = 10 * 60; // 10 minutes in seconds

                if ($time_elapsed < $cooldown_period) {
                    $remaining_time = $cooldown_period - $time_elapsed;
                    $remaining_minutes = ceil($remaining_time / 60);
                    $msg = "Please wait $remaining_minutes minute(s) before requesting a new OTP.";
                    $msg_type = 'error';
                } else {
                    // Generate and send OTP
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $now = date('Y-m-d H:i:s');

                    // Store the hashed OTP and request time in the database
                    $stmt = $pdo->prepare("UPDATE users SET password_reset_otp=?, password_reset_expires=?, password_reset_requested_at=? WHERE id=?");
                    $stmt->execute([$hashed_otp, $otp_expires, $now, $user['id']]);

                    // Send OTP email
                    $body = send_otp_email($user['first_name'], $otp);
                    if (send_mail($user['email'], 'Password Reset OTP - RentFlow', $body)) {
                        $msg = 'OTP has been resent to your email. Valid for 24 hours.';
                        $msg_type = 'success';
                        $otp_sent = true;
                        $otp_request_email = $email;
                        $_SESSION['otp_request_email'] = $email;
                    } else {
                        $msg = 'Error sending email. Please try again later.';
                        $msg_type = 'error';
                    }
                }
            } else {
                // Generate and send OTP for initial request
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $now = date('Y-m-d H:i:s');

                // Store the hashed OTP and request time in the database
                $stmt = $pdo->prepare("UPDATE users SET password_reset_otp=?, password_reset_expires=?, password_reset_requested_at=? WHERE id=?");
                $stmt->execute([$hashed_otp, $otp_expires, $now, $user['id']]);

                // Send OTP email
                $body = send_otp_email($user['first_name'], $otp);
                if (send_mail($user['email'], 'Password Reset OTP - RentFlow', $body)) {
                    $msg = 'OTP has been sent to your email. Valid for 24 hours.';
                    $msg_type = 'success';
                    $otp_sent = true;
                    $otp_request_email = $email;
                    $_SESSION['otp_request_email'] = $email;
                } else {
                    $msg = 'Error sending email. Please try again later.';
                    $msg_type = 'error';
                }
            }
        } else {
            $msg = 'Email not found in our system.';
            $msg_type = 'error';
        }
    }
}

// Helper function to generate OTP email
function send_otp_email($first_name, $otp) {
    return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #007bff; color: white; padding: 10px; border-radius: 5px; text-align: center; }
                .content { padding: 20px 0; }
                .otp-box { background-color: #f8f9fa; border: 2px solid #007bff; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; font-family: monospace; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RentFlow - Password Reset OTP</h2>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($first_name) . ",</p>
                    <p>You requested a password reset. Use the OTP below to reset your password. This OTP will expire in 24 hours.</p>
                    <div class='otp-box'>" . htmlspecialchars($otp) . "</div>
                    <p><strong>Do not share this OTP with anyone.</strong></p>
                    <p>If you did not request this reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " RentFlow. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/login.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
  </style>
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <h1>Forgot Password</h1>
  <p>Enter your email address and we'll send you an OTP to reset your password.</p>
  
  <?php if($msg): ?>
    <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if(!$otp_sent): ?>
    <form method="post">
      <input type="hidden" name="action" value="request">
      <input name="email" type="email" placeholder="Enter your email" required>
      <button type="submit" class="btn">Send OTP</button>
    </form>
  <?php else: ?>
    <p style="text-align: center; margin: 20px 0;">
      <strong>OTP sent to:</strong> <?= htmlspecialchars($otp_request_email) ?>
    </p>
    <form method="post" style="text-align: center;">
      <input type="hidden" name="action" value="resend">
      <input type="hidden" name="email" value="<?= htmlspecialchars($otp_request_email) ?>">
      <button type="submit" class="btn" id="resendBtn">Resend OTP</button>
      <p id="cooldownMsg" style="display: none; color: #666; margin-top: 10px;"></p>
    </form>
    <p style="text-align: center; margin-top: 20px;">
      <a href="reset_password.php" class="btn" style="background-color: #28a745; border: none;">Continue to Reset</a>
    </p>
  <?php endif; ?>

  <p style="margin-top: 20px; text-align: center;">
    <a href="login.php" style="color: #007bff; text-decoration: none;">Back to Login</a>
  </p>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
