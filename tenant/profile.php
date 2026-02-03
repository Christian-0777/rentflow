<?php
// tenant/profile.php
// Cover photo, name, status, edit button, stall info, lease duration

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$info = $pdo->prepare("
  SELECT u.*, s.stall_no, s.location, l.lease_start, l.lease_end
  FROM users u
  LEFT JOIN leases l ON l.tenant_id=u.id
  LEFT JOIN stalls s ON l.stall_id=s.id
  WHERE u.id=?
");
$info->execute([$tenantId]);
$user = $info->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/bootstrap-custom.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="dashboard.php" title="Dashboard"><i class="material-icons">dashboard</i><span></span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span></span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" class="active" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">
  <div class="profile-header">
    <div class="profile-cover" style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
    <div class="profile-info">
      <div style="width: 160px; height: 160px; border-radius: 50%; background: var(--light); border: 4px solid var(--white); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
        <i class="material-icons" style="font-size: 80px; color: var(--primary);">account_circle</i>
      </div>
      <div class="profile-info-text">
        <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
        <p>Status: <span class="badge"><?= htmlspecialchars(strtoupper($user['status'])) ?></span></p>
        <a class="btn btn-primary btn-small" href="account.php">
          <i class="material-icons" style="font-size: 18px;">edit</i> Edit Profile
        </a>
      </div>
    </div>
  </div>

  <div class="tenant-grid" style="margin-top: 24px;">
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">apartment</i>Rental Information</h3>
      <h4>Tenant ID</h4>
      <p><?= htmlspecialchars($user['tenant_id']) ?></p>
      
      <h4>Stall Number</h4>
      <p><?= htmlspecialchars($user['stall_no'] ?? '—') ?></p>
      
      <h4>Location</h4>
      <p><?= htmlspecialchars($user['location'] ?? 'Not assigned') ?></p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">business</i>Business Details</h3>
      <h4>Business Name</h4>
      <p><?= htmlspecialchars($user['business_name'] ?? '—') ?></p>
      
      <h4>Lease Period</h4>
      <p>
        <?= htmlspecialchars($user['lease_start']) ?> 
        <br>to 
        <br><?= htmlspecialchars($user['lease_end'] ?? 'Present') ?>
      </p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">info</i>Account Overview</h3>
      <h4>Contact Email</h4>
      <p><?= htmlspecialchars($user['email'] ?? '—') ?></p>
      
      <h4>Member Since</h4>
      <p><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))) ?></p>
      
      <a class="btn btn-secondary btn-small" href="account.php">
        <i class="material-icons" style="font-size: 18px;">settings</i> Account Settings
      </a>
      <a class="btn btn-danger btn-small" href="/rentflow/public/logout.php" style="margin-left: 8px;">
        <i class="material-icons" style="font-size: 18px;">logout</i> Logout
      </a>
    </div>
  </div>
</main>

<footer style="background-color: var(--white); border-top: 1px solid var(--border); padding: 30px 20px; margin-top: 40px;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px;">
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">About RentFlow</h4>
        <p style="font-size: 14px; color: var(--secondary); margin: 0; line-height: 1.6;">A modern stall rental management system for Baliwag Public Market with transparent pricing and easy payment tracking.</p>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Quick Links</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="dashboard.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Dashboard</a></li>
          <li style="margin-bottom: 8px;"><a href="stalls.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Stalls</a></li>
          <li style="margin-bottom: 8px;"><a href="payments.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Payments</a></li>
          <li><a href="support.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Support</a></li>
        </ul>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Account</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="profile.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Profile</a></li>
          <li style="margin-bottom: 8px;"><a href="account.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Settings</a></li>
          <li style="margin-bottom: 8px;"><a href="notifications.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Notifications</a></li>
          <li><a href="/rentflow/public/logout.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Logout</a></li>
        </ul>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Legal</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Terms of Service</a></li>
          <li style="margin-bottom: 8px;"><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Privacy Policy</a></li>
          <li><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Contact Us</a></li>
        </ul>
      </div>
    </div>
    <div style="border-top: 1px solid var(--border); padding-top: 20px; text-align: center; color: var(--secondary); font-size: 13px;">
      <p style="margin: 0;">&copy; <?= date('Y') ?> RentFlow. All rights reserved. | Baliwag Public Market Stall Management System</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/rentflow.js"></script>
</body>
</html>
 
