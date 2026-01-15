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
  <h1>Welcome back, <?= htmlspecialchars($firstName) ?> <?= htmlspecialchars($lastName) ?>!</h1>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert success" id="flashAlert">
      <?= htmlspecialchars($_SESSION['flash_success']) ?>
      <button class="dismiss-btn" onclick="dismissFlash()">×</button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <section class="cards">
    <div class="card">
      <h3>Next payment</h3>
      <p>₱<?= number_format($due['amount_due'] ?? 0, 2) ?> 
         on <?= htmlspecialchars($due['due_date'] ?? '—') ?></p>
    </div>
    <div class="card">
      <h3>Last payment</h3>
      <p>₱<?= number_format($lp['amount'] ?? 0, 2) ?> 
         on <?= htmlspecialchars($lp['payment_date'] ?? '—') ?></p>
      <small>Late penalty shown on receipt if applicable.</small>
    </div>
    <div class="card">
      <h3>Total arrears</h3>
      <p>₱<?= number_format($ar ?? 0, 2) ?></p>
    </div>
  </section>

  <section class="table-section">
    <h3>Arrears History</h3>
    <?php if (empty($allHistory)): ?>
      <p>No arrears history available.</p>
    <?php else: ?>
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
              <td>₱<?= number_format($item['amount'], 2) ?></td>
              <td><?= htmlspecialchars($item['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <?php if (!($_SESSION['user']['confirmed'] ?? 0)): ?>
    <div class="alert error">
      Your account is not yet confirmed. Please complete confirmation to access all features.
    </div>
  <?php endif; ?>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
function dismissFlash() {
  const alert = document.getElementById('flashAlert');
  if (alert) {
    alert.style.display = 'none';
  }
}
</script>

<script src="/rentflow/public/assets/js/ui.js"></script>
</body>
</html>
