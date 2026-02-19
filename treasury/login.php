<?php
// treasury/login.php
// Treasury role has been removed - redirecting to admin login

header('Location: /admin/login.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Treasury Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSS links -->
  <link href="/rentflow/public/assets/css/base.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/layout.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/auth-common.css" rel="stylesheet">
  <link href="/rentflow/public/assets/css/login.css" rel="stylesheet">
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
