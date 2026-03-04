<?php
// admin/login.php
// DB-backed Admin login

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = 'error';
// show success message when redirected after registration
if (isset($_GET['registered'])) {
  $msg = 'Registration successful. You can now log in.';
  $msg_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  if ($email === '' || $password === '') {
    $msg = 'Please provide both email and password.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' AND email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      if ($user['confirmed'] != 1) {
        $msg = 'Your account is not confirmed yet. Please check your email to confirm.';
      } elseif (password_verify($password, $user['password_hash'])) {
        // successful login
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit;
      } else {
        $msg = 'Credentials Not Match';
      }
    } else {
      $msg = 'No Account Found, Register First';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/login.css">
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
      <div class="alert <?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required>

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>

      <div style="margin-top:12px;">
        <button class="btn" type="submit">Login</button>
        <a href="register.php" style="margin-left:12px;">Register</a>
      </div>
    </form>
  </div>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
