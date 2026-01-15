<?php
// public/index.php
// Tenant-facing landing page with available stalls preview

require_once __DIR__.'/../config/db.php';

$stmt = $pdo->query("SELECT stall_no, type, location FROM stalls WHERE status='available' LIMIT 6");
$available = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
  <nav class="navigation">
    <ul>
      <li><a href="register.php">Register</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>
</header>

<main class="layout">
  <section class="hero">
    <h1>Find your stall at Baliwag Public Market</h1>
    <p>Transparent rent, timely reminders, and tracking.</p>
    <div class="cta">
      <a class="btn" href="register.php">Register</a>
      <a class="btn outline" href="login.php">Login</a>
    </div>
  </section>

  <section class="cards">
    <h2>Available stalls</h2>
    <div class="grid">
      <?php foreach ($available as $row): ?>
        <div class="card">
          <h3><?= htmlspecialchars($row['stall_no']) ?> (<?= htmlspecialchars($row['type']) ?>)</h3>
          <p><?= htmlspecialchars($row['location']) ?></p>
          <a class="btn small" href="/tenant/stalls.php">Apply</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
