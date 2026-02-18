<?php
// chat/chat.php
// Simple chat interface for tenant ↔ admin communication

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__.'/../partials/header.php';

$userId = $_SESSION['user']['id'] ?? 0;
$role = $_SESSION['user']['role'] ?? '';
$peerId = (int)($_GET['peer'] ?? 0);

// Fetch peer name
$peerName = '';
if ($peerId) {
  $peerName = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE id=?");
  $peerName->execute([$peerId]);
  $peerName = $peerName->fetchColumn();
}
?>
<main class="content">
  <h1>Chat <?= $peerName ? 'with '.htmlspecialchars($peerName) : '' ?></h1>

  <form action="/chat/notify.php" method="post" class="card">
    <input type="hidden" name="receiver_id" value="<?= $peerId ?>">
    <textarea name="message" placeholder="Type a message..." required></textarea>
    <button class="btn">Send</button>
  </form>

  <div id="chatThread" class="card"></div>
</main>

<script src="/public/assets/js/notification.js"></script>
<script>
// Poll chat thread
setInterval(() => {
  fetch('/api/chat_fetch.php?peer=<?= $peerId ?>&limit=50')
    .then(r => r.json())
    .then(items => {
      const el = document.getElementById('chatThread');
      el.innerHTML = items.map(i => `
        <div class="chat-item">
          <strong>${i.sender_id==<?= $userId ?> ? 'You' : 'Peer'}:</strong>
          <span>${escapeHtml(i.message)}</span>
          <small>${i.created_at}</small>
        </div>
      `).join('');
    });
}, 2000);

function escapeHtml(s){return s.replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
</script>
<?php require_once __DIR__.'/../partials/footer.php'; ?>
