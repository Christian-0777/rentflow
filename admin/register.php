<?php
// admin/register.php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = 'error';
$show_confirm_form = false;

// Validation function
function validate_password($pwd) {
    return strlen($pwd) >= 8;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
            $msg = 'Please fill in all required fields.';
        } elseif (!validate_email($email)) {
            $msg = 'Please enter a valid email address.';
        } elseif (!validate_password($password)) {
            $msg = 'Password must be at least 8 characters long.';
        } elseif ($password !== $password_confirm) {
            $msg = 'Passwords do not match.';
        } else {
            // Check for duplicate email
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $msg = 'This email is already registered.';
            } else {
                // Generate confirmation code (7 characters like tenant registration)
                $code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7);
                $hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert admin with confirmed=0 and code in cover_photo
                $insert = $pdo->prepare(
                    'INSERT INTO users (first_name, last_name, email, password_hash, role, status, confirmed, cover_photo, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                if ($insert->execute([$first_name, $last_name, $email, $hash, 'admin', 'active', 0, $code])) {
                    // Send confirmation email
                    $confirm_link = "http://" . $_SERVER['HTTP_HOST'] . "/rentflow/admin/confirm.php";
                    $email_body = "<h2>Welcome to RentFlow Admin!</h2>
                    <p>Hi $first_name,</p>
                    <p>Your admin account has been created. To activate it, please enter the following confirmation code:</p>
                    <h3>$code</h3>
                    <p>Enter this code on the confirmation page to activate your account.</p>
                    <p>This code expires in 24 hours.</p>
                    <p>Best regards,<br>RentFlow Team</p>";
                    
                    try {
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
                        $headers .= "From: noreply@rentflow.local" . "\r\n";
                        
                        send_mail($email, 'Confirm Your RentFlow Admin Account', $email_body);
                        
                        $msg = 'Registration successful! Please check your email to confirm your account.';
                        $msg_type = 'success';
                        $show_confirm_form = true;
                    } catch (Exception $e) {
                        $msg = 'Registration successful but email confirmation failed. Contact admin.';
                        $msg_type = 'warning';
                        $show_confirm_form = true;
                    }
                } else {
                    $msg = 'Registration failed. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['confirm'])) {
        $code = $_POST['code'];
        $email = trim($_POST['email'] ?? '');

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND cover_photo=? AND role='admin' AND confirmed=0");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();

        if ($user) {
            // Mark confirmed and clear code
            $pdo->prepare("UPDATE users SET confirmed=1, cover_photo=NULL WHERE id=?")->execute([$user['id']]);
            
            $msg = 'Email confirmed! You can now log in.';
            $msg_type = 'success';
            echo "<script>setTimeout(function() { window.location.href = 'login.php'; }, 2000);</script>";
        } else {
            $msg = 'Invalid code or email. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Register - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/signup.css">
  <style>
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .form-group input:focus { outline: none; border-color: #0066cc; }
    .password-hint { font-size: 12px; color: #666; margin-top: 4px; }
    .alert.warning { background-color: #fff3cd; border: 1px solid #ffc107; }
  </style>
</head>
<body class="admin">
<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>
<main class="content">
  <div class="card-container">
    <h1><?= $show_confirm_form ? 'Confirm Email' : 'Register Admin Account' ?></h1>
    <?php if ($msg): ?>
      <div class="alert <?= ($msg_type === 'success' ? 'success' : ($msg_type === 'warning' ? 'warning' : 'error')) ?>">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>
    
    <?php if (!$show_confirm_form): ?>
    <form method="post" novalidate>
      <div class="form-group">
        <label for="first_name">First Name *</label>
        <input id="first_name" name="first_name" type="text" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="last_name">Last Name *</label>
        <input id="last_name" name="last_name" type="text" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address *</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Password *</label>
        <input id="password" name="password" type="password" required>
        <div class="password-hint">Must be at least 8 characters long</div>
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirm Password *</label>
        <input id="password_confirm" name="password_confirm" type="password" required>
      </div>

      <div style="margin-top:20px;">
        <button class="btn" type="submit" name="register" value="1">Register</button>
        <a href="login.php" style="margin-left:12px;">Back to Login</a>
      </div>
    </form>
    <?php else: ?>
    <form method="post" novalidate>
      <p>Please enter the confirmation code sent to your email.</p>
      <div class="form-group">
        <label for="email_confirm">Email Address</label>
        <input id="email_confirm" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="code">Confirmation Code</label>
        <input id="code" name="code" type="text" placeholder="Enter 7-character code" required>
      </div>
      <div style="margin-top:20px;">
        <button class="btn" type="submit" name="confirm" value="1">Confirm</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</main>
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>
</body>
</html>