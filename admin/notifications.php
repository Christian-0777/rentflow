<?php
// admin/notifications.php
// Shows latest notifications and chat thread; sending messages posts to API

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Allow admin and treasury
require_role(['admin', 'treasury']);

$to = (int)($_GET['to'] ?? 0);
$threads = $pdo->query("
  SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name
  FROM notifications n
  JOIN users u ON n.sender_id=u.id
  WHERE n.receiver_id IN (SELECT id FROM users WHERE role='admin') OR n.type='system'
  ORDER BY n.created_at DESC
  LIMIT 50
")->fetchAll();

$chat = [];
if ($to) {
  $stmt = $pdo->prepare("
    SELECT n.*, CONCAT(s.first_name, ' ', s.last_name) AS sender_name
    FROM notifications n
    JOIN users s ON n.sender_id=s.id
    WHERE (sender_id=? AND receiver_id IN (SELECT id FROM users WHERE role='admin'))
       OR (receiver_id=? AND sender_id IN (SELECT id FROM users WHERE role='admin'))
    ORDER BY n.created_at DESC
    LIMIT 50
  ");
  $stmt->execute([$to, $to]);
  $chat = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Notifications</h1>

  <section class="grid">
    <div class="card">
      <h3>Latest</h3>
      <ul class="list">
        <?php foreach ($threads as $n): 
          $hasAttachment = strpos($n['message'], 'Attachment:') !== false;
          $attachmentIndicator = $hasAttachment ? ' 📎' : '';
          $safeTitle = htmlspecialchars($n['title'] ?? 'Message', ENT_QUOTES);
          $safeMessage = htmlspecialchars($n['message'], ENT_QUOTES);
        ?>
          <li data-id="<?= $n['id'] ?>" data-title="<?= $safeTitle ?>" data-message="<?= $safeMessage ?>" onclick="openNotificationFromEl(this)">
            <strong><?= $safeTitle . $attachmentIndicator ?></strong>
            <div><?= nl2br(htmlspecialchars($n['message'])) ?></div>
            <small><?= htmlspecialchars($n['created_at']) ?> — from <?= htmlspecialchars($n['sender_name']) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card">
      <h3>Chat with tenant</h3>
      <form action="/api/chat_send.php" method="post">
        <input type="hidden" name="receiver_id" value="<?= $to ?>">
        <textarea name="message" placeholder="Type a message..." required></textarea>
        <button class="btn">Send</button>
      </form>
      <div class="chat">
        <?php foreach ($chat as $c): 
          // Parse message for attachments
          $message = $c['message'];
          $attachmentLink = '';
          if (preg_match('/Attachment: (\/rentflow\/uploads\/support\/[^\s]+)/', $message, $matches)) {
            $attachmentPath = $matches[1];
            $message = preg_replace('/\n\nAttachment: [^\s]+/', '', $message);
            $attachmentLink = '<br><strong>Attachment:</strong> <a href="' . htmlspecialchars($attachmentPath) . '" target="_blank">View Image</a>';
          }
        ?>
          <div class="chat-item">
            <strong><?= htmlspecialchars($c['sender_name']) ?>:</strong>
            <span><?= nl2br(htmlspecialchars($message)) . $attachmentLink ?></span>
            <small><?= htmlspecialchars($c['created_at']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<!-- Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
  <div class="modal-content">
    <h2 id="modalTitle">Notification</h2>
    <p id="modalMessage"></p>
    <button class="btn" onclick="closeModal()">Close</button>
  </div>
</div>

<!-- Application Details Modal -->
<div id="applicationModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 600px;">
    <span onclick="closeApplicationModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h2>Stall Application Details</h2>
    <div id="applicationDetails">
      <!-- Application details will be loaded here -->
    </div>
    <div id="applicationActions" style="margin-top: 20px; text-align: center; display: none;">
      <button class="btn success" onclick="approveApplication()">Approve Application</button>
      <button class="btn danger" onclick="rejectApplication()">Reject Application</button>
      <button class="btn" onclick="closeApplicationModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="modal" style="display: none;">
  <div class="modal-content" style="background: black; max-width: 90%; max-height: 90vh; padding: 0; position: relative;">
    <span onclick="closeImagePreview()" style="position: absolute; top: 10px; right: 20px; font-size: 36px; font-weight: bold; cursor: pointer; color: white; z-index: 10;">&times;</span>
    <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 90vh; display: block; margin: auto;">
  </div>
</div>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
function openNotificationModal(id, title, message) {
  document.getElementById('modalTitle').textContent = title;
  
  // Parse message for attachments
  let displayMessage = message;
  let attachmentLink = '';
  
  // Check if message contains attachment
  const attachmentMatch = message.match(/Attachment: (\/rentflow\/uploads\/support\/[^\s]+)/);
  if (attachmentMatch) {
    const attachmentPath = attachmentMatch[1];
    // Remove attachment line from display message
    displayMessage = message.replace(/\n\nAttachment: [^\s]+/, '');
    attachmentLink = '<p><strong>Attachment:</strong> <a href="' + attachmentPath + '" target="_blank">View Image</a></p>';
  }
  
  document.getElementById('modalMessage').innerHTML = displayMessage.replace(/\n/g, '<br>') + attachmentLink;
  document.getElementById('notificationModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('notificationModal').style.display = 'none';
}

function decodeHTMLEntities(text) {
  const txt = document.createElement('textarea');
  txt.innerHTML = text;
  return txt.value;
}

function openNotificationFromEl(el) {
  const id = el.dataset.id;
  const title = decodeHTMLEntities(el.dataset.title || 'Notification');
  const message = decodeHTMLEntities(el.dataset.message || '');
  
  // Check if this is a stall application notification
  if (title === 'New stall application') {
    openApplicationModal(message);
  } else {
    openNotificationModal(id, title, message);
  }
}

let currentApplicationId = null;

function openApplicationModal(message) {
  // Extract application ID from message
  const appIdMatch = message.match(/Application ID: (\w+)/);
  if (!appIdMatch) {
    alert('Could not find application ID in notification');
    return;
  }
  
  const appId = appIdMatch[1];
  currentApplicationId = appId;
  
  // Fetch application details
  fetch(`/rentflow/api/get_application_details.php?id=${appId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert('Error: ' + data.error);
        return;
      }
      
      // Display application details
      const detailsHtml = `
        <div style="text-align: left; margin-bottom: 20px;">
          <p><strong>Application ID:</strong> ${data.id}</p>
          <p><strong>Tenant Name:</strong> ${data.tenant_name}</p>
          <p><strong>Tenant ID:</strong> ${data.tenant_id}</p>
          <p><strong>Email:</strong> ${data.email}</p>
          <p><strong>Business Name:</strong> ${data.business_name}</p>
          <p><strong>Business Description:</strong> ${data.business_description}</p>
          <p><strong>Stall Type:</strong> ${data.type}</p>
          <p><strong>Status:</strong> <span class="badge ${data.status === 'pending' ? 'warning' : data.status === 'approved' ? 'success' : 'danger'}">${data.status}</span></p>
          <p><strong>Submitted:</strong> ${data.created_at}</p>
        </div>
        <div style="margin-bottom: 20px;">
          <h4>Documents:</h4>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px;">
            <div style="text-align: center;">
              <strong style="display: block; margin-bottom: 8px; font-size: 12px;">Business Permit</strong>
              <img src="${data.business_permit_path}" alt="Business Permit" style="max-width: 100%; max-height: 120px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" onclick="openImagePreview('${data.business_permit_path}')">
            </div>
            <div style="text-align: center;">
              <strong style="display: block; margin-bottom: 8px; font-size: 12px;">Valid ID</strong>
              <img src="${data.valid_id_path}" alt="Valid ID" style="max-width: 100%; max-height: 120px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" onclick="openImagePreview('${data.valid_id_path}')">
            </div>
            <div style="text-align: center;">
              <strong style="display: block; margin-bottom: 8px; font-size: 12px;">Signature</strong>
              <img src="${data.signature_path}" alt="Signature" style="max-width: 100%; max-height: 120px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" onclick="openImagePreview('${data.signature_path}')">
            </div>
          </div>
        </div>
      `;
      
      document.getElementById('applicationDetails').innerHTML = detailsHtml;
      
      // Show action buttons only if status is pending
      if (data.status === 'pending') {
        document.getElementById('applicationActions').style.display = 'block';
      } else {
        document.getElementById('applicationActions').style.display = 'none';
      }
      
      document.getElementById('applicationModal').style.display = 'block';
    })
    .catch(error => {
      alert('Error loading application details: ' + error.message);
    });
}

function closeApplicationModal() {
  document.getElementById('applicationModal').style.display = 'none';
  currentApplicationId = null;
}

function approveApplication() {
  if (!currentApplicationId) return;
  
  processApplication('approve');
}

function rejectApplication() {
  if (!currentApplicationId) return;
  
  processApplication('reject');
}

function processApplication(action) {
  const formData = new FormData();
  formData.append('application_id', currentApplicationId);
  formData.append('action', action);
  
  fetch('/rentflow/api/approve_application.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.success);
      closeApplicationModal();
      // Reload the page to update notifications
      window.location.reload();
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('An error occurred: ' + error.message);
  });
}

function openImagePreview(imagePath) {
  document.getElementById('previewImage').src = imagePath;
  document.getElementById('imagePreviewModal').style.display = 'block';
}

function closeImagePreview() {
  document.getElementById('imagePreviewModal').style.display = 'none';
}

// Close image preview when clicking outside of image
document.addEventListener('click', function(event) {
  const imagePreviewModal = document.getElementById('imagePreviewModal');
  if (event.target === imagePreviewModal) {
    closeImagePreview();
  }
});
</script>

</body>
</html>
