<?php
// config/auth.php
// Session start, role checks, admin/treasury 3-code login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_role($role) {
  $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
  
  if (!isset($_SESSION['user'])) {
    if ($isAjax) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }
    header('Location: /admin/login.php');
    exit;
  }
  
  $authorized = false;
  if (is_array($role)) {
    $authorized = in_array($_SESSION['user']['role'], $role);
  } else {
    $authorized = $_SESSION['user']['role'] === $role;
  }
  
  if (!$authorized) {
    if ($isAjax) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    header('Location: /admin/login.php');
    exit;
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
