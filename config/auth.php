<?php
// config/auth.php
// Session start and role checks (admin 3-code login removed for treasury)

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

// Treasury-special auth helper removed with treasury role

