<?php
// public/confirm.php
// Handles tenant code confirmation and prompts Terms & Agreements

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = trim($_POST['code']);
    $email = trim($_POST['email']);

    // Check tenant with matching email + code
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, business_name 
                           FROM users 
                           WHERE email=? AND cover_photo=? AND role='tenant' AND confirmed=0");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        // Mark confirmed
        $pdo->prepare("UPDATE users SET confirmed=1, cover_photo=NULL WHERE id=?")
            ->execute([$user['id']]);

        // 🔔 Notify Admin
        $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role='admin' LIMIT 1");
        $adminStmt->execute();
        $adminId = $adminStmt->fetchColumn();

        if ($adminId) {
            $pdo->prepare("
              INSERT INTO notifications (sender_id, receiver_id, type, title, message)
              VALUES (?, ?, 'system', 'New Tenant Confirmed',
                      CONCAT('Tenant ', ?, ' ', ?, ' (', ?, ') has confirmed their account.'))
            ")->execute([$user['id'], $adminId, $user['first_name'], $user['last_name'], $user['business_name']]);
        }

        // Set session with confirmed user
        $stmt = $pdo->prepare("SELECT id, role, email, first_name, last_name, business_name, status, confirmed 
                               FROM users WHERE id=?");
        $stmt->execute([$user['id']]);
        $confirmed_user = $stmt->fetch();

        $_SESSION['user'] = $confirmed_user;

        $msg = "Account confirmed. Please accept the Terms and Agreements below.";
    } else {
        $msg = "Invalid confirmation code or email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Confirmation - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">>
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <h1>Account Confirmation</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if(!$user): ?>
    <form method="post">
      <input name="email" type="email" placeholder="Email" required>
      <input name="code" placeholder="7-character Confirmation Code" maxlength="7" required>
      <button type="submit" class="btn">Confirm</button>
    </form>
  <?php else: ?>
    <form method="post" action="terms_accept.php">
      <input type="hidden" name="accept_terms" value="1">
      <p><strong>Terms and Agreements:</strong></p>
      <p>By using RentFlow, you agree to timely payments, proper stall use, and compliance with market rules.</p>
      
      <div style="margin: 20px 0; padding: 15px; background-color: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 6px;">
        <h4 style="margin-top: 0; color: #0B3C5D;">Security Settings</h4>
        
        <div style="margin-bottom: 0;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
            <input type="checkbox" name="enable_2fa" id="enable2fa" value="1" style="width: 18px; height: 18px; cursor: pointer;">
            <span>Enable Two-Factor Authentication (2FA)</span>
          </label>
          <p style="margin: 8px 0 0 28px; font-size: 13px; color: #666;">
            Requires a verification code in addition to your password for enhanced security.
          </p>
        </div>
      </div>
      
      <button class="btn">Accept</button>
    </form>
  <?php endif; ?>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
