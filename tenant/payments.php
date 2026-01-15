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
  <h1>Payments</h1>

  <section class="grid">
    <div class="card">
      <h3>Upcoming payment</h3>
      <p><strong>Amount:</strong> ₱<?= number_format($upcoming['amount_due'] ?? 0,2) ?></p>
      <p><strong>Due:</strong> <?= htmlspecialchars($upcoming['due_date'] ?? '—') ?></p>
      <p><strong>Total Arrears:</strong> ₱<?= number_format($ar['total_arrears'] ?? 0,2) ?></p>
      <p><strong>Previous Arrears:</strong> ₱<?= number_format(($ar['total_arrears'] ?? 0) - ($ar['current_month_penalties'] ?? 0), 2) ?></p>
      <p><strong>Late penalty:</strong> Applied daily if overdue.</p>
    </div>

    <div class="card">
      <h3>Recent paid</h3>
      <p><strong>Total Arrears:</strong> ₱<?= number_format($ar['total_arrears'] ?? 0,2) ?></p>
      <p><strong>Previous Arrears:</strong> ₱<?= number_format(($ar['total_arrears'] ?? 0) - ($ar['current_month_penalties'] ?? 0), 2) ?></p>
      <p><strong>Last payment:</strong> ₱<?= number_format($last['amount'] ?? 0,2) ?> on <?= htmlspecialchars($last['payment_date'] ?? '—') ?></p>
      <p><strong>Penalty applied:</strong> Included in receipt if late.</p>
    </div>
  </section>

  <section class="table-section">
    <h3>Transaction history</h3>
    <table class="table">
      <thead>
        <tr><th>Date</th><th>Amount</th><th>Method</th><th>Transaction ID</th><th>Receipt</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['payment_date']) ?></td>
            <td>₱<?= number_format($r['amount'],2) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td><?= htmlspecialchars($r['transaction_id']) ?></td>
            <td>
              <?php if($r['receipt_path']): ?>
                <a class="btn small" href="<?= htmlspecialchars($r['receipt_path']) ?>" target="_blank">View</a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

  
</body>
</html>
