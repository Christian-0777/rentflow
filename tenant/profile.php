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
  <div class="profile-cover" style="background-image:url('<?= htmlspecialchars($user['cover_photo'] ?? '/public/assets/img/placeholders/cover.jpg') ?>')">
    <div class="profile-info">
      <img class="avatar" src="<?= htmlspecialchars($user['profile_photo'] ?? '/public/assets/img/placeholders/avatar.png') ?>">
      <div>
        <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
        <p>Status: <span class="badge"><?= htmlspecialchars(strtoupper($user['status'])) ?></span></p>
        <a class="btn small" href="account.php">Edit profile</a>
        <a class="btn small outline" href="account.php">Settings</a> <!-- ✅ Settings button -->
      </div>
    </div>
  </div>

  <section class="grid">
    <div class="card">
      <h3>Stall</h3>
      <p><strong>Tenant ID:</strong> <?= htmlspecialchars($user['tenant_id']) ?></p>
      <p><strong>No.:</strong> <?= htmlspecialchars($user['stall_no'] ?? '—') ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($user['location'] ?? '') ?></p>
      <p><strong>Business:</strong> <?= htmlspecialchars($user['business_name'] ?? '—') ?></p>
      <p><strong>Lease:</strong> <?= htmlspecialchars($user['lease_start']) ?> to <?= htmlspecialchars($user['lease_end'] ?? 'present') ?></p>
    </div>
  </section>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</body>
</html>
 
