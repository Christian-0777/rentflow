<?php
// tenant/notifications.php
// Tenant view of notifications and chat

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$notifications = $pdo->prepare("
  SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name
  FROM notifications n
  JOIN users u ON n.sender_id=u.id
  WHERE n.receiver_id=? ORDER BY n.created_at DESC LIMIT 50
");
$notifications->execute([$tenantId]);
$items = $notifications->fetchAll();

// Optional: mark as read
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE receiver_id=?")->execute([$tenantId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Dashboard - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="tenant">

<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
      <li><a href="payments.php">Payments</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="profile.php" class="nav-profile" title="Account"><i class="material-icons">person</i></a></li>
      <li><a href="support.php" title="Contact Support"><i class="material-icons">contact_support</i></a></li>
      <li><a href="/rentflow/public/logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Notifications</h1>
  <ul class="list" id="notifList">
    <?php foreach ($items as $n): ?>
      <li>
        <strong><?= htmlspecialchars($n['title'] ?? 'Notification') ?></strong>
        <div><?= htmlspecialchars($n['message']) ?></div>
        <small><?= htmlspecialchars($n['created_at']) ?> — from <?= htmlspecialchars($n['sender_name']) ?></small>
      </li>
    <?php endforeach; ?>
  </ul>

  <h2>Chat with Admin</h2>
  <button class="btn" onclick="openReplyModal()">Reply</button>

  <!-- Reply Modal -->
  <div id="replyModal" class="modal" style="display: none;">
    <div class="modal-content">
      <h2>Send Message to Admin</h2>
      <form action="/api/chat_send.php" method="post" class="card">
        <input type="hidden" name="receiver_id" value="<?= $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn(); ?>">
        <textarea name="message" placeholder="Type a message..." required></textarea>
        <button class="btn" type="submit">Send</button>
        <button class="btn" type="button" onclick="closeReplyModal()">Cancel</button>
      </form>
    </div>
  </div>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
function openReplyModal() {
  document.getElementById('replyModal').style.display = 'block';
}

function closeReplyModal() {
  document.getElementById('replyModal').style.display = 'none';
}
</script>

</body>
</html>
 
