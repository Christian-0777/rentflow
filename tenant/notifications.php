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
  <title>Notifications - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/bootstrap-custom.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="dashboard.php" title="Dashboard"><i class="material-icons">dashboard</i><span></span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span></span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" class="active" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">
  <div class="page-header">
    <h1>Notifications</h1>
    <p>Stay updated with important announcements</p>
  </div>

  <div class="tenant-card" style="margin-bottom: 24px;">
    <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">notifications_active</i>Recent Notifications</h3>
    <?php if (empty($items)): ?>
      <p style="color: var(--secondary); margin: 0;">No notifications yet.</p>
    <?php else: ?>
      <ul style="list-style: none; padding: 0; margin: 0;">
        <?php foreach ($items as $n): ?>
          <li style="padding: 16px 0; border-bottom: 1px solid var(--border);">
            <div style="display: flex; gap: 12px;">
              <div style="flex: 1;">
                <h4 style="margin: 0 0 4px 0; font-weight: 600;"><?= htmlspecialchars($n['title'] ?? 'Notification') ?></h4>
                <p style="margin: 0 0 8px 0; color: var(--dark); font-size: 14px;"><?= htmlspecialchars($n['message']) ?></p>
                <small style="color: var(--secondary);">
                  <i class="material-icons" style="font-size: 14px; vertical-align: text-bottom;">schedule</i>
                  <?= htmlspecialchars($n['created_at']) ?> — from <?= htmlspecialchars($n['sender_name']) ?>
                </small>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="tenant-card">
    <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">chat</i>Chat with Admin</h3>
    <p style="font-size: 14px; margin-bottom: 16px;">Send a message to our support team for assistance.</p>
    <button class="btn btn-primary" id="sendMessageButton" type="button">
      <i class="material-icons" style="font-size: 18px;">mail</i> Send Message
    </button>
  </div>

  <!-- Reply Modal -->
  <div id="replyModal" class="modal">
    <div class="modal-content">
      <button class="modal-close">&times;</button>
      <h2 style="margin-bottom: 16px;">Send Message to Admin</h2>
      <form id="messageForm" action="/rentflow/api/chat_send.php" method="post">
        <input type="hidden" name="receiver_id" value="<?= $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn(); ?>">
        <div class="form-group">
          <label>Your Message</label>
          <textarea name="message" placeholder="Type your message here..." required></textarea>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
          <button class="btn btn-secondary" type="button" onclick="closeModal('replyModal')">Cancel</button>
          <button class="btn btn-primary" type="submit">Send Message</button>
        </div>
      </form>
    </div>
  </div>
</main>

<footer style="background-color: var(--white); border-top: 1px solid var(--border); padding: 30px 20px; margin-top: 40px;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px;">
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">About RentFlow</h4>
        <p style="font-size: 14px; color: var(--secondary); margin: 0; line-height: 1.6;">A modern stall rental management system for Baliwag Public Market with transparent pricing and easy payment tracking.</p>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Quick Links</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="dashboard.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Dashboard</a></li>
          <li style="margin-bottom: 8px;"><a href="stalls.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Stalls</a></li>
          <li style="margin-bottom: 8px;"><a href="payments.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Payments</a></li>
          <li><a href="support.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Support</a></li>
        </ul>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Account</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="profile.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Profile</a></li>
          <li style="margin-bottom: 8px;"><a href="account.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Settings</a></li>
          <li style="margin-bottom: 8px;"><a href="notifications.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Notifications</a></li>
          <li><a href="/rentflow/public/logout.php" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Logout</a></li>
        </ul>
      </div>
      <div>
        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: 12px; font-size: 16px;">Legal</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;"><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Terms of Service</a></li>
          <li style="margin-bottom: 8px;"><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Privacy Policy</a></li>
          <li><a href="#" style="color: var(--secondary); text-decoration: none; font-size: 14px; transition: color 0.2s;">Contact Us</a></li>
        </ul>
      </div>
    </div>
    <div style="border-top: 1px solid var(--border); padding-top: 20px; text-align: center; color: var(--secondary); font-size: 13px;">
      <p style="margin: 0;">&copy; <?= date('Y') ?> RentFlow. All rights reserved. | Baliwag Public Market Stall Management System</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/rentflow.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const sendMessageButton = document.getElementById('sendMessageButton');
  const replyModal = document.getElementById('replyModal');
  const messageForm = document.getElementById('messageForm');
  
  if (sendMessageButton) {
    sendMessageButton.addEventListener('click', function() {
      openModal(replyModal);
    });
  }
  
  if (replyModal) {
    const closeBtn = replyModal.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeModal(replyModal);
        if (messageForm) messageForm.reset();
      });
    }
  }
});
</script>
</body>
</html>

