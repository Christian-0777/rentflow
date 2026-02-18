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
  <title>Account Settings - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/tenant-bootstrap.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="dashboard.php" title="Dashboard"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span></span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">
  <div class="page-header">
    <h1>Account Settings</h1>
    <p>Manage your profile, security, and preferences</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success">
      <i class="material-icons">check_circle</i>
      <div><?= htmlspecialchars($msg) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>

  <div class="tenant-grid">
    <!-- Profile -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">person</i>Profile Information</h3>
      <form method="post">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($u['first_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($u['last_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Business Name</label>
          <input type="text" name="business_name" value="<?= htmlspecialchars($u['business_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="location" value="<?= htmlspecialchars($u['location'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Profile</button>
      </form>
    </div>

    <!-- Email -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">email</i>Email Address</h3>
      <form method="post">
        <input type="hidden" name="update_email" value="1">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Email</button>
      </form>
    </div>

    <!-- Password -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">lock</i>Password</h3>
      <form method="post">
        <input type="hidden" name="update_password" value="1">
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="password" placeholder="Enter new password" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
      </form>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">security</i>Two-Factor Auth (2FA)</h3>
      <p style="font-size: 14px; margin-bottom: 12px;">Protect your account with an extra layer of security.</p>
      <form method="post">
        <input type="hidden" name="toggle_2fa" value="1">
        <div class="form-group" style="margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <input type="checkbox" id="enable_2fa" name="enable_2fa" <?= $u['two_factor_enabled'] ? 'checked' : '' ?>>
            <label for="enable_2fa" style="margin: 0; flex: 1;">Enable 2FA</label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <?= $u['two_factor_enabled'] ? 'Disable 2FA' : 'Enable 2FA' ?>
        </button>
      </form>
    </div>

    <!-- Trusted Devices -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">devices</i>Trusted Devices (<?= count($trustedDevices) ?>)</h3>
      <p style="font-size: 14px; margin-bottom: 12px;">Devices that have been trusted on this account.</p>
      <?php if (!empty($trustedDevices)): ?>
        <div>
          <?php foreach ($trustedDevices as $device): ?>
            <div class="device-item">
              <div class="device-name"><?= htmlspecialchars($device['device_name']) ?></div>
              <div class="device-meta">
                IP: <?= htmlspecialchars($device['ip_address']) ?><br>
                Created: <?= date('M d, Y H:i', strtotime($device['created_at'])) ?><br>
                Last used: <?= date('M d, Y H:i', strtotime($device['last_used_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color: var(--secondary); font-size: 14px;">No trusted devices yet.</p>
      <?php endif; ?>
    </div>

    <!-- Danger Zone -->
    <div class="tenant-card" style="border-left: 4px solid var(--danger);">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px; color: var(--danger);">warning</i>Danger Zone</h3>
      <p style="font-size: 14px; margin-bottom: 12px;">Logout from all devices to ensure account security.</p>
      <a class="btn btn-danger btn-small" href="/rentflow/public/logout.php">Logout All Devices</a>
    </div>

    <!-- Contact Support -->
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">contact_support</i>Contact Support</h3>
      <p style="font-size: 14px; margin-bottom: 12px;">Need help? Reach out to our support team for assistance with any issues or questions.</p>
      <a class="btn btn-secondary btn-small" href="support.php">
        <i class="material-icons" style="font-size: 18px; vertical-align: middle;">message</i> Contact Support
      </a>
    </div>

    <!-- Logout -->
    <div class="tenant-card" style="border-left: 4px solid #FF6B6B;">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px; color: #FF6B6B;">exit_to_app</i>Logout</h3>
      <p style="font-size: 14px; margin-bottom: 12px;">Sign out from your current session.</p>
      <a class="btn btn-secondary btn-small" href="/rentflow/public/logout.php">
        <i class="material-icons" style="font-size: 18px; vertical-align: middle;">exit_to_app</i> Logout
      </a>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 
