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
  <link rel="stylesheet" href="/rentflow/public/assets/css/tenant-bootstrap.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="dashboard.php" class="active" title="Dashboard"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span></span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
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

  <div class="tenant-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">
    <i class="material-icons" style="font-size: 40px; display: block; margin-bottom: 12px;">chat_bubble</i>
    <h3 style="color: white; margin-bottom: 8px;">Need Support?</h3>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 16px;">Head to your profile to chat with our admin support team.</p>
    <a href="profile.php" class="btn" style="background: white; color: #667eea; font-weight: 600; border: none; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 6px;">
      <i class="material-icons" style="font-size: 18px; vertical-align: middle;">person</i> Go to Profile
    </a>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 
