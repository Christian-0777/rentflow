<?php
// public/verify_2fa.php
// 2FA OTP verification with trusted device option

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if 2FA is required
if (!isset($_SESSION['2fa_required']) || !$_SESSION['2fa_required']) {
    header('Location: /rentflow/public/login.php');
    exit;
}

$msg = '';
$msg_type = '';

// Function to generate device fingerprint
function generateDeviceFingerprint($userAgent, $ipAddress) {
    return hash('sha256', $userAgent . $ipAddress . date('Y-m-d'));
}

// Function to generate secure device token
function generateDeviceToken() {
    return bin2hex(random_bytes(32));
}

// Function to get device name from user agent
function getDeviceName($userAgent) {
    if (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Edge';
    } else {
        $browser = 'Unknown Browser';
    }

    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'Mac';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($userAgent, 'iPhone') !== false) {
        $os = 'iPhone';
    } else {
        $os = 'Unknown OS';
    }

    return "$browser on $os";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp']);
    $remember_device = isset($_POST['remember_device']) ? 1 : 0;
    $user_id = $_SESSION['2fa_user_id'];

    // Get user with OTP
    $stmt = $pdo->prepare("SELECT email, password_reset_otp, password_reset_expires FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && $user['password_reset_otp']) {
        // Check if OTP has expired
        if (strtotime($user['password_reset_expires']) > time()) {
            // Verify OTP
            if (password_verify($entered_otp, $user['password_reset_otp'])) {
                // OTP verified successfully
                $msg = '2FA verification successful!';
                $msg_type = 'success';

                // If user wants to remember device, add it to trusted devices
                if ($remember_device && $_SESSION['pending_login']) {
                    $deviceFingerprint = generateDeviceFingerprint($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
                    $deviceToken = generateDeviceToken();
                    $deviceName = getDeviceName($_SERVER['HTTP_USER_AGENT']);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO trusted_devices 
                        (user_id, device_fingerprint, device_name, device_token, user_agent, ip_address) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $user_id,
                        $deviceFingerprint,
                        $deviceName,
                        $deviceToken,
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REMOTE_ADDR']
                    ]);
                }

                // Clear OTP from database
                $pdo->prepare("UPDATE users SET password_reset_otp=NULL, password_reset_expires=NULL WHERE id=?")->execute([$user_id]);

                // Set user session
                $userStmt = $pdo->prepare("SELECT id, role, email, first_name, last_name, business_name, status, confirmed 
                                          FROM users WHERE id=? LIMIT 1");
                $userStmt->execute([$user_id]);
                $loggedInUser = $userStmt->fetch();

                $_SESSION['user'] = [
                    'id'         => $loggedInUser['id'],
                    'role'       => $loggedInUser['role'],
                    'email'      => $loggedInUser['email'],
                    'first_name' => $loggedInUser['first_name'],
                    'last_name'  => $loggedInUser['last_name'],
                    'business_name' => $loggedInUser['business_name'],
                    'status'     => $loggedInUser['status'],
                    'confirmed'  => $loggedInUser['confirmed']
                ];

                // Clear 2FA session
                unset($_SESSION['2fa_required']);
                unset($_SESSION['2fa_user_id']);
                unset($_SESSION['pending_login']);

                header('Location: /rentflow/tenant/dashboard.php');
                exit;
            } else {
                $msg = 'Invalid OTP. Please try again.';
                $msg_type = 'error';
            }
        } else {
            $msg = 'OTP has expired. Please login again.';
            $msg_type = 'error';
        }
    } else {
        $msg = 'No OTP found. Please login again.';
        $msg_type = 'error';
    }
}

$user_first_name = $_SESSION['pending_login']['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>2FA Verification - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/verify_2fa.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content" style="display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 200px); padding-top: 80px;">
  <!-- 2FA Verification Modal -->
  <div class="modal" style="position: relative; background: transparent; left: auto; top: auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Verify Your Identity</h2>
        <p>Enter the 6-digit code sent to your email</p>
      </div>

      <?php if($msg): ?>
        <div class="alert-modal <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="otp-input-container">
          <input 
            type="text" 
            name="otp" 
            class="otp-input" 
            placeholder="000000" 
            maxlength="6" 
            pattern="\d{6}" 
            inputmode="numeric" 
            required 
            autofocus
          >
        </div>

        <div class="remember-device-container">
          <label>
            <input type="checkbox" name="remember_device" value="1">
            <span>Remember this device for 30 days</span>
          </label>
          <p>You won't need to enter the verification code on this device for future logins.</p>
        </div>

        <div class="modal-footer">
          <button type="submit">Verify</button>
        </div>
      </form>

      <div class="back-link">
        <p><a href="login.php">Back to Login</a></p>
      </div>
    </div>
  </div>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script src="/rentflow/public/assets/js/verify_2fa.js"></script>

</body>
</html>
