<?php
// admin/tenant_profile.php
// Cover photo, name, status, message button, stall info, lease duration, payment summary

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Allow admin and treasury
require_role(['admin', 'treasury']);

$tenantId = (int)($_GET['id'] ?? 0);
$info = $pdo->prepare("
  SELECT u.*, s.stall_no, s.location, l.lease_start, l.lease_end, l.monthly_rent, l.id AS lease_id
  FROM users u
  LEFT JOIN leases l ON l.tenant_id=u.id
  LEFT JOIN stalls s ON l.stall_id=s.id
  WHERE u.id=?
");
$info->execute([$tenantId]);
$user = $info->fetch();

$nextDue = $pdo->prepare("SELECT * FROM dues WHERE lease_id=? AND paid=0 ORDER BY due_date ASC LIMIT 1");
$nextDue->execute([$user['lease_id']]);
$next = $nextDue->fetch();

$totalPaid = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE lease_id=?");
$totalPaid->execute([$user['lease_id']]);
$tp = $totalPaid->fetch();

$lastPay = $pdo->prepare("SELECT amount, payment_date FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 1");
$lastPay->execute([$user['lease_id']]);
$lp = $lastPay->fetch();

$ar = $pdo->prepare("SELECT total_arrears FROM arrears WHERE lease_id=?");
$ar->execute([$user['lease_id']]);
$arrears = $ar->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenant Profile - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/base.css">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <!-- 🔹 Integrated Header -->
  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="tenants.php">Tenants</a></li>
      <li><a href="payments.php" class="active">Payments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i></a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i></a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <div class="profile-cover" style="background-image:url('<?= htmlspecialchars($user['cover_photo'] ?? '/public/assets/img/placeholders/cover.jpg') ?>')">
    <div class="profile-info">
      <img class="avatar" src="<?= htmlspecialchars($user['profile_photo'] ?? '/public/assets/img/placeholders/avatar.png') ?>">
      <div>
        <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['tenant_id']) ?>)</h1>
        <p>Status: <span class="badge"><?= htmlspecialchars($user['status']) ?></span></p>
        <button class="btn small" onclick="openMessageModal(<?= $tenantId ?>)">Message</button>
        <button class="btn small danger" onclick="openDeleteModal(<?= $tenantId ?>)">Delete Account</button>
      </div>
    </div>
  </div>

  <section class="grid">
    <div class="card">
      <h3>Stall & Lease</h3>
      <p><strong>Stall:</strong> <?= htmlspecialchars($user['stall_no'] ?? '—') ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($user['location'] ?? '—') ?></p>
      <p><strong>Business:</strong> <?= htmlspecialchars($user['business_name'] ?? '—') ?></p>
      <p><strong>Lease:</strong> <?= htmlspecialchars($user['lease_start']) ?> to <?= htmlspecialchars($user['lease_end'] ?? 'present') ?></p>
      <p><strong>Monthly rent:</strong> ₱<?= number_format($user['monthly_rent'] ?? 0,2) ?></p>
    </div>

    <div class="card">
      <h3>Payment Summary</h3>
      <p><strong>Next payment:</strong> ₱<?= number_format($next['amount_due'] ?? 0,2) ?> on <?= htmlspecialchars($next['due_date'] ?? '—') ?></p>
      <p><strong>Total paid:</strong> ₱<?= number_format($tp['total'] ?? 0,2) ?></p>
      <p><strong>Last payment:</strong> ₱<?= number_format($lp['amount'] ?? 0,2) ?> on <?= htmlspecialchars($lp['payment_date'] ?? '—') ?></p>
      <p><strong>Arrears left:</strong> ₱<?= number_format($arrears ?? 0,2) ?></p>
    </div>
  </section>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- Message Modal -->
<div id="messageModal" class="modal" style="display: none;">
  <div class="modal-content">
    <h2>Send Message to Tenant</h2>
    <form action="/api/chat_send.php" method="post">
      <input type="hidden" id="receiverId" name="receiver_id" value="">
      <textarea name="message" placeholder="Type your message..." required></textarea>
      <button class="btn" type="submit">Send</button>
      <button class="btn" type="button" onclick="closeModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="modal" style="display: none;">
  <div class="modal-content">
    <h2>Delete Tenant Account</h2>
    <p>Are you sure you want to delete this tenant's account? This action cannot be undone. The tenant will be deactivated and their stall will become available.</p>
    <form id="deleteForm" action="/rentflow/api/delete_tenant.php" method="post">
      <input type="hidden" id="deleteTenantId" name="tenant_id" value="">
      <button class="btn danger" type="submit">Yes, Delete Account</button>
      <button class="btn" type="button" onclick="closeDeleteModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openMessageModal(tenantId) {
  document.getElementById('receiverId').value = tenantId;
  document.getElementById('messageModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('messageModal').style.display = 'none';
}

function openDeleteModal(tenantId) {
  document.getElementById('deleteTenantId').value = tenantId;
  document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}

// Handle delete form submission
document.getElementById('deleteForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  
  fetch('/rentflow/api/delete_tenant.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Tenant account deleted successfully');
      window.location.reload();
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('An error occurred: ' + error.message);
  });
});
</script>

<script src="/rentflow/public/assets/js/table.js"></script>
</body>
</html>
