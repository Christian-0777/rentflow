<?php
// tenant/payments.php
// Upcoming payment, recent paid, transaction history with receipt view

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$leaseId = $pdo->prepare("SELECT id FROM leases WHERE tenant_id=?");
$leaseId->execute([$tenantId]);
$leaseId = $leaseId->fetchColumn();

$due = $pdo->prepare("SELECT amount_due, due_date FROM dues WHERE lease_id=? AND paid=0 ORDER BY due_date ASC LIMIT 1");
$due->execute([$leaseId]);
$upcoming = $due->fetch();

$recent = $pdo->prepare("SELECT amount, payment_date FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 1");
$recent->execute([$leaseId]);
$last = $recent->fetch();

$history = $pdo->prepare("SELECT payment_date, amount, method, transaction_id, receipt_path FROM payments WHERE lease_id=? ORDER BY payment_date DESC");
$history->execute([$leaseId]);
$rows = $history->fetchAll();

$arrears = $pdo->prepare("
  SELECT 
    a.total_arrears,
    COALESCE((
      SELECT SUM(penalty_amount) 
      FROM penalties p 
      WHERE p.lease_id = a.lease_id 
      AND MONTH(p.applied_on) = MONTH(CURDATE()) 
      AND YEAR(p.applied_on) = YEAR(CURDATE())
    ), 0) as current_month_penalties
  FROM arrears a 
  WHERE a.lease_id=?
");
$arrears->execute([$leaseId]);
$ar = $arrears->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payments - RentFlow</title>
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
      <li><a href="dashboard.php" title="Dashboard"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
      <li><a href="payments.php" class="active" title="Payments"><i class="material-icons">payment</i><span>Payments</span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span>Stalls</span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span>Notifications</span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span>Profile</span></a></li>
      <li><a href="account.php" title="Settings"><i class="material-icons">settings</i><span>Settings</span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">
  <div class="page-header">
    <h1>Payments</h1>
    <p>Manage and track your rental payments</p>
  </div>

  <div class="payment-info">
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">calendar_today</i>Upcoming Payment</h3>
      <div class="stat-value">₱<?= number_format($upcoming['amount_due'] ?? 0,2) ?></div>
      <h4>Due Date</h4>
      <p><?= htmlspecialchars($upcoming['due_date'] ?? '—') ?></p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">trending_down</i>Total Arrears</h3>
      <div class="stat-value" style="color: var(--danger);">₱<?= number_format($ar['total_arrears'] ?? 0,2) ?></div>
      <h4>Current Month Penalties</h4>
      <p>₱<?= number_format($ar['current_month_penalties'] ?? 0, 2) ?></p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">check_circle</i>Last Payment</h3>
      <div class="stat-value" style="color: var(--success);">₱<?= number_format($last['amount'] ?? 0,2) ?></div>
      <h4>Paid On</h4>
      <p><?= htmlspecialchars($last['payment_date'] ?? '—') ?></p>
    </div>
  </div>

  <div class="tenant-card" style="margin-bottom: 24px;">
    <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">history</i>Transaction History</h3>
    <?php if (empty($rows)): ?>
      <p style="color: var(--secondary); margin: 0;">No payment records found.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Transaction ID</th>
              <th>Receipt</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['payment_date']) ?></td>
                <td><strong>₱<?= number_format($r['amount'],2) ?></strong></td>
                <td><code style="font-size: 12px;"><?= htmlspecialchars(substr($r['transaction_id'], 0, 12)) ?>...</code></td>
                <td>
                  <?php if($r['receipt_path']): ?>
                    <a class="btn btn-primary btn-small" href="<?= htmlspecialchars($r['receipt_path']) ?>" target="_blank">
                      <i class="material-icons" style="font-size: 16px;">picture_as_pdf</i>
                    </a>
                  <?php else: ?>
                    <span style="color: var(--secondary);">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="tenant-card" style="border-left: 4px solid var(--warning);">
    <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px; color: var(--warning);">info</i>Payment Information</h3>
    <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
      <li>Late penalty is applied daily if payment is overdue</li>
      <li>Payment receipts include penalty charges if applicable</li>
      <li>All amounts are in Philippine Peso (₱)</li>
      <li>Contact support for payment difficulties</li>
    </ul>
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
