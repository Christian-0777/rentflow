<?php
// tenant/dashboard.php
// Next payment, last payment (with penalty), total arrears

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$firstName = $_SESSION['user']['first_name'] ?? '';
$lastName  = $_SESSION['user']['last_name'] ?? '';

$lease = $pdo->prepare("SELECT id FROM leases WHERE tenant_id=?");
$lease->execute([$tenantId]);
$leaseId = $lease->fetchColumn();

$next = $pdo->prepare("SELECT amount_due, due_date 
                       FROM dues 
                       WHERE lease_id=? AND paid=0 
                       ORDER BY due_date ASC LIMIT 1");
$next->execute([$leaseId]);
$due = $next->fetch();

$last = $pdo->prepare("SELECT amount, payment_date 
                       FROM payments 
                       WHERE lease_id=? 
                       ORDER BY payment_date DESC LIMIT 1");
$last->execute([$leaseId]);
$lp = $last->fetch();

$arrears = $pdo->prepare("SELECT total_arrears FROM arrears WHERE lease_id=?");
$arrears->execute([$leaseId]);
$ar = $arrears->fetchColumn();

// Fetch arrears history: penalties and unpaid dues
$history = $pdo->prepare("
    SELECT applied_on as date, penalty_amount as amount, 'Penalty Applied' as type, 'Applied' as status
    FROM penalties
    WHERE lease_id = ?
    ORDER BY applied_on DESC
");
$history->execute([$leaseId]);
$penalties = $history->fetchAll(PDO::FETCH_ASSOC);

$unpaidDues = $pdo->prepare("
    SELECT due_date as date, amount_due as amount, 'Unpaid Due' as type, 'Unpaid' as status
    FROM dues
    WHERE lease_id = ? AND paid = 0 AND due_date > (SELECT MIN(due_date) FROM dues WHERE lease_id = ? AND paid = 0)
    ORDER BY due_date DESC
");
$unpaidDues->execute([$leaseId, $leaseId]);
$dues = $unpaidDues->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort by date descending
$allHistory = array_merge($penalties, $dues);
usort($allHistory, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Dashboard - RentFlow</title>
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
      <li><a href="dashboard.php" class="active" title="Dashboard"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span></span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">

  <div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars($firstName) ?> <?= htmlspecialchars($lastName) ?>!</h1>
    <p>Here's an overview of your account</p>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
      <i class="material-icons">check_circle</i>
      <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <div class="tenant-grid">
    <div class="tenant-card">
      <h3>Next Payment</h3>
      <div class="stat-value">₱<?= number_format($due['amount_due'] ?? 0, 2) ?></div>
      <small>Due: <?= htmlspecialchars($due['due_date'] ?? '—') ?></small>
    </div>
    
    <div class="tenant-card">
      <h3>Last Payment</h3>
      <div class="stat-value">₱<?= number_format($lp['amount'] ?? 0, 2) ?></div>
      <small>Paid: <?= htmlspecialchars($lp['payment_date'] ?? '—') ?></small>
      <p style="margin-top: 8px; font-size: 13px;">Late penalty shown on receipt if applicable.</p>
    </div>

    <div class="tenant-card">
      <h3>Total Arrears</h3>
      <div class="stat-value" style="color: var(--danger);">₱<?= number_format($ar ?? 0, 2) ?></div>
      <small>Outstanding balance</small>
    </div>
  </div>

  <div class="tenant-card" style="margin-bottom: 24px;">
    <h3>Arrears History</h3>
    <?php if (empty($allHistory)): ?>
      <p style="color: var(--secondary); margin: 0;">No arrears history available.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allHistory as $item): ?>
              <tr>
                <td><?= htmlspecialchars(date('M d, Y', strtotime($item['date']))) ?></td>
                <td><?= htmlspecialchars($item['type']) ?></td>
                <td><strong>₱<?= number_format($item['amount'], 2) ?></strong></td>
                <td><span class="badge <?= $item['status'] === 'Paid' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars($item['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!($_SESSION['user']['confirmed'] ?? 0)): ?>
    <div class="alert alert-warning">
      <i class="material-icons">info</i>
      <div><strong>Account Pending Confirmation</strong><br>Your account is not yet confirmed. Please complete verification to access all features.</div>
    </div>
  <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
