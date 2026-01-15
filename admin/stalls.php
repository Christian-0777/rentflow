<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_role('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
  if ($_POST['action']==='add') {
    $type = $_POST['type'];
    $prefix = strtoupper(substr($type, 0, 1)); // W, D, A
    // Find max number for this type
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(stall_no, 2) AS UNSIGNED)) FROM stalls WHERE type=?");
    $stmt->execute([$type]);
    $maxNum = $stmt->fetchColumn() ?? 0;
    $nextNum = $maxNum + 1;
    $stallNo = $prefix . sprintf('%03d', $nextNum);

    $picturePath = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = __DIR__ . '/../uploads/stalls/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }
      $fileName = $stallNo . '_' . time() . '.' . pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
      $filePath = $uploadDir . $fileName;
      if (move_uploaded_file($_FILES['picture']['tmp_name'], $filePath)) {
        $picturePath = '/rentflow/uploads/stalls/' . $fileName;
      }
    }

    $stmt = $pdo->prepare("INSERT INTO stalls (stall_no, type, location, status, picture_path) VALUES (?,?,?,?,?)");
    $stmt->execute([$stallNo, $_POST['type'], $_POST['location'], $_POST['status'], $picturePath]);
    $msg = "Stall {$stallNo} added.";
  }
  if ($_POST['action']==='remove') {
    $stallNo = (int)$_POST['stall_no'];
    $pdo->prepare("DELETE FROM stalls WHERE stall_no=?")->execute([$stallNo]);
    $msg = "Stall #{$stallNo} removed.";
  }
  if ($_POST['action']==='edit') {
    $stallNo = $_POST['stall_no'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $pdo->prepare("UPDATE stalls SET type=?, status=? WHERE stall_no=?")->execute([$type, $status, $stallNo]);
    $msg = "Stall {$stallNo} updated.";
  }
  if ($_POST['action']==='assign') {
    $tenantId = (int)$_POST['tenant_id'];
    $stallNo = $_POST['stall_no'];
    $monthlyRent = (float)$_POST['monthly_rent'];

    // Get stall id
    $stall = $pdo->prepare("SELECT id FROM stalls WHERE stall_no=?");
    $stall->execute([$stallNo]);
    $stallId = $stall->fetchColumn();

    // Create lease
    $l = $pdo->prepare("INSERT INTO leases (tenant_id, stall_id, lease_start, monthly_rent) VALUES (?,?,CURDATE(),?)");
    $l->execute([$tenantId, $stallId, $monthlyRent]);
    $leaseId = $pdo->lastInsertId();

    // First due
    $d = $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?,?,?,0)");
    $d->execute([$leaseId, date('Y-m-d', strtotime('+30 days')), $monthlyRent]);

    // Arrears init
    $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears) VALUES (?,0)")->execute([$leaseId]);

    // Update stall status
    $pdo->prepare("UPDATE stalls SET status='occupied' WHERE id=?")->execute([$stallId]);

    // Notification to tenant
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Stall Assigned', 'A stall has been assigned to you. Please check your portal.')")
        ->execute([$_SESSION['user']['id'], $tenantId]);

    $msg = "Stall {$stallNo} assigned to tenant.";
  }
  // ... release logic unchanged ...
}

// Filters
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$sql = "SELECT * FROM stalls WHERE 1=1";
$params = [];
if ($type) { $sql.=" AND type=?"; $params[]=$type; }
if ($status){ $sql.=" AND status=?"; $params[]=$status; }
$sql.=" ORDER BY CASE WHEN status='occupied' THEN 1 ELSE 0 END, stall_no ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stalls = $stmt->fetchAll();

// Fetch available tenants (tenants without leases)
$availableTenants = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS full_name, email FROM users WHERE role='tenant' AND id NOT IN (SELECT tenant_id FROM leases)")->fetchAll();

// Fetch available stalls
$availableStalls = $pdo->query("SELECT stall_no, type, location FROM stalls WHERE status='available' ORDER BY stall_no")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Stalls - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">
<header class="header">
  <h1 class="site-title">RentFlow</h1>

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
  <h1>Stalls</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if (isset($_GET['edit'])): 
    $editStall = $pdo->prepare("SELECT * FROM stalls WHERE stall_no=?");
    $editStall->execute([$_GET['edit']]);
    $stall = $editStall->fetch();
    if ($stall): ?>
  <section class="card">
    <h3>Edit Stall <?= htmlspecialchars($stall['stall_no']) ?></h3>
    <form method="post">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="stall_no" value="<?= htmlspecialchars($stall['stall_no']) ?>">
      <select name="type" required>
        <option value="wet" <?= $stall['type']=='wet'?'selected':'' ?>>Wet</option>
        <option value="dry" <?= $stall['type']=='dry'?'selected':'' ?>>Dry</option>
        <option value="apparel" <?= $stall['type']=='apparel'?'selected':'' ?>>Apparel</option>
      </select>
      <select name="status">
        <option value="available" <?= $stall['status']=='available'?'selected':'' ?>>Available</option>
        <option value="occupied" <?= $stall['status']=='occupied'?'selected':'' ?>>Occupied</option>
        <option value="maintenance" <?= $stall['status']=='maintenance'?'selected':'' ?>>Maintenance</option>
      </select>
      <button class="btn">Update</button>
      <a href="stalls.php?type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>" class="btn">Cancel</a>
    </form>
  </section>
  <?php endif; endif; ?>

  <section class="grid">
    <div class="card">
      <h3>Add stall</h3>
      <button class="btn" onclick="openAddStallModal()">Add New Stall</button>
    </div>

    <div class="card">
      <h3>Assign Stall to Existing Tenant</h3>
      <form method="post">
        <input type="hidden" name="action" value="assign">
        <select name="tenant_id" required>
          <option value="">Select Tenant</option>
          <?php foreach ($availableTenants as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <select name="stall_no" required>
          <option value="">Select Stall</option>
          <?php foreach ($availableStalls as $s): ?>
            <option value="<?= $s['stall_no'] ?>"><?= htmlspecialchars($s['stall_no']) ?> - <?= htmlspecialchars($s['type']) ?> (<?= htmlspecialchars($s['location']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <input name="monthly_rent" type="number" step="0.01" placeholder="Monthly Rent" required>
        <button class="btn">Assign</button>
      </form>
    </div>

    <!-- release form unchanged -->
  </section>

  <section class="filters">…</section>

  <!-- ✅ Show stalls -->
  <table class="table">
    <thead>
      <tr><th>Stall No</th><th>Type</th><th>Location</th><th>Status</th><th>Picture</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($stalls as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['stall_no']) ?></td>
        <td><?= htmlspecialchars($s['type']) ?></td>
        <td><?= htmlspecialchars($s['location']) ?></td>
        <td><?= htmlspecialchars(strtoupper($s['status'])) ?></td>
        <td>
          <?php if ($s['picture_path']): ?>
            <img src="<?= htmlspecialchars($s['picture_path']) ?>" alt="Stall Picture" style="max-width: 100px; max-height: 100px;">
          <?php else: ?>
            No Picture
          <?php endif; ?>
        </td>
        <td>
          <a href="?edit=<?= urlencode($s['stall_no']) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>" class="btn small">Edit</a>
          <form method="post" class="inline">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="stall_no" value="<?= $s['stall_no'] ?>">
            <button class="btn small" onclick="return confirm('Are you sure you want to remove stall #<?= $s['stall_no'] ?>?')">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<!-- Add Stall Modal -->
<div id="addStallModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span onclick="closeAddStallModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Add New Stall</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      <label for="type">Type:</label>
      <select name="type" id="type" required>
        <option value="wet">Wet</option>
        <option value="dry">Dry</option>
        <option value="apparel">Apparel</option>
      </select><br><br>
      <label for="location">Location:</label>
      <input type="text" name="location" id="location" placeholder="Location" required><br><br>
      <label for="status">Status:</label>
      <select name="status" id="status">
        <option value="available">Available</option>
        <option value="maintenance">Maintenance</option>
      </select><br><br>
      <label for="picture">Picture:</label>
      <input type="file" name="picture" id="picture" accept="image/*"><br><br>
      <button type="submit" class="btn">Add Stall</button>
    </form>
  </div>
</div>

<footer class="footer"><p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p></footer>

<script src="/rentflow/public/assets/js/table.js"></script>
<script>
function openAddStallModal() {
  document.getElementById('addStallModal').style.display = 'block';
}

function closeAddStallModal() {
  document.getElementById('addStallModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target == document.getElementById('addStallModal')) {
    closeAddStallModal();
  }
}
</script>
</body>
</html>
