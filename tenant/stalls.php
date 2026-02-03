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
  <title>Stalls - RentFlow</title>
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
      <li><a href="stalls.php" class="active" title="Stalls"><i class="material-icons">storefront</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">
  <div class="page-header">
    <h1>Stalls</h1>
    <p>Browse available stalls and manage your leases</p>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert alert-success">
      <i class="material-icons">check_circle</i>
      <div><?= htmlspecialchars($flash_success) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>
  
  <?php if ($flash_error): ?>
    <div class="alert alert-danger">
      <i class="material-icons">error</i>
      <div><?= htmlspecialchars($flash_error) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($rented)): ?>
    <h2 style="margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
      <i class="material-icons">check_circle</i>
      Your Rented Stalls
    </h2>
    <div class="table-responsive" style="margin-bottom: 32px;">
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
              <td><strong><?= htmlspecialchars($r['stall_no']) ?></strong></td>
              <td><?= htmlspecialchars($r['type']) ?></td>
              <td><?= htmlspecialchars($r['location']) ?></td>
              <td><strong style="color: var(--primary);">₱<?= number_format($r['monthly_rent'], 2) ?></strong></td>
              <td><?= date('M d, Y', strtotime($r['lease_start'])) ?></td>
              <td>
                <?php if ($r['picture_path']): ?>
                  <img src="<?= htmlspecialchars($r['picture_path']) ?>" alt="Stall Picture" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px;" onclick="openImageModal('<?= htmlspecialchars($r['picture_path']) ?>', 'Stall <?= htmlspecialchars($r['stall_no']) ?>')">
                <?php else: ?>
                  <span style="color: var(--secondary);">No Photo</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 style="margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
    <i class="material-icons">storefront</i>
    Available Stalls
  </h2>
  
  <div class="table-responsive">
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
            <td><strong><?= htmlspecialchars($s['stall_no']) ?></strong></td>
            <td><?= htmlspecialchars($s['type']) ?></td>
            <td><?= htmlspecialchars($s['location']) ?></td>
            <td>
              <?php if ($s['picture_path']): ?>
                <img src="<?= htmlspecialchars($s['picture_path']) ?>" alt="Stall Picture" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px;" onclick="openImageModal('<?= htmlspecialchars($s['picture_path']) ?>', 'Stall <?= htmlspecialchars($s['stall_no']) ?>')">
              <?php else: ?>
                <span style="color: var(--secondary);">No Photo</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-primary btn-small" type="button" onclick="openApplyModal('<?= htmlspecialchars($s['stall_no']) ?>', '<?= htmlspecialchars($s['type']) ?>', 'applyModal')">
                <i class="material-icons" style="font-size: 16px;">add</i> Apply
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Apply Modal -->
<div id="applyModal" class="modal">
  <div class="modal-content">
    <button class="modal-close">&times;</button>
    <h2 style="margin-bottom: 16px;">Apply for Stall</h2>
    
    <form id="applyForm" action="/rentflow/api/stalls_apply.php" method="post" enctype="multipart/form-data">
      <input type="hidden" id="modalStallNo" name="stall_no" value="">
      <input type="hidden" id="modalType" name="type" value="">

      <div class="form-group">
        <label>Business Name *</label>
        <input type="text" name="business_name" required>
      </div>

      <div class="form-group">
        <label>Business Description *</label>
        <textarea name="business_description" placeholder="Describe your business..." required></textarea>
      </div>

      <div class="form-group">
        <label>Business Permit *</label>
        <input type="file" name="permit" accept=".txt,.doc,.pdf" required>
        <small style="color: var(--secondary);">Accepted formats: txt, doc, pdf</small>
      </div>

      <div class="form-group">
        <label>Valid ID *</label>
        <input type="file" name="valid_id" accept=".png,.jpeg,.webp" required>
        <small style="color: var(--secondary);">Accepted formats: png, jpg, webp</small>
      </div>

      <div class="form-group">
        <label>Signature *</label>
        <input type="file" name="signature" accept=".pdf,.doc" required>
        <small style="color: var(--secondary);">Accepted formats: pdf, doc</small>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button class="btn btn-secondary" type="button" onclick="closeModal('applyModal')">Cancel</button>
        <button class="btn btn-primary" type="submit">Submit Application</button>
      </div>
    </form>
  </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageModal" class="modal">
  <div class="modal-content" style="max-width: 90%; width: auto; text-align: center;">
    <button class="modal-close">&times;</button>
    <h3 id="imageModalTitle" style="margin-bottom: 16px;">Stall Picture</h3>
    <img id="modalImage" src="" alt="Stall Picture" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px;">
  </div>
</div>
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
<script src="/rentflow/public/assets/js/stalls-page.js"></script>
</body>
</html>
