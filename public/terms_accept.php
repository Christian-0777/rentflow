<?php
// public/terms_accept.php
// Display Terms, Privacy & Policies and mark tenant as having accepted them

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    // No user in session, redirect to login
    header('Location: /rentflow/public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_terms'])) {
    $userId = $_SESSION['user']['id'];
    $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
    $remember_device = isset($_POST['remember_device']) ? 1 : 0;

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

    // Mark tenant as active and set 2FA + remember device preferences
    $pdo->prepare("UPDATE users SET status='active', two_factor_enabled=?, remember_device_enabled=? WHERE id=?")->execute([$enable_2fa, $remember_device, $userId]);

    // If user enabled 2FA and wants to remember this device during registration, register it as trusted
    if ($enable_2fa && $remember_device) {
        try {
            $deviceFingerprint = generateDeviceFingerprint($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
            $deviceToken = generateDeviceToken();
            $deviceName = getDeviceName($_SERVER['HTTP_USER_AGENT']);

            $insertStmt = $pdo->prepare("
                INSERT INTO trusted_devices 
                (user_id, device_fingerprint, device_name, device_token, user_agent, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $userId,
                $deviceFingerprint,
                $deviceName,
                $deviceToken,
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e) {
            // Log error but don't stop registration if device registration fails
            error_log("Device registration failed: " . $e->getMessage());
        }
    }

    // Refresh session data with updated status
    $stmt = $pdo->prepare("SELECT id, role, email, first_name, last_name, business_name, status, confirmed, two_factor_enabled, remember_device_enabled 
                           FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch();

    if ($updatedUser) {
        $_SESSION['user'] = $updatedUser;
    }

    // Redirect to tenant dashboard
    header('Location: /rentflow/tenant/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Terms & Policies - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/signup.css">
  <style>
    .policies-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    .policies-content {
      max-height: 500px;
      overflow-y: auto;
      border: 1px solid #ccc;
      padding: 20px;
      background: #f9f9f9;
      margin-bottom: 20px;
    }
    .policies-content h4 {
      margin-top: 20px;
      color: #0B3C5D;
    }
    .policies-content ul {
      margin-left: 20px;
    }
  </style>
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="policies-container">
    <h1>Terms & Policies</h1>
    <p>Please read and understand the following terms, privacy policy, and associated policies before accepting.</p>
    
    <div class="policies-content">
      <h3>Terms and Agreement</h3>
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
      <ul>
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
    </div>
    
    <form method="post" style="text-align: center;">
      <div style="margin-bottom: 20px; text-align: left; background-color: #f9f9f9; padding: 15px; border-radius: 6px;">
        <label style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px; cursor: pointer;">
          <input type="checkbox" id="acceptCheckbox" required style="margin-top: 5px;"> 
          <span>I have read and understand all Terms and Policies, and I agree to be bound by them</span>
        </label>

        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
          <h4 style="margin-top: 0; color: #0B3C5D; font-size: 14px;">Additional Security Options</h4>
          
          <div style="margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
              <input type="checkbox" name="enable_2fa" id="enable2fa" value="1" style="width: 18px; height: 18px; cursor: pointer;">
              <span>Enable Two-Factor Authentication (2FA)</span>
            </label>
            <p style="margin: 8px 0 0 28px; font-size: 13px; color: #666;">
              Requires a verification code in addition to your password for enhanced security.
            </p>
          </div>

          <div style="margin-left: 30px; padding: 12px; background-color: #e8f4f8; border-left: 3px solid #0B3C5D; border-radius: 4px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
              <input type="checkbox" name="remember_device" id="rememberDevice" value="1" style="width: 18px; height: 18px; cursor: pointer;" disabled>
              <span>Trust this device - Remember me on this device</span>
            </label>
            <p style="margin: 8px 0 0 28px; font-size: 13px; color: #666;">
              If 2FA is enabled, you won't need to enter a verification code on this device for future logins (valid for 30 days).
            </p>
          </div>
        </div>
      </div>

      <button type="submit" name="accept_terms" class="btn" id="acceptBtn" disabled style="background-color: gray;">Accept and Continue</button>
      <p><a href="/rentflow/tenant/dashboard.php" style="margin-top: 15px; display: inline-block;">Skip for now</a></p>
    </form>
  </div>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
document.getElementById('acceptCheckbox').addEventListener('change', function() {
  const btn = document.getElementById('acceptBtn');
  if (this.checked) {
    btn.disabled = false;
    btn.style.backgroundColor = '#0B3C5D';
  } else {
    btn.disabled = true;
    btn.style.backgroundColor = 'gray';
  }
});

// Enable/Disable Remember Device checkbox based on 2FA checkbox
const enable2faCheckbox = document.getElementById('enable2fa');
const rememberDeviceCheckbox = document.getElementById('rememberDevice');

enable2faCheckbox.addEventListener('change', function() {
  if (this.checked) {
    rememberDeviceCheckbox.disabled = false;
  } else {
    rememberDeviceCheckbox.disabled = true;
    rememberDeviceCheckbox.checked = false;
  }
});
</script>

</body>
</html>
