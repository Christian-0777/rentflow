<?php
// tenant/account.php
// Tenant account settings: profile photos only

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('tenant');

$msg = '';
$tenantId = $_SESSION['user']['id'];

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uploadDir = __DIR__ . '/../uploads/tenants/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'profile_' . $tenantId . '_' . time() . '.' . pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filePath)) {
      $pdo->prepare("UPDATE users SET profile_photo=? WHERE id=?")->execute(['/rentflow/uploads/tenants/' . $fileName, $tenantId]);
      $msg = 'Profile photo updated.';
    }
  }

  if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'cover_' . $tenantId . '_' . time() . '.' . pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $filePath)) {
      $pdo->prepare("UPDATE users SET cover_photo=? WHERE id=?")->execute(['/rentflow/uploads/tenants/' . $fileName, $tenantId]);
      $msg = 'Cover photo updated.';
    }
  }
}

// Fetch tenant info
$stmt = $pdo->prepare("SELECT first_name, last_name, profile_photo, cover_photo FROM users WHERE id=?");
$stmt->execute([$tenantId]);
$u = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account - RentFlow</title>
  <link rel="icon" type="image/png" href="assets/img/icon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="/rentflow/public/assets/css/tenant-bootstrap.css"><!-- Pace.js CSS --><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pace-js@latest/pace-theme-default.min.css"><!-- Pace.js JS --><script src="https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js"></script></head>
<body>

<!-- Navigation Bar -->
<nav class="tenant-navbar">
  <div class="tenant-navbar-content">
    <ul class="tenant-navbar-nav">
      <li><a href="home.php" title="Home"><i class="material-icons">home</i><span></span></a></li>
      <li><a href="messages.php" title="Messages"><i class="material-icons">message</i><span></span></a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i><span></span></a></li>
      <li><a href="profile.php" class="active" title="Profile"><i class="material-icons">person</i><span></span></a></li>
    </ul>
  </div>
</nav>

<main class="tenant-content">

  <div class="page-header">
    <h1>Account Settings</h1>
    <p>Manage your profile photos</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success">
      <i class="material-icons">check_circle</i>
      <div><?= htmlspecialchars($msg) ?></div>
      <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-6">
      <div class="tenant-card">
        <h3>Profile Picture</h3>
        <?php if ($u['profile_photo']): ?>
          <img src="<?= htmlspecialchars($u['profile_photo']) ?>" alt="Profile Photo" class="img-fluid mb-3" style="max-height: 200px;">
        <?php else: ?>
          <div class="text-center mb-3" style="height: 200px; display: flex; align-items: center; justify-content: center; background: var(--light); border: 1px dashed var(--border);">
            <i class="material-icons" style="font-size: 48px; color: var(--secondary);">person</i>
          </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <input type="file" name="profile_photo" accept="image/*" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Upload Profile Photo</button>
        </form>
      </div>
    </div>

    <div class="col-md-6">
      <div class="tenant-card">
        <h3>Cover Photo</h3>
        <?php if ($u['cover_photo']): ?>
          <img src="<?= htmlspecialchars($u['cover_photo']) ?>" alt="Cover Photo" class="img-fluid mb-3" style="max-height: 200px; width: 100%; object-fit: cover;">
        <?php else: ?>
          <div class="text-center mb-3" style="height: 200px; display: flex; align-items: center; justify-content: center; background: var(--light); border: 1px dashed var(--border);">
            <i class="material-icons" style="font-size: 48px; color: var(--secondary);">image</i>
          </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <input type="file" name="cover_photo" accept="image/*" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Upload Cover Photo</button>
        </form>
      </div>
    </div>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rentflow/public/assets/js/tenant.js"></script>
</body>
</html>
 

