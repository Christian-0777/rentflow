<?php
// public/register.php
// Tenant registration with terms popup

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$show_code_form = false;
$show_otp_modal = false;
$otp_email = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['register'])) {
        $email  = $_POST['email'];
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $first  = trim($_POST['first_name']);
        $last   = trim($_POST['last_name']);

        // Validate passwords match
        if ($password !== $password_confirm) {
            $msg = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $msg = 'Password must be at least 6 characters long.';
        } else {
            $pwd    = password_hash($password, PASSWORD_BCRYPT);

            // Generate unique 4-character alphanumeric tenant_id
            do {
                $tenant_id = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
                $check = $pdo->prepare("SELECT id FROM users WHERE tenant_id = ?");
                $check->execute([$tenant_id]);
            } while ($check->fetch());

            // Generate 7-character alphanumeric code
            $code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7);

            // Insert tenant with confirmed=0 and code in cover_photo
            $stmt = $pdo->prepare("INSERT INTO users 
                (tenant_id, role,email,password_hash,first_name,last_name,status,confirmed,cover_photo) 
                VALUES (?, 'tenant',?,?,?,?,?,0,?)");
            $stmt->execute([$tenant_id, $email, $pwd, $first, $last, 'active', $code]);

            $tenantId = $pdo->lastInsertId();

            // Send confirmation email
            $subject = 'RentFlow Account Confirmation';
            $body = "
            <h2>Welcome to RentFlow!</h2>
            <p>Hi $first,</p>
            <p>Your Tenant ID is: <strong>$tenant_id</strong></p>
            <p>Your account has been created. To activate it, please enter the following confirmation code:</p>
            <h3>$code</h3>
            <p>Enter this code on the confirmation page.</p>
            <p>Best regards,<br>RentFlow Team</p>
            ";
            send_mail($email, $subject, $body);

            $msg = 'Registration successful. Please enter the confirmation code sent to your email.';
            $show_code_form = true;
        }
    } elseif (isset($_POST['confirm'])) {
        $code = $_POST['code'];
        $email = $_POST['email'];
        $enable2fa = isset($_POST['enable2fa']) ? 1 : 0;
        $trustDevice = isset($_POST['trustDevice']) ? 1 : 0;

        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email=? AND cover_photo=? AND role='tenant' AND confirmed=0");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();

        if ($user) {
            // Mark confirmed and save 2FA and remember device preferences
            $pdo->prepare("UPDATE users SET confirmed=1, cover_photo=NULL, two_factor_enabled=?, remember_device_enabled=? WHERE id=?")->execute([$enable2fa, $trustDevice, $user['id']]);

            // If 2FA enabled but trust device not checked, show OTP modal
            if ($enable2fa && !$trustDevice) {
                // Generate OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Save OTP to database
                $pdo->prepare("UPDATE users SET password_reset_otp=?, password_reset_expires=? WHERE id=?")->execute([$hashed_otp, $otp_expires, $user['id']]);

                // Send OTP email
                $subject = 'RentFlow - 2FA Verification Code';
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
                            <p>Hello " . htmlspecialchars($user['first_name']) . ",</p>
                            <p>Your account has been successfully created. To complete the setup and verify your device, please enter the following code:</p>
                            <div class='otp-box'>" . htmlspecialchars($otp) . "</div>
                            <p>This code will expire in 10 minutes.</p>
                            <p><strong>If you did not request this code, please ignore this email.</strong></p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " RentFlow. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                send_mail($email, $subject, $body);

                // Show OTP modal
                $_SESSION['pending_otp_user'] = [
                    'id' => $user['id'],
                    'email' => $email,
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ];
                $show_otp_modal = true;
                $otp_email = $email;
            } else {
                // Set session and redirect directly
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
                $stmt->execute([$user['id']]);
                $confirmed_user = $stmt->fetch();
                $_SESSION['user'] = $confirmed_user;

                header('Location: /rentflow/tenant/dashboard.php');
                exit;
            }
        } else {
            $msg = 'Invalid confirmation code.';
            $show_code_form = true;
        }
    } elseif (isset($_POST['verify_otp'])) {
        // API endpoint for OTP verification
        header('Content-Type: application/json');
        
        $otp = $_POST['otp'] ?? '';
        $trust_device = isset($_POST['trust_device']) ? 1 : 0;
        
        if (!isset($_SESSION['pending_otp_user'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
            exit;
        }
        
        $user_id = $_SESSION['pending_otp_user']['id'];
        $user_email = $_SESSION['pending_otp_user']['email'];
        
        // Get user and verify OTP
        $stmt = $pdo->prepare("SELECT password_reset_otp, password_reset_expires FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Check if OTP is expired
        if (strtotime($user['password_reset_expires']) < time()) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired']);
            exit;
        }
        
        // Verify OTP
        if (password_verify($otp, $user['password_reset_otp'])) {
            // Clear OTP
            $pdo->prepare("UPDATE users SET password_reset_otp=NULL, password_reset_expires=NULL WHERE id=?")->execute([$user_id]);
            
            // If trust device is checked, add to trusted devices
            if ($trust_device) {
                $deviceFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . date('Y-m-d'));
                $deviceToken = bin2hex(random_bytes(32));
                
                $pdo->prepare("INSERT INTO trusted_devices (user_id, device_fingerprint, device_token, user_agent, ip_address) 
                             VALUES (?, ?, ?, ?, ?)")->execute([
                    $user_id,
                    $deviceFingerprint,
                    $deviceToken,
                    $_SERVER['HTTP_USER_AGENT'],
                    $_SERVER['REMOTE_ADDR']
                ]);
            }
            
            // Set session
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $stmt->execute([$user_id]);
            $confirmed_user = $stmt->fetch();
            $_SESSION['user'] = $confirmed_user;
            unset($_SESSION['pending_otp_user']);
            
            echo json_encode(['success' => true, 'redirect' => '/rentflow/tenant/dashboard.php']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Registration - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/signup.css">
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Tenant Registration</h1>
    <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if(!$show_code_form): ?>
    <form id="registerForm" method="post">
      <input name="first_name" placeholder="First name" required>
      <input name="last_name" placeholder="Last name" required>
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>
      <input name="password_confirm" type="password" placeholder="Re-enter password" required>
      <button type="submit" name="register" class="btn">Register</button>
    </form>
    <p>Already registered? <a href="confirm.php">Confirm your account</a></p>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input name="code" placeholder="Enter 7-digit confirmation code" maxlength="7" required>
      <input type="hidden" id="enable2faHidden" name="enable2fa" value="0">
      <input type="hidden" id="trustDeviceHidden" name="trustDevice" value="0">
      <div style="margin: 20px 0; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9; border-radius: 6px;">
        <h3 style="margin-top: 0;">Terms and Agreement</h3>
        <p>By using RentFlow, you agree to timely payments, proper stall use, and compliance with market rules.</p>
        
        <h4>Terms of Service</h4>
        <p>
          Users of RentFlow agree to use the platform in compliance with all applicable laws and regulations. 
          You are responsible for maintaining the confidentiality of your account credentials and for all activities 
          that occur under your account. Violations of these terms may result in account suspension or termination.
        </p>
        
        <h4>Privacy Policy</h4>
        <p>
          <strong>Information We Collect:</strong> We collect personal information such as your name, email address, 
          tenant ID, contact details, and payment information to facilitate stall rental and management services.
        </p>
        <p>
          <strong>Data Protection:</strong> Your personal data is protected using industry-standard encryption and security measures. 
          We do not share your personal information with third parties without your explicit consent, except as required by law.
        </p>
        <p>
          <strong>Cookie Usage:</strong> RentFlow uses cookies to enhance user experience and maintain session security. 
          You can manage cookie preferences in your browser settings.
        </p>
        <p>
          <strong>Data Retention:</strong> We retain your information as long as necessary to provide services and maintain 
          legal records. You may request data deletion by contacting our support team.
        </p>
        
        <h4>Payment and Rental Policies</h4>
        <p>
          <strong>Timely Payments:</strong> All rent payments must be made by the due date specified in your rental agreement. 
          Late payments may incur penalties as per the platform's arrears policy.
        </p>
        <p>
          <strong>Stall Usage:</strong> Renters agree to use their assigned stall in accordance with market regulations and 
          maintain the premises in good condition. Unauthorized modifications are prohibited.
        </p>
        <p>
          <strong>Compliance:</strong> All users must comply with local market authority rules and RentFlow's code of conduct.
        </p>
        
        <h4>User Responsibilities</h4>
        <ul style="margin: 10px 0; padding-left: 20px;">
          <li>Provide accurate and complete registration information</li>
          <li>Maintain payment schedules and avoid arrears</li>
          <li>Respect market and platform rules</li>
          <li>Report any suspicious activities or unauthorized access immediately</li>
          <li>Use the platform solely for legitimate rental purposes</li>
        </ul>
        
        <h4>Limitation of Liability</h4>
        <p>
          RentFlow provides its services on an "as-is" basis. We are not liable for indirect, incidental, or consequential damages 
          arising from your use of the platform. Users are encouraged to resolve disputes through our support system.
        </p>
        
        <h4>Contact and Support</h4>
        <p>
          For privacy concerns, terms clarification, or general support, please contact our support team through the 
          RentFlow messaging system or email us at support@rentflow.local.
        </p>
        
        <label style="margin-top: 15px; display: flex; align-items: flex-start; gap: 10px;">
          <input type="checkbox" id="termsCheckbox" required style="margin-top: 5px;"> 
          <span>I have read and accept the Terms and Agreement, Privacy Policy, and all related policies</span>
        </label>
        
        <label style="margin-top: 15px; display: flex; align-items: flex-start; gap: 10px;">
          <input type="checkbox" id="enable2fa" style="margin-top: 5px;"> 
          <span>Enable Two-Factor Authentication (2FA) for enhanced security</span>
        </label>
        
        <label style="margin-top: 15px; display: flex; align-items: flex-start; gap: 10px;">
          <input type="checkbox" id="trustDevice" style="margin-top: 5px;"> 
          <span>Trust this device (skip 2FA on next login)</span>
        </label>
      </div>
      <button type="submit" name="confirm" class="btn" id="continueBtn" disabled style="background-color: gray; margin-top: 15px;">Continue</button>
    </form>
    <?php endif; ?>
  </div>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- OTP Verification Modal -->
<div id="otpModal" class="modal" <?php echo $show_otp_modal ? 'style="display: flex;"' : 'style="display: none;"'; ?>>
  <div class="modal-content" style="width: 90%; max-width: 400px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
    <h2 style="margin-top: 0; color: #0B3C5D;">Verify Your Device</h2>
    <p style="color: #666;">We've sent a verification code to your email. Enter it below to complete your registration.</p>
    
    <form id="otpForm" style="margin: 20px 0;">
      <input type="text" id="otpInput" placeholder="Enter 6-digit code" maxlength="6" required 
             style="width: 100%; padding: 12px; font-size: 18px; text-align: center; letter-spacing: 10px; border: 2px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
      
      <label style="display: flex; align-items: center; gap: 10px; margin: 15px 0; font-size: 14px;">
        <input type="checkbox" id="trustDeviceModal" checked>
        <span>Trust this device (skip 2FA on next login)</span>
      </label>
      
      <button type="submit" class="btn" style="width: 100%; background-color: #0B3C5D; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-size: 16px; margin-bottom: 10px;">
        Verify & Complete Registration
      </button>
    </form>
    
    <p id="otpMessage" style="margin: 10px 0; min-height: 20px; font-size: 13px;"></p>
    
    <p style="font-size: 12px; color: #999; margin-top: 15px;">
      Didn't receive the code? Check your spam folder or <a href="javascript:location.reload();" style="color: #0B3C5D; text-decoration: none;">try again</a>
    </p>
  </div>
</div>

<style>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateY(-50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>

<script>
<?php if ($show_otp_modal): ?>
document.getElementById('otpForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const otpInput = document.getElementById('otpInput');
  const trustDevice = document.getElementById('trustDeviceModal').checked;
  const message = document.getElementById('otpMessage');
  
  if (otpInput.value.length !== 6) {
    message.textContent = 'Please enter a 6-digit code';
    message.style.color = '#d9534f';
    return;
  }
  
  try {
    const response = await fetch(window.location.pathname, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'verify_otp=1&otp=' + otpInput.value + '&trust_device=' + (trustDevice ? '1' : '0')
    });
    
    const result = await response.json();
    
    if (result.success) {
      message.textContent = 'Success! Redirecting...';
      message.style.color = '#5cb85c';
      setTimeout(() => {
        window.location.href = result.redirect;
      }, 1500);
    } else {
      message.textContent = result.message || 'Invalid OTP';
      message.style.color = '#d9534f';
      otpInput.value = '';
      otpInput.focus();
    }
  } catch (error) {
    message.textContent = 'An error occurred. Please try again.';
    message.style.color = '#d9534f';
  }
});
<?php endif; ?>

document.getElementById('termsCheckbox').addEventListener('change', function() {
  const btn = document.getElementById('continueBtn');
  if (this.checked) {
    btn.disabled = false;
    btn.style.backgroundColor = '#0B3C5D';
  } else {
    btn.disabled = true;
    btn.style.backgroundColor = 'gray';
  }
});

document.getElementById('enable2fa').addEventListener('change', function() {
  document.getElementById('enable2faHidden').value = this.checked ? '1' : '0';
});

document.getElementById('trustDevice').addEventListener('change', function() {
  document.getElementById('trustDeviceHidden').value = this.checked ? '1' : '0';
});
</script>

</body>
</html>
