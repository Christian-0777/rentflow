<?php
// treasury/dashboard.php
// Treasury role has been removed

header('Location: /admin/login.php');
exit;

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $leaseId = (int)$_POST['lease_id'];
  $newArrears = (float)$_POST['total_arrears'];
  $pdo->prepare("UPDATE arrears SET total_arrears=?, last_updated=NOW() WHERE lease_id=?")
      ->execute([$newArrears, $leaseId]);
  $msg = 'Arrears updated.';
}

$rows = $pdo->query("
  SELECT l.id AS lease_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, s.stall_no, a.total_arrears
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
  <title>Dashboard - RentFlow</title>
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
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Treasury Dashboard</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th>Lease</th><th>Tenant</th><th>Stall</th><th>Arrears</th><th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= $r['lease_id'] ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars($r['stall_no']) ?></td>
          <td>₱<?= number_format($r['total_arrears'] ?? 0,2) ?></td>
          <td>
            <form method="post" class="inline">
              <input type="hidden" name="lease_id" value="<?= $r['lease_id'] ?>">
              <input type="number" step="0.01" name="total_arrears" value="<?= $r['total_arrears'] ?? 0 ?>">
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
