<?php
// public/login.php
// Tenant login with 2FA support and trusted devices

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

// Function to generate device fingerprint
function generateDeviceFingerprint($userAgent, $ipAddress) {
    return hash('sha256', $userAgent . $ipAddress . date('Y-m-d'));
}

// Function to generate secure device token
function generateDeviceToken() {
    return bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email']); // Can be email or tenant_id
    $pwd   = $_POST['password'];

    // Try to find user by email or tenant_id
    $stmt = $pdo->prepare("SELECT id, role, email, tenant_id, password_hash, first_name, last_name, business_name, status, confirmed, two_factor_enabled 
                           FROM users 
                           WHERE (email=? OR tenant_id=?) AND role='tenant' LIMIT 1");
    $stmt->execute([$login, $login]);
    $u = $stmt->fetch();

    if ($u) {
        if (password_verify($pwd, $u['password_hash'])) {
            if ($u['confirmed'] == 1) {
                // Check if user has 2FA enabled
                if ($u['two_factor_enabled']) {
                    // Check if device is trusted
                    $deviceFingerprint = generateDeviceFingerprint($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
                    
                    $trustStmt = $pdo->prepare("SELECT id, device_token FROM trusted_devices 
                                               WHERE user_id=? AND device_fingerprint=? AND is_active=1 LIMIT 1");
                    $trustStmt->execute([$u['id'], $deviceFingerprint]);
                    $trustedDevice = $trustStmt->fetch();

                    if ($trustedDevice) {
                        // Device is trusted, skip OTP and login directly
                        $_SESSION['user'] = [
                            'id'         => $u['id'],
                            'role'       => $u['role'],
                            'email'      => $u['email'],
                            'first_name' => $u['first_name'],
                            'last_name'  => $u['last_name'],
                            'business_name' => $u['business_name'],
                            'status'     => $u['status'],
                            'confirmed'  => $u['confirmed'],
                            'two_factor_enabled' => $u['two_factor_enabled']
                        ];

                        // Update last used timestamp
                        $pdo->prepare("UPDATE trusted_devices SET last_used_at=NOW() WHERE id=?")->execute([$trustedDevice['id']]);

                        header('Location: /rentflow/tenant/dashboard.php');
                        exit;
                    } else {
                        // Device not trusted, require OTP verification
                        $_SESSION['pending_login'] = [
                            'user_id'   => $u['id'],
                            'email'     => $u['email'],
                            'first_name' => $u['first_name'],
                            'last_name'  => $u['last_name'],
                            'business_name' => $u['business_name'],
                            'status'    => $u['status'],
                            'confirmed' => $u['confirmed']
                        ];
                        
                        // Generate and send OTP
                        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
                        $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                        $pdo->prepare("UPDATE users SET password_reset_otp=?, password_reset_expires=? WHERE id=?")->execute([$hashed_otp, $otp_expires, $u['id']]);

                        // Send OTP email
                        $body = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                                .header { background-color: #0B3C5D; color: white; padding: 10px; border-radius: 5px; text-align: center; }
                                .content { padding: 20px 0; }
                                .otp-box { background-color: #f8f9fa; border: 2px solid #0B3C5D; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; font-family: monospace; }
                                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>RentFlow - Two-Factor Authentication</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello " . htmlspecialchars($u['first_name']) . ",</p>
                                    <p>Someone is trying to access your RentFlow account. Use the OTP below to verify your login. This OTP will expire in 10 minutes.</p>
                                    <div class='otp-box'>" . htmlspecialchars($otp) . "</div>
                                    <p><strong>If you did not attempt this login, please change your password immediately.</strong></p>
                                </div>
                                <div class='footer'>
                                    <p>&copy; " . date('Y') . " RentFlow. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        send_mail($u['email'], 'RentFlow - 2FA Verification Code', $body);

                        $_SESSION['2fa_required'] = true;
                        $_SESSION['2fa_user_id'] = $u['id'];

                        header('Location: /rentflow/public/verify_2fa.php');
                        exit;
                    }
                } else {
                    // 2FA not enabled, login normally
                    $_SESSION['user'] = [
                        'id'         => $u['id'],
                        'role'       => $u['role'],
                        'email'      => $u['email'],
                        'first_name' => $u['first_name'],
                        'last_name'  => $u['last_name'],
                        'business_name' => $u['business_name'],
                        'status'     => $u['status'],
                        'confirmed'  => $u['confirmed'],
                        'two_factor_enabled' => false
                    ];
                    header('Location: /rentflow/tenant/dashboard.php');
                    exit;
                }
            } else {
                $msg = 'Please confirm your account before logging in.';
            }
        } else {
            $msg = 'Credentials Not Match';
        }
    } else {
        $msg = 'No Account Found, Register First';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/login.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">>
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Tenant Login</h1>
    <?php if($msg): ?><div class="alert error"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
      <input name="email" type="text" placeholder="Email or Tenant ID" required>
      <input name="password" type="password" placeholder="Password" required>
      <button type="submit" class="btn">Login</button>
    </form>
    <p style="margin: 15px 0; padding: 10px; background-color: #e8f4f8; border-radius: 4px; font-size: 13px; color: #0B3C5D;">
      <strong>ℹ Note:</strong> If your account has 2FA enabled and this is a new device, you'll need to verify with a one-time code sent to your email.
    </p>
    <p>
      <a href="forgot_password.php">Forgot Password?</a>
    </p>
    <p>
      Don't have an account? <a href="register.php"><strong>Sign Up</strong></a>
    </p>
  </div>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
