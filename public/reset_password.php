<?php
// public/reset_password.php
// OTP-based password reset confirmation page with modal

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = '';
$reset_success = false;
$user_email = '';

// Check if we're processing the combined form (OTP + password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    if ($_POST['step'] === 'reset_combined') {
        $email = trim($_POST['email']);
        $entered_otp = $_POST['otp'];
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords first
        if (strlen($new_password) < 6) {
            $msg = 'Password must be at least 6 characters long.';
            $msg_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $msg = 'Passwords do not match.';
            $msg_type = 'error';
        } else {
            // Get user with stored OTP
            $stmt = $pdo->prepare("SELECT id, email, password_reset_otp, password_reset_expires FROM users WHERE email=? AND role='tenant' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['password_reset_otp']) {
                // Check if OTP has expired
                if (strtotime($user['password_reset_expires']) > time()) {
                    // Verify the entered OTP against the hashed OTP
                    if (password_verify($entered_otp, $user['password_reset_otp'])) {
                        // OTP verified, now update password
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash=?, password_reset_otp=NULL, password_reset_expires=NULL, password_reset_requested_at=NULL WHERE email=?");
                        
                        if ($stmt->execute([$hashed_password, $email])) {
                            $msg = 'Password has been reset successfully! You can now login with your new password.';
                            $msg_type = 'success';
                            $reset_success = true;
                            unset($_SESSION['otp_request_email']);
                            unset($_SESSION['otp_verified']);
                            unset($_SESSION['reset_email']);
                        } else {
                            $msg = 'Error updating password. Please try again.';
                            $msg_type = 'error';
                        }
                    } else {
                        $msg = 'Invalid OTP. Please try again.';
                        $msg_type = 'error';
                    }
                } else {
                    $msg = 'OTP has expired. Please request a new one.';
                    $msg_type = 'error';
                }
            } else {
                $msg = 'No active OTP request found for this email.';
                $msg_type = 'error';
            }
        }
    }
}

// Check if we came from forgot_password page
$from_forgot_page = isset($_SESSION['otp_request_email']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/login.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      text-align: center;
    }

    .modal-header {
      margin-bottom: 20px;
    }

    .modal-header h2 {
      margin: 0 0 10px 0;
      color: #333;
    }

    .modal-header p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }

    .otp-input-container {
      margin: 20px 0;
    }

    .otp-input {
      font-size: 18px;
      letter-spacing: 5px;
      text-align: center;
      padding: 12px;
      border: 2px solid #007bff;
      border-radius: 4px;
      width: 100%;
      font-weight: bold;
      font-family: monospace;
      box-sizing: border-box;
    }

    .otp-input:focus {
      outline: none;
      border-color: #0056b3;
      box-shadow: 0 0 5px rgba(0, 86, 179, 0.5);
    }

    .modal-footer {
      margin-top: 20px;
    }

    .modal-footer button {
      background-color: #0B3C5D;
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
    }

    .modal-footer button:hover {
      background-color: #083051;
    }

    .modal-footer button:disabled {
      background-color: #ccc;
      cursor: not-allowed;
    }

    .alert-modal {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 4px;
    }

    .alert-modal.error {
      color: #721c24;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
    }

    .alert-modal.success {
      color: #155724;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
    }

    .resend-otp-link {
      margin-top: 15px;
      font-size: 14px;
    }

    .resend-otp-link a {
      color: #0B3C5D;
      text-decoration: none;
      cursor: pointer;
    }

    .resend-otp-link a:hover {
      text-decoration: underline;
    }

    .resend-otp-link a:disabled {
      color: #ccc;
      cursor: not-allowed;
      text-decoration: none;
    }

    .cooldown-timer {
      color: #666;
      font-size: 12px;
      margin-top: 5px;
    }

    .password-input-container {
      margin: 15px 0;
    }

    .password-input-container input {
      width: 100%;
      padding: 12px;
      border: 2px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
      box-sizing: border-box;
    }

    .password-input-container input:focus {
      outline: none;
      border-color: #0B3C5D;
      box-shadow: 0 0 5px rgba(11, 60, 93, 0.2);
    }
  </style>
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Reset Password</h1>

    <?php if($msg && !$from_forgot_page): ?>
      <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if(!$from_forgot_page && !$reset_success): ?>
      <!-- OTP Verification Form (Direct Access) -->
      <form method="post">
        <input type="hidden" name="step" value="reset_combined">
        <input name="email" type="email" placeholder="Enter your email" required>
        <input name="otp" type="text" placeholder="Enter 6-digit OTP" maxlength="6" pattern="\d{6}" required>
        <input name="password" type="password" placeholder="New Password" required minlength="6">
        <input name="confirm_password" type="password" placeholder="Confirm Password" required minlength="6">
        <button type="submit" class="btn">Reset Password</button>
      </form>
    <?php elseif($reset_success): ?>
      <!-- Success Message -->
      <div class="alert success"><?= htmlspecialchars($msg) ?></div>
      <p style="text-align: center; margin-top: 20px;">
        <a href="login.php" class="btn">Go to Login</a>
      </p>
    <?php endif; ?>

    <p style="margin-top: 20px; text-align: center;">
      <a href="login.php" style="color: #0B3C5D; text-decoration: none;">Back to Login</a>
    </p>
  </div>
</main>

<!-- Password Reset Modal (Combined OTP + Password) -->
<div id="resetModal" class="modal <?= ($from_forgot_page && !$reset_success) ? 'active' : '' ?>">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Reset Your Password</h2>
      <p>Enter the OTP sent to your email along with your new password</p>
    </div>

    <?php if($msg && $from_forgot_page && !$reset_success): ?>
      <div class="alert-modal <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post" id="resetForm">
      <input type="hidden" name="step" value="reset_combined">
      <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['otp_request_email'] ?? '') ?>">
      
      <div class="otp-input-container">
        <label style="display: block; text-align: left; margin-bottom: 8px; font-weight: 500; color: #333;">Enter OTP (6 digits)</label>
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

      <div class="password-input-container">
        <label style="display: block; text-align: left; margin-bottom: 8px; font-weight: 500; color: #333;">New Password (min 6 characters)</label>
        <input 
          type="password" 
          name="password" 
          placeholder="Enter new password" 
          required 
          minlength="6"
        >
      </div>

      <div class="password-input-container">
        <label style="display: block; text-align: left; margin-bottom: 8px; font-weight: 500; color: #333;">Confirm Password</label>
        <input 
          type="password" 
          name="confirm_password" 
          placeholder="Retype password" 
          required 
          minlength="6"
        >
      </div>

      <div class="modal-footer">
        <button type="submit">Reset Password</button>
      </div>

      <div class="resend-otp-link">
        <p>Didn't receive the OTP?</p>
        <form method="post" style="display: inline;">
          <input type="hidden" name="action" value="resend">
          <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['otp_request_email'] ?? '') ?>">
          <button type="submit" id="resendBtn" style="background: none; border: none; cursor: pointer; color: #0B3C5D; text-decoration: underline;">Request new OTP</button>
        </form>
      </div>
    </form>

    <p style="margin-top: 20px; text-align: center; font-size: 14px;">
      <a href="forgot_password.php" style="color: #0B3C5D; text-decoration: none;">Use a different email</a>
    </p>
  </div>
</div>



<script>
  // Only input numbers in OTP field
  document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
      otpInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
      });
    }
  });
</script>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
