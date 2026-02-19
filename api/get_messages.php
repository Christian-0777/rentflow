<?php
// api/get_messages.php
// Fetch messages in a conversation between admin and tenant

require_once __DIR__.'/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? 0;
$peerId = (int)($_GET['peer'] ?? 0);
$limit = (int)($_GET['limit'] ?? 50);

if (!$userId || !$peerId) {
  echo json_encode(['error' => 'Missing user or peer']);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT 
      m.id,
      m.sender_id,
      m.receiver_id,
      m.message,
      m.sender_email,
      m.attachment_path,
      m.attachment_type,
      m.created_at,
      u.first_name,
      u.last_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
    LIMIT ?
  ");

  $stmt->execute([$userId, $peerId, $peerId, $userId, $limit]);
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Mark messages as read if they are for current user
  $updateStmt = $pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
  ");
  $updateStmt->execute([$userId, $peerId]);

  echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
  ]);

} catch (PDOException $e) {
  error_log('Database error: ' . $e->getMessage());
  echo json_encode(['error' => 'Failed to fetch messages']);
}
