<?php
// treasury/adjusment.php
// Treasury manual adjustment of dues and balances (typo kept as requested)

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('treasury');

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $leaseId = (int)$_POST['lease_id'];
  $newArrears = (float)$_POST['total_arrears'];
  $newDueDate = $_POST['due_date'] ?? null;
  $newDueAmount = (float)($_POST['amount_due'] ?? 0);

  // Update arrears
  $pdo->prepare("UPDATE arrears SET total_arrears=?, last_updated=NOW() WHERE lease_id=?")
      ->execute([$newArrears, $leaseId]);

  // Update next due (if provided)
  if ($newDueDate && $newDueAmount > 0) {
    // Upsert next unpaid due
    $due = $pdo->prepare("SELECT id FROM dues WHERE lease_id=? AND paid=0 ORDER BY due_date ASC LIMIT 1");
    $due->execute([$leaseId]);
    $d = $due->fetchColumn();
    if ($d) {
      $pdo->prepare("UPDATE dues SET due_date=?, amount_due=? WHERE id=?")
          ->execute([$newDueDate, $newDueAmount, $d]);
    } else {
      $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?,?,?,0)")
          ->execute([$leaseId, $newDueDate, $newDueAmount]);
    }
  }

  $msg = 'Adjustments saved.';

  // Notify admin of the adjustment
  $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
  if ($adminId) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Treasury Adjustment', 'Treasury updated lease #{$leaseId} arrears to ₱{$newArrears}.')")
        ->execute([$_SESSION['user']['id'], $adminId]);
  }

  // Notify tenant of the adjustment
  $stmt = $pdo->prepare("SELECT tenant_id FROM leases WHERE id=?");
  $stmt->execute([$leaseId]);
  $tenantId = $stmt->fetchColumn();
  if ($tenantId) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Arrears Updated', 'Your arrears have been updated to ₱{$newArrears}. Please check your account.')")
        ->execute([$_SESSION['user']['id'], $tenantId]);
  }
}


$rows = $pdo->query("
  SELECT l.id AS lease_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, s.stall_no, a.total_arrears,
         (SELECT due_date FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_due,
         (SELECT amount_due FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_amount
  FROM leases l
  JOIN users u ON l.tenant_id=u.id
  JOIN stalls s ON l.stall_id=s.id
  LEFT JOIN arrears a ON a.lease_id=l.id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Adjustments - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSS links -->
  <link href="/rentflow/public/assets/css/base.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/layout.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/components.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/treasury.css" rel="stylesheet">
</head>
<body class="treasury">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>
  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
      <li><a href="adjustments.php">Adjustments</a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Treasury Adjustments</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th>Lease</th><th>Tenant</th><th>Stall</th><th>Arrears</th><th>Next Due</th><th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= $r['lease_id'] ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars($r['stall_no']) ?></td>
          <td>₱<?= number_format($r['total_arrears'] ?? 0,2) ?></td>
          <td><?= htmlspecialchars($r['next_due'] ? ($r['next_due'].' — ₱'.number_format($r['next_amount'],2)) : '—') ?></td>
          <td>
            <form method="post" class="inline">
              <input type="hidden" name="lease_id" value="<?= $r['lease_id'] ?>">
              <label>Arrears <input type="number" step="0.01" name="total_arrears" value="<?= $r['total_arrears'] ?? 0 ?>"></label>
              <label>Due date <input type="date" name="due_date" value="<?= $r['next_due'] ?? '' ?>"></label>
              <label>Amount <input type="number" step="0.01" name="amount_due" value="<?= $r['next_amount'] ?? 0 ?>"></label>
              <button class="btn small">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
