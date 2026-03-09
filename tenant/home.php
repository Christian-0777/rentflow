<?php
// tenant/home.php
// Combined dashboard and payments page

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$firstName = $_SESSION['user']['first_name'] ?? '';
$lastName  = $_SESSION['user']['last_name'] ?? '';

$lease = $pdo->prepare("SELECT id FROM leases WHERE tenant_id=?");
$lease->execute([$tenantId]);
$leaseId = $lease->fetchColumn();

// Upcoming payment
$due = $pdo->prepare("SELECT amount_due, due_date FROM dues WHERE lease_id=? AND paid=0 ORDER BY due_date ASC LIMIT 1");
$due->execute([$leaseId]);
$upcoming = $due->fetch(PDO::FETCH_ASSOC) ?: [];

// Total arrears
$arrears = $pdo->prepare("SELECT total_arrears FROM arrears WHERE lease_id=?");
$arrears->execute([$leaseId]);
$ar = $arrears->fetchColumn();

// Last payment
$last = $pdo->prepare("SELECT amount, payment_date, receipt_path FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 1");
$last->execute([$leaseId]);
$lp = $last->fetch(PDO::FETCH_ASSOC) ?: [];

// Transaction history
$history = $pdo->prepare("
  SELECT 
    p.payment_date as date,
    d.amount_due as total_amount,
    p.amount as amount_paid,
    CASE 
      WHEN p.amount >= d.amount_due THEN 'Paid'
      WHEN p.amount > 0 AND p.amount < d.amount_due THEN 'Partial'
      ELSE 'Not Paid'
    END as remarks,
    LPAD(SUBSTR(MD5(p.transaction_id), 1, 4), 4, '0') as transaction_id,
    p.receipt_path
  FROM payments p
  LEFT JOIN dues d ON p.due_id = d.id
  WHERE p.lease_id = ?
  ORDER BY p.payment_date DESC
");
$history->execute([$leaseId]);
$rows = $history->fetchAll();

// Arrears history
$historyQuery = $pdo->prepare("
    SELECT applied_on as date, penalty_amount as amount, 'Penalty Applied' as type, 'Applied' as status
    FROM penalties
    WHERE lease_id = ?
    ORDER BY applied_on DESC
");
$historyQuery->execute([$leaseId]);
$penalties = $historyQuery->fetchAll(PDO::FETCH_ASSOC);

$unpaidDues = $pdo->prepare("
    SELECT due_date as date, amount_due as amount, 'Unpaid Due' as type, 'Unpaid' as status
    FROM dues
    WHERE lease_id = ? AND paid = 0 AND due_date > (SELECT MIN(due_date) FROM dues WHERE lease_id = ? AND paid = 0)
    ORDER BY due_date DESC
");
$unpaidDues->execute([$leaseId, $leaseId]);
$dues = $unpaidDues->fetchAll(PDO::FETCH_ASSOC);

$allHistory = array_merge($penalties, $dues);
usort($allHistory, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Latest notification
$latestNotif = $pdo->prepare("
    SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name
    FROM notifications n
    JOIN users u ON n.sender_id=u.id
    WHERE n.receiver_id=?
    ORDER BY n.created_at DESC LIMIT 1
");
$latestNotif->execute([$tenantId]);
$notification = $latestNotif->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home - RentFlow</title>
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
      <li><a href="home.php" class="active" title="Home"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="messages.php" title="Messages"><i class="material-icons">message</i><span></span></a></li>
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

  <?php if ($notification): ?>
    <div class="alert alert-info" style="border-left: 4px solid var(--primary);">
      <div style="display: flex; gap: 12px; align-items: flex-start;">
        <i class="material-icons" style="color: var(--primary); margin-top: 2px;">notifications_active</i>
        <div style="flex: 1;">
          <?php if ($notification['title']): ?>
            <strong><?= htmlspecialchars($notification['title']) ?></strong><br>
          <?php endif; ?>
          <p style="margin: 4px 0 0 0; font-size: 14px;"><?= htmlspecialchars($notification['message']) ?></p>
          <small style="color: var(--secondary);">
            <?= htmlspecialchars($notification['sender_name'] ?? 'System') ?> • 
            <?= htmlspecialchars(date('M d, Y h:i A', strtotime($notification['created_at']))) ?>
          </small>
        </div>
      </div>
      <button class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>

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
      <h3>Upcoming Payment</h3>
      <div class="stat-value">₱<?= number_format($upcoming['amount_due'] ?? 0, 2) ?></div>
      <small>Due: <?= htmlspecialchars($upcoming['due_date'] ?? '—') ?></small>
    </div>
    
    <div class="tenant-card">
      <h3>Total Arrears</h3>
      <div class="stat-value" style="color: var(--danger);">₱<?= number_format($ar ?? 0, 2) ?></div>
      <small>Outstanding balance</small>
    </div>

    <div class="tenant-card">
      <h3>Last Payment</h3>
      <div class="stat-value" style="color: var(--success);">₱<?= number_format($lp['amount'] ?? 0, 2) ?></div>
      <small>Paid: <?= htmlspecialchars($lp['payment_date'] ?? '—') ?></small>
      <?php if (!empty($lp['receipt_path'])): ?>
        <button class="btn btn-sm btn-outline-primary mt-2" onclick="viewReceipt('<?= htmlspecialchars($lp['receipt_path']) ?>')">View Receipt</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="tenant-card">
        <h3>Transaction History</h3>
        <?php if (empty($rows)): ?>
          <p style="color: var(--secondary); margin: 0;">No payment records found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($rows, 0, 5) as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($r['date']))) ?></td>
                    <td>₱<?= number_format($r['amount_paid'], 2) ?></td>
                    <td>
                      <span class="badge <?= 
                        $r['remarks'] === 'Paid' ? 'bg-success' : 
                        ($r['remarks'] === 'Partial' ? 'bg-warning' : 'bg-danger')
                      ?>">
                        <?= htmlspecialchars($r['remarks']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-6">
      <div class="tenant-card">
        <h3>Arrears History</h3>
        <?php if (empty($allHistory)): ?>
          <p style="color: var(--secondary); margin: 0;">No arrears history available.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($allHistory, 0, 5) as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($item['date']))) ?></td>
                    <td><?= htmlspecialchars($item['type']) ?></td>
                    <td>₱<?= number_format($item['amount'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <iframe id="receiptFrame" src="" width="100%" height="500px" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/tenant.js"></script>
<script>
function viewReceipt(path) {
    document.getElementById('receiptFrame').src = path;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}
</script>
</body>
</html>