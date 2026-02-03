<?php
// public/index.php
// Tenant-facing landing page with available stalls preview

require_once __DIR__.'/../config/db.php';

$stmt = $pdo->query("SELECT stall_no, type, location, picture_path FROM stalls WHERE status='available' LIMIT 12");
$available = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to RentFlow - Baliwag Public Market</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/bootstrap-custom.css">
</head>
<body>

<header class="header">
  <h1 class="site-title">RentFlow</h1>
  <ul class="header-nav">
    <li><a href="login.php">Login</a></li>
    <li><a href="register.php" class="btn btn-primary" style="padding: 8px 16px; margin: 0;">Register</a></li>
  </ul>
</header>

<main class="layout">
  <section class="hero">
    <h1>Find Your Stall at Baliwag Public Market</h1>
    <p>Transparent rent, timely reminders, and comprehensive tracking</p>
    <div class="cta">
      <a class="btn btn-primary" href="register.php">Get Started</a>
      <a class="btn btn-secondary" href="login.php">Login</a>
    </div>
  </section>

  <section class="cards">
    <h2>Available Stalls</h2>
    <?php if (!empty($available)): ?>
      <div class="card-grid">
        <?php foreach ($available as $row): ?>
          <div class="card">
            <?php if ($row['picture_path']): ?>
              <img src="<?= htmlspecialchars($row['picture_path']) ?>" alt="Stall Picture" class="card-image" onclick="openImageModal('<?= htmlspecialchars($row['picture_path']) ?>', 'Stall <?= htmlspecialchars($row['stall_no']) ?>')">
            <?php else: ?>
              <div class="card-image" style="background: var(--light); display: flex; align-items: center; justify-content: center; color: var(--secondary);">
                <i class="material-icons" style="font-size: 48px;">image_not_supported</i>
              </div>
            <?php endif; ?>
            <div class="card-body">
              <h3 class="card-title">Stall <?= htmlspecialchars($row['stall_no']) ?></h3>
              <p class="card-text">
                <strong>Type:</strong> <?= htmlspecialchars($row['type']) ?><br>
                <strong>Location:</strong> <?= htmlspecialchars($row['location']) ?>
              </p>
              <div class="card-footer">
                <a class="btn btn-primary" href="register.php">Apply Now</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="text-align: center; padding: 40px 20px; color: var(--secondary);">
        <p>No available stalls at the moment. Please check back later.</p>
      </div>
    <?php endif; ?>
  </section>

  <section style="background: var(--primary-light); padding: 40px 20px; border-radius: 12px; margin-top: 40px; text-align: center;">
    <h2 style="color: var(--primary); margin-bottom: 16px;">Why RentFlow?</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
      <div>
        <i class="material-icons" style="font-size: 48px; color: var(--primary); margin-bottom: 12px;">visibility</i>
        <h4>Transparent Process</h4>
        <p style="font-size: 14px; color: var(--secondary);">Clear and honest rent pricing</p>
      </div>
      <div>
        <i class="material-icons" style="font-size: 48px; color: var(--primary); margin-bottom: 12px;">schedule</i>
        <h4>Timely Reminders</h4>
        <p style="font-size: 14px; color: var(--secondary);">Never miss a payment deadline</p>
      </div>
      <div>
        <i class="material-icons" style="font-size: 48px; color: var(--primary); margin-bottom: 12px;">analytics</i>
        <h4>Easy Tracking</h4>
        <p style="font-size: 14px; color: var(--secondary);">Monitor your payments and status</p>
      </div>
    </div>
  </section>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved. | <a href="#" style="color: var(--primary); text-decoration: none;">Privacy Policy</a></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/rentflow.js"></script>

</body>
</html>
