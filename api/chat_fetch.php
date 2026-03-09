<?php
// api/chat_fetch.php
// Fetches chat messages and notifications for polling (latest first)

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? 0;
if (!$userId) { echo json_encode([]); exit; }

$limit = (int)($_GET['limit'] ?? 20);
$peerId = (int)($_GET['peer'] ?? 0);

// If peer specified, fetch chat thread between user and peer
if ($peerId) {
  $stmt = $pdo->prepare("
    SELECT n.id, n.sender_id, n.receiver_id, n.type, n.title, n.message, n.created_at
    FROM notifications n
    WHERE (sender_id=? AND receiver_id=? AND type='chat')
       OR (sender_id=? AND receiver_id=? AND type='chat')
    ORDER BY n.created_at DESC
    LIMIT ?
  ");
  $stmt->execute([$userId, $peerId, $peerId, $userId, $limit]);
  echo json_encode($stmt->fetchAll());
  exit;
}

// Otherwise, fetch latest notifications for this user
$stmt = $pdo->prepare("
  SELECT id, type, title, message, created_at, is_read
  FROM notifications
  WHERE receiver_id=?
  ORDER BY created_at DESC
  LIMIT ?
");
$stmt->execute([$userId, $limit]);
echo json_encode($stmt->fetchAll());
