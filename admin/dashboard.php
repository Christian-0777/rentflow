<?php
// admin/dashboard.php
// Admin dashboard with stall availability, upcoming payments, revenue highlights, and recent payments

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('admin');

// Stall availability summary by type
$stalls = $pdo->query("
  SELECT 
    s.type,
    SUM(CASE WHEN s.status='available' THEN 1 ELSE 0 END) AS available_count,
    SUM(CASE WHEN s.status='occupied' THEN 1 ELSE 0 END) AS occupied_count,
    SUM(CASE WHEN s.status='maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
    COUNT(*) AS total_count
  FROM stalls s
  GROUP BY s.type
  ORDER BY s.type
")->fetchAll();

// Upcoming payments (dues not paid yet)
$upcoming = $pdo->query("
  SELECT s.stall_no, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.business_name, d.amount_due, d.due_date,
         CASE WHEN d.paid=1 THEN 'Paid' ELSE 'Not Paid' END AS remarks
  FROM dues d
  JOIN leases l ON d.lease_id=l.id
  JOIN stalls s ON l.stall_id=s.id
  JOIN users u ON l.tenant_id=u.id
  WHERE d.due_date>=CURDATE()
  ORDER BY d.due_date ASC
  LIMIT 10
")->fetchAll();

// Revenue highlights (last 90 days)
$rev = $pdo->query("
  SELECT MAX(amount) AS highest, MIN(amount) AS lowest, AVG(amount) AS average
  FROM payments
  WHERE payment_date>=DATE_SUB(CURDATE(), INTERVAL 90 DAY)
")->fetch();

// Recent payments with lateness check
$recent = $pdo->query("
  SELECT s.stall_no, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.business_name, p.amount, p.payment_date, p.method,
         CASE
           WHEN p.payment_date <= (
             SELECT due_date FROM dues WHERE lease_id=p.lease_id ORDER BY due_date DESC LIMIT 1
           )
           THEN 'On time'
           ELSE CONCAT(DATEDIFF(p.payment_date, (
             SELECT due_date FROM dues WHERE lease_id=p.lease_id ORDER BY due_date DESC LIMIT 1
           )), ' days late')
         END AS remarks
  FROM payments p
  JOIN leases l ON p.lease_id=l.id
  JOIN stalls s ON l.stall_id=s.id
  JOIN users u ON l.tenant_id=u.id
  ORDER BY p.payment_date DESC
  LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/admin.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="tenants.php">Tenants</a></li>
      <li><a href="payments.php" class="active">Payments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i></a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i></a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Admin Dashboard</h1>

  <!-- Stall availability -->
  <section class="table-section">
    <h2>Stall Availability</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Category</th><th>Available</th><th>Occupied</th><th>Under Maintenance</th><th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stalls as $s): ?>
          <tr>
            <td><?= ucfirst($s['type']) ?></td>
            <td><?= $s['available_count'] ?></td>
            <td><?= $s['occupied_count'] ?></td>
            <td><?= $s['maintenance_count'] ?></td>
            <td><?= $s['total_count'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Upcoming payments -->
  <section class="table-section">
    <h2>Upcoming Payments</h2>
    <table class="table">
      <thead>
        <tr><th>Stall</th><th>Tenant</th><th>Business</th><th>Amount</th><th>Due</th><th>Remarks</th></tr>
      </thead>
      <tbody>
        <?php foreach ($upcoming as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['stall_no']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['business_name']) ?></td>
            <td>₱<?= number_format($row['amount_due'],2) ?></td>
            <td><?= htmlspecialchars($row['due_date']) ?></td>
            <td><span class="badge"><?= htmlspecialchars($row['remarks']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Revenue highlights -->
  <section class="cards">
    <h2>Revenue Highlights (Last 90 Days)</h2>
    <div class="grid">
      <div class="card"><strong>Highest:</strong> ₱<?= number_format($rev['highest'] ?? 0,2) ?></div>
      <div class="card"><strong>Lowest:</strong> ₱<?= number_format($rev['lowest'] ?? 0,2) ?></div>
      <div class="card"><strong>Average:</strong> ₱<?= number_format($rev['average'] ?? 0,2) ?></div>
    </div>
  </section>

  <!-- Recent payments -->
  <section class="table-section">
    <h2>Recent Payments</h2>
    <table class="table">
      <thead>
        <tr><th>Stall</th><th>Tenant</th><th>Business</th><th>Amount</th><th>Date</th><th>Remarks</th><th>Method</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['stall_no']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['business_name']) ?></td>
            <td>₱<?= number_format($row['amount'],2) ?></td>
            <td><?= htmlspecialchars($row['payment_date']) ?></td>
            <td><?= htmlspecialchars($row['remarks']) ?></td>
            <td><?= htmlspecialchars($row['method']) ?></td>
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
<script src="/rentflow/public/assets/js/rentflow.js"></script>
<script src="/rentflow/public/assets/js/table.js"></script>
</body>
</html>
