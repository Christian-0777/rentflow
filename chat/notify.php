<?php
// chat/notify.php
// Pushes new chat messages as notifications

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sender = $_SESSION['user']['id'] ?? 0;
$receiver = (int)($_POST['receiver_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');

if ($sender && $receiver && $msg) {
  $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?,?, 'chat', 'Chat', ?)")
      ->execute([$sender, $receiver, $msg]);
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/tenant/notifications.php'));
