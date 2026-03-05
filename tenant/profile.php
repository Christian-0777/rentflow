<?php
// tenant/profile.php
// Cover photo, name, status, edit button, stall info, lease duration

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$tenantId = $_SESSION['user']['id'];
$info = $pdo->prepare("
  SELECT u.*, s.stall_no, s.location, l.lease_start, l.lease_end
  FROM users u
  LEFT JOIN leases l ON l.tenant_id=u.id
  LEFT JOIN stalls s ON l.stall_id=s.id
  WHERE u.id=?
");
$info->execute([$tenantId]);
$user = $info->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - RentFlow</title>
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
  <div class="profile-header">
    <div class="profile-cover" style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
    <div class="profile-info">
      <div style="width: 160px; height: 160px; border-radius: 50%; background: var(--light); border: 4px solid var(--white); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
        <i class="material-icons" style="font-size: 80px; color: var(--primary);">account_circle</i>
      </div>
      <div class="profile-info-text">
        <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
        <p>Status: <span class="badge"><?= htmlspecialchars(strtoupper($user['status'])) ?></span></p>
        <a class="btn btn-primary btn-small" href="account.php">
          <i class="material-icons" style="font-size: 18px;">edit</i> Edit Profile
        </a>
      </div>
    </div>
  </div>

  <div class="tenant-grid" style="margin-top: 24px;">
    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">apartment</i>Rental Information</h3>
      <h4>Tenant ID</h4>
      <p><?= htmlspecialchars($user['tenant_id']) ?></p>
      
      <h4>Stall Number</h4>
      <p><?= htmlspecialchars($user['stall_no'] ?? '—') ?></p>
      
      <h4>Location</h4>
      <p><?= htmlspecialchars($user['location'] ?? 'Not assigned') ?></p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">business</i>Business Details</h3>
      <h4>Business Name</h4>
      <p><?= htmlspecialchars($user['business_name'] ?? '—') ?></p>
      
      <h4>Lease Period</h4>
      <p>
        <?= htmlspecialchars($user['lease_start']) ?> 
        <br>to 
        <br><?= htmlspecialchars($user['lease_end'] ?? 'Present') ?>
      </p>
    </div>

    <div class="tenant-card">
      <h3><i class="material-icons" style="vertical-align: middle; margin-right: 8px;">info</i>Account Overview</h3>
      <h4>Contact Email</h4>
      <p><?= htmlspecialchars($user['email'] ?? '—') ?></p>
      
      <h4>Member Since</h4>
      <p><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))) ?></p>
      
      <a class="btn btn-secondary btn-small" href="account.php">
        <i class="material-icons" style="font-size: 18px;">settings</i> Account Settings
      </a>
      <a class="btn btn-secondary btn-small" href="logout.php">
        <i class="material-icons" style="font-size: 18px;">logout</i>Log Out
      </a>
    </div>
  </div>

  <!-- Chat with Admin Button -->
  <div class="tenant-card" style="margin-top: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">
    <i class="material-icons" style="font-size: 48px; display: block; margin-bottom: 12px;">chat</i>
    <h3 style="margin-bottom: 12px; color: white;">Need Help?</h3>
    <p style="margin-bottom: 16px; color: rgba(255,255,255,0.9);">Send a message to our support team for any questions or concerns.</p>
    <button class="btn" style="background: white; color: #667eea; font-weight: 600; border: none;" onclick="openMessageModal()">
      <i class="material-icons" style="font-size: 18px;">mail</i> Chat with Admin
    </button>
  </div>
</main>

<!-- Chat Modal -->
<div id="messageModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div class="modal-content" style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 style="margin: 0;">Send Message to Admin</h2>
      <button style="background: none; border: none; font-size: 28px; cursor: pointer; color: #999;" onclick="closeMessageModal()">&times;</button>
    </div>

    <form id="messageForm" method="post" action="/rentflow/api/send_message.php">
      <input type="hidden" name="receiver_id" value="">
      <input type="hidden" name="from_tenant" value="1">

      <div style="margin-bottom: 16px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
          Email (Optional)
        </label>
        <input 
          type="email" 
          name="sender_email" 
          placeholder="your.email@example.com"
          style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
        <small style="display: block; margin-top: 4px; color: #666;">If provided, replies will be sent to this email</small>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
          Message *
        </label>
        <textarea 
          name="message" 
          required
          placeholder="Type your message here..."
          style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px; box-sizing: border-box;"></textarea>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button 
          type="button" 
          class="btn" 
          style="background: #f0f0f0; color: #333; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;"
          onclick="closeMessageModal()">
          Cancel
        </button>
        <button 
          type="submit" 
          class="btn" 
          style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
          <i class="material-icons" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">send</i>Send
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Get admin ID
  function getAdminId() {
    const adminId = '<?php 
      $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role='admin' LIMIT 1");
      $adminStmt->execute();
      echo $adminStmt->fetchColumn(); 
    ?>';
    return adminId;
  }

  function openMessageModal() {
    const adminId = getAdminId();
    document.querySelector('#messageForm input[name="receiver_id"]').value = adminId;
    document.getElementById('messageModal').style.display = 'flex';
  }

  function closeMessageModal() {
    document.getElementById('messageModal').style.display = 'none';
  }

  // Close modal on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMessageModal();
    }
  });

  // Handle form submission
  document.getElementById('messageForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const form = e.target;
    
    const formData = new FormData(form);
    
    fetch('/rentflow/api/send_message.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('Message sent successfully! Our support team will reply shortly.');
        closeMessageModal();
        form.reset();
      } else {
        alert('Error: ' + (data.error || 'Failed to send message'));
      }
    })
    .catch(err => {
      alert('Error: ' + err.message);
    });
  });

  // Close modal when clicking outside
  document.getElementById('messageModal')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('messageModal')) {
      closeMessageModal();
    }
  });
</script>
</body>
</html>
 
