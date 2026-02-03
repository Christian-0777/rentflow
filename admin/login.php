<?php
// admin/login.php
// One-click Admin login (no credentials)

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Fetch the first admin user from DB
  $stmt = $pdo->query("SELECT * FROM users WHERE role='admin' LIMIT 1");
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['user'] = $user;
    header('Location: dashboard.php'); // relative path inside /admin
    exit;
  } else {
    $msg = 'No admin account found.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/admin.css">
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Admin Login</h1>
    <?php if($msg): ?>
      <div class="alert error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
      <button class="btn">Login</button>
    </form>
  </div>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
