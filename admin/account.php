<?php
// admin/account.php
// Admin profile view, settings update, and logout

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$msg = '';
$adminId = $_SESSION['user']['id'] ?? 0;

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = $_POST['full_name'];
  $location = $_POST['location'];
  
  // Split full name into first and last name
  $nameParts = explode(' ', $fullName, 2);
  $firstName = $nameParts[0] ?? '';
  $lastName = $nameParts[1] ?? '';
  
  $pdo->prepare("UPDATE users SET first_name=?, last_name=?, location=? WHERE id=?")
      ->execute([$firstName, $lastName, $location, $adminId]);
  $msg = 'Profile updated.';
}

// Fetch admin info
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name, email, location, profile_photo, cover_photo FROM users WHERE id=?");
$stmt->execute([$adminId]);
$u = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
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
  <h1>Account</h1>
  <?php if ($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <section class="grid">
    <div class="card">
      <h3>Profile</h3>
      <p><strong>Name:</strong> <?= htmlspecialchars($u['full_name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($u['email']) ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($u['location'] ?? '—') ?></p>
    </div>

    <div class="card">
      <h3>Settings</h3>
      <form method="post">
        <input name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" placeholder="Full name" required>
        <input name="location" value="<?= htmlspecialchars($u['location'] ?? '') ?>" placeholder="Location">
        <button class="btn">Save</button>
      </form>
    </div>

    <div class="card">
      <h3>Logout</h3>
      <a class="btn outline" href="/public/logout.php">Log out</a>
    </div>
  </section>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>
<script src="/rentflow/public/assets/js/table.js"></script>
</body>
</html>
