<?php
// admin/messages.php
// Messenger-inspired messaging interface for admin

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('admin');

$adminId = $_SESSION['user']['id'];
$selectedTenantId = (int)($_GET['tenant'] ?? 0);

// Fetch all conversations with tenants
$conversStmt = $pdo->prepare("
  SELECT DISTINCT
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) AS tenant_name,
    u.profile_photo,
    u.email,
    m.message AS last_message,
    m.created_at AS last_message_at,
    m.sender_id,
    (SELECT COUNT(*) FROM messages WHERE sender_id=u.id AND receiver_id=? AND is_read=0) AS unread_count
  FROM users u
  LEFT JOIN messages m ON (m.sender_id=u.id AND m.receiver_id=?) 
    OR (m.sender_id=? AND m.receiver_id=u.id)
  WHERE u.role='tenant'
  GROUP BY u.id
  ORDER BY m.created_at DESC
");
$conversStmt->execute([$adminId, $adminId, $adminId]);
$conversations = $conversStmt->fetchAll();

// Fetch messages for selected tenant
$messages = [];
if ($selectedTenantId) {
  $msgStmt = $pdo->prepare("
    SELECT 
      m.*,
      CONCAT(u.first_name, ' ', u.last_name) AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id=u.id
    WHERE (sender_id=? AND receiver_id=?) 
       OR (sender_id=? AND receiver_id=?)
    ORDER BY m.created_at ASC
  ");
  $msgStmt->execute([$selectedTenantId, $adminId, $adminId, $selectedTenantId]);
  $messages = $msgStmt->fetchAll();
  
  // Mark messages as read
  $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0")
    ->execute([$selectedTenantId, $adminId]);
}

// Get selected tenant info
$selectedTenant = null;
if ($selectedTenantId) {
  $tenantStmt = $pdo->prepare("
    SELECT *, 
      CONCAT(first_name, ' ', last_name) AS tenant_name
    FROM users 
    WHERE id=? AND role='tenant'
  ");
  $tenantStmt->execute([$selectedTenantId]);
  $selectedTenant = $tenantStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Messages - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/messenger.css">
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
      <li><a href="tenants.php"><i class="material-icons">people</i>Tenants</a></li>
      <li><a href="payments.php"><i class="material-icons">payments</i>Payments</a></li>
      <li><a href="reports.php"><i class="material-icons">assessment</i>Reports</a></li>
      <li><a href="stalls.php"><i class="material-icons">store</i>Stalls</a></li>
      <li><a href="messages.php" class="active" title="Messages"><i class="material-icons">mail</i>Messages</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
    </ul>
  </nav>
</header>

<!-- Messenger Container -->
<div class="messenger-container">
  <!-- Conversations List (Left Sidebar) -->
  <div class="messenger-sidebar">
    <div class="messenger-header">
      <h2>Messages</h2>
      <div class="messenger-search">
        <input type="text" id="conversationSearch" placeholder="Search conversations..." class="search-input">
        <i class="material-icons">search</i>
      </div>
    </div>

    <div class="conversations-list" id="conversationsList">
      <?php if (empty($conversations)): ?>
        <div class="empty-state">
          <i class="material-icons">mail_outline</i>
          <p>No conversations yet</p>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $conv): 
          $isSelected = $conv['id'] === $selectedTenantId;
          $unreadClass = ($conv['unread_count'] > 0) ? 'unread' : '';
        ?>
          <div class="conversation-item <?= $isSelected ? 'active' : '' ?> <?= $unreadClass ?>" 
               data-tenant-id="<?= $conv['id'] ?>"
               onclick="selectTenant(<?= $conv['id'] ?>)">
            <div class="conversation-avatar">
              <?php if ($conv['profile_photo']): ?>
                <img src="<?= htmlspecialchars($conv['profile_photo']) ?>" alt="Profile">
              <?php else: ?>
                <i class="material-icons">account_circle</i>
              <?php endif; ?>
              <?php if ($conv['unread_count'] > 0): ?>
                <span class="unread-badge"><?= $conv['unread_count'] ?></span>
              <?php endif; ?>
            </div>
            <div class="conversation-info">
              <div class="conversation-name"><?= htmlspecialchars($conv['tenant_name']) ?></div>
              <div class="conversation-preview">
                <?php 
                  $preview = $conv['last_message'];
                  $isSentByAdmin = $conv['sender_id'] == $adminId;
                  $prefix = $isSentByAdmin ? 'You: ' : '';
                  echo htmlspecialchars(substr($prefix . $preview, 0, 40));
                  if (strlen($prefix . $preview) > 40) echo '...';
                ?>
              </div>
            </div>
            <div class="conversation-time">
              <?php 
                if ($conv['last_message_at']) {
                  $time = date('M d', strtotime($conv['last_message_at']));
                  echo $time;
                }
              ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat View (Main Area) -->
  <div class="messenger-main">
    <?php if ($selectedTenant): ?>
      <!-- Chat Header -->
      <div class="chat-header">
        <div class="chat-header-info">
          <div class="chat-avatar">
            <?php if ($selectedTenant['profile_photo']): ?>
              <img src="<?= htmlspecialchars($selectedTenant['profile_photo']) ?>" alt="Profile">
            <?php else: ?>
              <i class="material-icons">account_circle</i>
            <?php endif; ?>
          </div>
          <div>
            <h3><?= htmlspecialchars($selectedTenant['tenant_name']) ?></h3>
            <p><?= htmlspecialchars($selectedTenant['email']) ?></p>
          </div>
        </div>
        <a href="/rentflow/admin/tenants.php?view=<?= $selectedTenant['id'] ?>" class="btn-icon" title="View Profile">
          <i class="material-icons">person</i>
        </a>
      </div>

      <!-- Messages -->
      <div class="messages-container" id="messagesContainer">
        <?php foreach ($messages as $msg): 
          $isAdminMessage = $msg['sender_id'] == $adminId;
          $align = $isAdminMessage ? 'right' : 'left';
          $bgClass = $isAdminMessage ? 'sent' : 'received';
        ?>
          <div class="message-group <?= $align ?>">
            <div class="message <?= $bgClass ?>">
              <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
              <?php if ($msg['attachment_path']): ?>
                <div class="message-attachment">
                  <a href="<?= htmlspecialchars($msg['attachment_path']) ?>" target="_blank" class="attachment-link">
                    <i class="material-icons">attachment</i> 
                    <?= htmlspecialchars(basename($msg['attachment_path'])) ?>
                  </a>
                </div>
              <?php endif; ?>
              <div class="message-time"><?= htmlspecialchars(date('H:i', strtotime($msg['created_at']))) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Message Input -->
      <div class="chat-input-area">
        <form id="messageForm" method="post" action="/rentflow/api/send_message.php">
          <input type="hidden" name="receiver_id" value="<?= $selectedTenantId ?>">
          <input type="hidden" name="from_admin" value="1">
          
          <div class="input-group">
            <textarea 
              name="message" 
              id="messageInput"
              class="message-input" 
              placeholder="Type your message..."
              required></textarea>
            <button type="submit" class="btn-send">
              <i class="material-icons">send</i>
            </button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <!-- Empty State -->
      <div class="empty-chat-state">
        <i class="material-icons">mail_outline</i>
        <h2>Select a conversation</h2>
        <p>Choose a tenant from the list to start messaging</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script src="/rentflow/public/assets/js/messenger.js"></script>
<script>
  // Auto-scroll to latest message
  function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
      container.scrollTop = container.scrollHeight;
    }
  }

  // Select tenant
  function selectTenant(tenantId) {
    window.location.href = '?tenant=' + tenantId;
  }

  // Search conversations
  document.getElementById('conversationSearch')?.addEventListener('keyup', (e) => {
    const query = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    items.forEach(item => {
      const name = item.querySelector('.conversation-name').textContent.toLowerCase();
      item.style.display = name.includes(query) ? 'flex' : 'none';
    });
  });

  // Handle message form submission
  document.getElementById('messageForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const form = e.target;
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();

    if (!message) return;

    const formData = new FormData(form);
    
    fetch('/rentflow/api/send_message.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        messageInput.value = '';
        location.reload(); // Reload to show new message
      } else {
        alert('Error: ' + (data.error || 'Failed to send message'));
      }
    })
    .catch(err => {
      alert('Error: ' + err.message);
    });
  });

  // Auto-scroll on load
  window.addEventListener('load', scrollToBottom);
</script>

</body>
</html>
