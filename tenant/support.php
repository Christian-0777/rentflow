<?php
// tenant/support.php
// Tenant support chat interface (messages go to admin)

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $message = trim($_POST['message']);
  
  // Handle file upload
  if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($_FILES['attachment']['name']);
    $targetPath = __DIR__.'/../uploads/support/' . $fileName;
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
      $attachmentPath = '/rentflow/uploads/support/' . $fileName;
      $message .= "\n\nAttachment: " . $attachmentPath;
    }
  }
  
  // Send to admin
  $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
  if ($adminId) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'chat', 'Support', ?)")
        ->execute([$tenantId, $adminId, $message]);
  }
  // Treasury role removed - messages only sent to admin
  $msg = 'Message sent to support.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Support - RentFlow</title>
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
    <h1>Customer Support</h1>
    <p>Send a message to our support team and we'll help you shortly</p>
  </div>

  <?php if($msg): ?>
    <div class="alert alert-success">
      <i class="material-icons">check_circle</i>
      <div><?= htmlspecialchars($msg) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>

  <div class="tenant-card" style="max-width: 600px; margin-bottom: 24px;">
    <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">mail</i>Send Message</h3>
    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label>Your Message</label>
        <textarea name="message" placeholder="Describe your issue, question, or concern..." rows="6" required></textarea>
      </div>
      
      <div class="form-group">
        <label>Attach File (Optional)</label>
        <small style="display: block; margin-bottom: 8px; color: var(--secondary);">Attach screenshot or image (max 5MB)</small>
        <input type="file" name="attachment" accept="image/*">
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
          <i class="material-icons" style="font-size: 18px;">send</i> Send Message
        </button>
      </div>
    </form>
  </div>

  <div class="tenant-grid">
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">schedule</i>Response Time</h3>
      <p>We typically respond to support requests within 24 hours during business days.</p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">help</i>Common Issues</h3>
      <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
        <li>Payment problems or inquiries</li>
        <li>Stall lease and application issues</li>
        <li>Account and profile updates</li>
        <li>Technical support</li>
      </ul>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">info</i>Support Hours</h3>
      <p>Our support team is available:</p>
      <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
        <li>Monday - Friday: 9:00 AM - 6:00 PM</li>
        <li>Saturday: 10:00 AM - 3:00 PM</li>
        <li>Sunday: Closed</li>
      </ul>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 
