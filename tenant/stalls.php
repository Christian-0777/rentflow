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
  <h1>Available Stalls</h1>

  <?php if ($flash_success): ?>
    <div class="alert success"><?= htmlspecialchars($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert error"><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; ?>

  <div class="grid" role="list">
    <?php foreach ($available as $s): ?>
      <div class="card" role="listitem" aria-label="Stall <?= htmlspecialchars($s['stall_no']) ?>">
        <?php if ($s['picture_path']): ?>
          <img src="<?= htmlspecialchars($s['picture_path']) ?>" alt="Stall Image" style="max-width: 100%; height: auto; margin-bottom: 10px;">
        <?php endif; ?>
        <h3><?= htmlspecialchars($s['stall_no']) ?> <small style="color:#888;font-weight:400;">(<?= htmlspecialchars($s['type']) ?>)</small></h3>
        <p><?= htmlspecialchars($s['location']) ?></p>

        <button class="btn small" type="button" onclick="openApplyModal('<?= htmlspecialchars($s['stall_no']) ?>', '<?= htmlspecialchars($s['type']) ?>')">Apply</button>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- Apply Modal -->
<div id="applyModal" class="modal" style="display: none;">
  <div class="modal-content">
    <h2>Apply for Stall</h2>
    <form id="applyForm" action="/rentflow/public/api/stalls_apply.php" method="post" enctype="multipart/form-data">
      <input type="hidden" id="modalStallNo" name="stall_no" value="">
      <input type="hidden" id="modalType" name="type" value="">

      <label>
        Business Name
        <input type="text" name="business_name" required>
      </label>

      <label>
        Business Description
        <textarea name="business_description" rows="3" placeholder="Describe your business..." required></textarea>
      </label>

      <label>
        Business Permit
        <input type="file" name="permit" accept=".txt,.doc,.pdf" required>
      </label>

      <label>
        Valid ID
        <input type="file" name="valid_id" accept=".png,.jpeg,.webp" required>
      </label>

      <label>
        Signature
        <input type="file" name="signature" accept=".pdf,.doc" required>
      </label>

      <div style="margin-top:8px;">
        <button class="btn" type="submit">Submit Application</button>
        <button class="btn" type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
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
</script>

  
</body>
</html>
