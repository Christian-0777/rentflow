<?php
// treasury/login.php
// One-click Treasury login (no credentials)

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Fetch the first treasury user from DB
  $stmt = $pdo->query("SELECT * FROM users WHERE role='treasury' LIMIT 1");
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['user'] = $user;
    // 👇 Adjust path depending on where dashboard.php is located
    header('Location: /rentflow/treasury/dashboard.php');
    exit;
  } else {
    $msg = 'No treasury account found.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Treasury Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSS links -->
  <link href="/rentflow/public/assets/css/base.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/auth.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/layout.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/components.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/treasury.css" rel="stylesheet">
</head>
<body class="treasury">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Treasury Login</h1>
    <?php if($msg): ?><div class="alert error"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
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
