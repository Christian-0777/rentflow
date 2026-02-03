<?php
// tenant/support.php
// Tenant support chat interface (messages go to admin and treasury)

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
  // Send to treasury (optional)
  $treasuryId = $pdo->query("SELECT id FROM users WHERE role='treasury' LIMIT 1")->fetchColumn();
  if ($treasuryId) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'chat', 'Support', ?)")
        ->execute([$tenantId, $treasuryId, $message]);
  }
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
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/bootstrap-custom.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="dashboard.php" title="Dashboard"><i class="material-icons">dashboard</i><span>Dashboard</span></a></li>
      <li><a href="payments.php" title="Payments"><i class="material-icons">payment</i><span>Payments</span></a></li>
      <li><a href="stalls.php" title="Stalls"><i class="material-icons">storefront</i><span>Stalls</span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span>Notifications</span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span>Profile</span></a></li>
      <li><a href="account.php" title="Settings"><i class="material-icons">settings</i><span>Settings</span></a></li>
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
</body>
</html>
 
