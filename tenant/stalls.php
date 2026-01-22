<?php
// tenant/stalls.php
// Shows available stalls and allows application

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Require tenant role
require_role('tenant');

// Flash messages (set by API)
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Fetch available stalls
$available = $pdo->query("SELECT stall_no, type, location, picture_path FROM stalls WHERE status='available' ORDER BY stall_no")->fetchAll(PDO::FETCH_ASSOC);

// Fetch rented stalls (tenants with leases)
$rented = $pdo->prepare("
  SELECT s.stall_no, s.type, s.location, s.picture_path, l.monthly_rent, l.lease_start
  FROM leases l
  JOIN stalls s ON l.stall_id = s.id
  WHERE l.tenant_id = ?
  ORDER BY s.stall_no
");
$rented->execute([$_SESSION['user']['id']]);
$rented = $rented->fetchAll(PDO::FETCH_ASSOC);
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
  <h1>Stalls</h1>

  <?php if ($flash_success): ?>
    <div class="alert success"><?= htmlspecialchars($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert error"><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; ?>

  <?php if (!empty($rented)): ?>
  <section style="margin-bottom: 30px;">
    <h2>Your Rented Stalls</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Stall No</th>
          <th>Type</th>
          <th>Location</th>
          <th>Monthly Rent</th>
          <th>Lease Start</th>
          <th>Picture</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rented as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['stall_no']) ?></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td>₱<?= number_format($r['monthly_rent'], 2) ?></td>
            <td><?= date('M d, Y', strtotime($r['lease_start'])) ?></td>
            <td>
              <?php if ($r['picture_path']): ?>
                <img src="<?= htmlspecialchars($r['picture_path']) ?>" alt="Stall Picture" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="openImageModal('<?= htmlspecialchars($r['picture_path']) ?>', '<?= htmlspecialchars($r['stall_no']) ?>')">
              <?php else: ?>
                No Picture
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <section>
    <h2>Available Stalls</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Stall No</th>
          <th>Type</th>
          <th>Location</th>
          <th>Preview</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($available as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['stall_no']) ?></td>
            <td><?= htmlspecialchars($s['type']) ?></td>
            <td><?= htmlspecialchars($s['location']) ?></td>
            <td>
              <?php if ($s['picture_path']): ?>
                <img src="<?= htmlspecialchars($s['picture_path']) ?>" alt="Stall Picture" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" onclick="openImageModal('<?= htmlspecialchars($s['picture_path']) ?>', '<?= htmlspecialchars($s['stall_no']) ?>')">
              <?php else: ?>
                No Picture
              <?php endif; ?>
            </td>
            <td>
              <button class="btn small" type="button" onclick="openApplyModal('<?= htmlspecialchars($s['stall_no']) ?>', '<?= htmlspecialchars($s['type']) ?>')">Apply</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<!-- Apply Modal -->
<div id="applyModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 style="margin: 0;">Apply for Stall</h2>
      <span onclick="closeModal()" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    </div>
    
    <form id="applyForm" action="/rentflow/public/api/stalls_apply.php" method="post" enctype="multipart/form-data">
      <input type="hidden" id="modalStallNo" name="stall_no" value="">
      <input type="hidden" id="modalType" name="type" value="">

      <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Business Name *</label>
        <input type="text" name="business_name" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
      </div>

      <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Business Description *</label>
        <textarea name="business_description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Describe your business..." required></textarea>
      </div>

      <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Business Permit *</label>
        <input type="file" name="permit" accept=".txt,.doc,.pdf" style="display: block;" required>
      </div>

      <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Valid ID *</label>
        <input type="file" name="valid_id" accept=".png,.jpeg,.webp" style="display: block;" required>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Signature *</label>
        <input type="file" name="signature" accept=".pdf,.doc" style="display: block;" required>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button class="btn" type="button" onclick="closeModal()" style="background-color: #f0f0f0; color: #333;">Cancel</button>
        <button class="btn" type="submit" style="background-color: #007bff; color: white;">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 90%; width: auto; text-align: center;">
    <span onclick="closeImageModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3 id="imageModalTitle">Stall Picture</h3>
    <img id="modalImage" src="" alt="Stall Picture" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
  </div>
</div>

<!-- Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
function openApplyModal(stallNo, type) {
  document.getElementById('modalStallNo').value = stallNo;
  document.getElementById('modalType').value = type;
  document.getElementById('applyModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('applyModal').style.display = 'none';
}

function openImageModal(imagePath, stallNo) {
  document.getElementById('modalImage').src = imagePath;
  document.getElementById('imageModalTitle').textContent = 'Stall ' + stallNo;
  document.getElementById('imageModal').style.display = 'block';
}

function closeImageModal() {
  document.getElementById('imageModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
  const applyModal = document.getElementById('applyModal');
  const imageModal = document.getElementById('imageModal');
  
  if (event.target == applyModal) {
    closeModal();
  }
  if (event.target == imageModal) {
    closeImageModal();
  }
}
</script>

  
</body>
</html>
