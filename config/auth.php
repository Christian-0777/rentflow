<?php
// config/auth.php
// Session start, role checks, admin/treasury 3-code login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_role($role) {
  if (!isset($_SESSION['user'])) {
    header('Location: /admin/login.php');
    exit;
  }
  if (is_array($role)) {
    if (!in_array($_SESSION['user']['role'], $role)) {
      header('Location: /admin/login.php');
      exit;
    }
  } else {
    if ($_SESSION['user']['role'] !== $role) {
      header('Location: /admin/login.php');
      exit;
    }
  }
}

function admin_treasury_login($pdo, $role, $c1, $c2, $c3) {
  $stmt = $pdo->prepare("SELECT * FROM auth_codes WHERE role=? AND valid_until>=NOW()");
  $stmt->execute([$role]);
  $row = $stmt->fetch();
  if ($row && $row['code1'] === $c1 && $row['code2'] === $c2 && $row['code3'] === $c3) {
    $_SESSION['user'] = ['id'=>$row['id'], 'role'=>$role, 'full_name'=>ucfirst($role).' User'];
    return true;
  }
  return false;
}
