<?php
// tenant/messages.php
// Display message threads with admin

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$tenantId = $_SESSION['user']['id'];

// Get message threads
$threads = $pdo->prepare("
    SELECT mt.id, mt.last_message_at, mt.last_message_id,
           u.first_name, u.last_name, u.business_name,
           m.subject, m.message, m.is_read
    FROM message_threads mt
    JOIN users u ON (mt.user1_id = u.id OR mt.user2_id = u.id) AND u.id != ?
    JOIN messages m ON m.id = mt.last_message_id
    WHERE (mt.user1_id = ? OR mt.user2_id = ?) AND u.role = 'admin'
    ORDER BY mt.last_message_at DESC
");
$threads->execute([$tenantId, $tenantId, $tenantId]);
$conversations = $threads->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Messages - RentFlow</title>
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
      <li><a href="home.php" title="Home"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="messages.php" class="active" title="Messages"><i class="material-icons">message</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">

  <div class="page-header">
    <h1>Messages</h1>
    <p>Communicate with your admin</p>
  </div>

  <?php if (empty($conversations)): ?>
    <div class="tenant-card">
      <p class="text-center text-muted">No messages yet. Messages from admin will appear here.</p>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($conversations as $conv): ?>
        <div class="col-md-6 mb-3">
          <div class="tenant-card">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5>Admin</h5>
                <p class="mb-1"><strong>Subject:</strong> <?= htmlspecialchars($conv['subject'] ?? 'No subject') ?></p>
                <p class="mb-1 text-truncate" style="max-width: 300px;"><?= htmlspecialchars(substr($conv['message'], 0, 100)) ?>...</p>
                <small class="text-muted">
                  <?= htmlspecialchars(date('M d, Y h:i A', strtotime($conv['last_message_at']))) ?>
                  <?php if (!$conv['is_read']): ?>
                    <span class="badge bg-danger">New</span>
                  <?php endif; ?>
                </small>
              </div>
              <a href="messages.php?thread=<?= $conv['id'] ?>" class="btn btn-sm btn-primary">View</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/tenant.js"></script>
</body>
</html>