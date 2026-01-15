<?php
// tenant/account.php
// Tenant account settings: profile, email, password, 2FA, notifications

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$msg = '';
$tenantId = $_SESSION['user']['id'];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update_profile'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $business = $_POST['business_name'];
    $location = $_POST['location'];
    $pdo->prepare("UPDATE users SET first_name=?, last_name=?, business_name=?, location=? WHERE id=?")
        ->execute([$first, $last, $business, $location, $tenantId]);
    $_SESSION['user']['first_name'] = $first;
    $_SESSION['user']['last_name'] = $last;
    $msg = 'Profile updated.';
  }

  if (isset($_POST['update_email'])) {
    $email = $_POST['email'];
    $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email, $tenantId]);
    $_SESSION['user']['email'] = $email;
    $msg = 'Email updated.';
  }

  if (isset($_POST['update_password'])) {
    $pwd = $_POST['password'];
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $tenantId]);
    $msg = 'Password updated.';
  }

  if (isset($_POST['toggle_2fa'])) {
    $enable = isset($_POST['enable_2fa']) ? 1 : 0;
    $pdo->prepare("UPDATE users SET two_factor_enabled=? WHERE id=?")->execute([$enable, $tenantId]);
    $msg = $enable ? '2FA enabled.' : '2FA disabled.';
  }
}

// Fetch tenant info
$stmt = $pdo->prepare("SELECT first_name, last_name, email, business_name, location, two_factor_enabled FROM users WHERE id=?");
$stmt->execute([$tenantId]);
$u = $stmt->fetch();

// Fetch trusted devices
$devices = $pdo->prepare("SELECT id, device_name, device_fingerprint, ip_address, user_agent, created_at, last_used_at FROM trusted_devices WHERE user_id=? ORDER BY last_used_at DESC");
$devices->execute([$tenantId]);
$trustedDevices = $devices->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Dashboard - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="tenant">

<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
      <li><a href="payments.php">Payments</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="profile.php" class="nav-profile" title="Account"><i class="material-icons">person</i></a></li>
      <li><a href="support.php" title="Contact Support"><i class="material-icons">contact_support</i></a></li>
      <li><a href="/rentflow/public/logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Account Settings</h1>
  <?php if ($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <section class="grid">
    <!-- Profile -->
    <div class="card">
      <h3>Profile</h3>
      <form method="post">
        <input type="hidden" name="update_profile" value="1">
        <input name="first_name" value="<?= htmlspecialchars($u['first_name']) ?>" placeholder="First name" required>
        <input name="last_name" value="<?= htmlspecialchars($u['last_name']) ?>" placeholder="Last name" required>
        <input name="business_name" value="<?= htmlspecialchars($u['business_name'] ?? '') ?>" placeholder="Business name">
        <input name="location" value="<?= htmlspecialchars($u['location'] ?? '') ?>" placeholder="Location">
        <button class="btn">Save Profile</button>
      </form>
    </div>

    <!-- Email -->
    <div class="card">
      <h3>Email</h3>
      <form method="post">
        <input type="hidden" name="update_email" value="1">
        <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
        <button class="btn">Update Email</button>
      </form>
    </div>

    <!-- Password -->
    <div class="card">
      <h3>Password</h3>
      <form method="post">
        <input type="hidden" name="update_password" value="1">
        <input type="password" name="password" placeholder="New password" required>
        <button class="btn">Change Password</button>
      </form>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="card">
      <h3>Two-Factor Authentication (2FA)</h3>
      <p>Protect your account with an extra layer of security.</p>
      <form method="post">
        <input type="hidden" name="toggle_2fa" value="1">
        <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
          <input type="checkbox" name="enable_2fa" <?= $u['two_factor_enabled'] ? 'checked' : '' ?>>
          <span><?= $u['two_factor_enabled'] ? 'Enabled' : 'Disabled' ?></span>
        </label>
        <button class="btn" style="margin-top: 15px;">
          <?= $u['two_factor_enabled'] ? 'Disable 2FA' : 'Enable 2FA' ?>
        </button>
      </form>
    </div>

    <!-- Trusted Devices -->
    <div class="card">
      <h3>Trusted Devices (<?= count($trustedDevices) ?>)</h3>
      <p>Devices that have been trusted on this account.</p>
      <?php if (!empty($trustedDevices)): ?>
        <div style="margin-top: 15px;">
          <?php foreach ($trustedDevices as $device): ?>
            <div style="padding: 12px; margin-bottom: 10px; background-color: #f5f5f5; border-radius: 6px; border-left: 4px solid #2196F3;">
              <div style="margin-bottom: 8px;">
                <strong><?= htmlspecialchars($device['device_name']) ?></strong>
                <small style="color: #666;"> • IP: <?= htmlspecialchars($device['ip_address']) ?></small>
              </div>
              <small style="color: #999;">
                Created: <?= date('M d, Y H:i', strtotime($device['created_at'])) ?><br>
                Last used: <?= date('M d, Y H:i', strtotime($device['last_used_at'])) ?>
              </small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color: #999; margin-top: 10px;">No trusted devices yet.</p>
      <?php endif; ?>
    </div>

    <!-- Logout -->
    <div class="card">
      <h3>Logout</h3>
      <a class="btn outline" href="/rentflow/public/logout.php">Log out</a>
    </div>
  </section>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
 
