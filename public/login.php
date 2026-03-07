<?php
// public/login.php
// New tenant login with email and 7-digit code

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code  = trim($_POST['code']);

    // Check if email exists in tenant_accounts
    $stmt = $pdo->prepare("SELECT id, code_hash FROM tenant_accounts WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $account = $stmt->fetch();

    if ($account) {
        if (password_verify($code, $account['code_hash'])) {
            // Find the user in users table
            $userStmt = $pdo->prepare("SELECT id, role, email, tenant_id, first_name, last_name, business_name, status
                                       FROM users
                                       WHERE email=? AND role='tenant' LIMIT 1");
            $userStmt->execute([$email]);
            $u = $userStmt->fetch();

            if ($u) {
                $_SESSION['user'] = [
                    'id'         => $u['id'],
                    'role'       => $u['role'],
                    'email'      => $u['email'],
                    'tenant_id'  => $u['tenant_id'],
                    'first_name' => $u['first_name'],
                    'last_name'  => $u['last_name'],
                    'business_name' => $u['business_name'],
                    'status'     => $u['status']
                ];
                header('Location: /rentflow/tenant/home.php');
                exit;
            } else {
                $msg = 'Account setup incomplete. Please contact admin.';
            }
        } else {
            $msg = 'Credentials not match';
        }
    } else {
        $msg = 'No account found';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Login - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/auth-common.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/login.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="public">

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="content">
  <div class="card-container">
    <h1>Tenant Login</h1>
    <?php if($msg): ?><div class="alert error"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
      <input name="email" type="email" placeholder="Email" required>
      <input name="code" type="text" placeholder="7-digit Code" maxlength="7" required>
      <button type="submit" class="btn">Login</button>
    </form>
  </div>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
