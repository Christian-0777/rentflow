<?php
// public/index.php
// Tenant-facing landing page with available stalls preview

require_once __DIR__.'/../config/db.php';

$stmt = $pdo->query("SELECT stall_no, type, location, picture_path FROM stalls WHERE status='available' LIMIT 6");
$available = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="description" content="RentFlow - Find and manage your stall at Baliwag Public Market">
  <title>Welcome to RentFlow - Baliwag Public Market</title>
  <link rel="stylesheet" href="/rentflow/public/assets/css/public-landing.css">
</head>
<body>

<header class="header">
  <h1 class="site-title">RentFlow</h1>
</header>

<main class="layout">
  <section class="hero">
    <h1>Find your stall at Baliwag Public Market</h1>
    <p>Transparent rent, timely reminders, and tracking.</p>
    <div class="cta">
      <a class="btn" href="register.php">Register</a>
      <a class="btn outline" href="login.php">Login</a>
    </div>
  </section>

  <section class="cards">
    <h2>Available stalls</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Stall No</th>
          <th>Type</th>
          <th>Location</th>
          <th>Picture</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($available as $row): ?>
          <tr>
            <td data-label="Stall No"><?= htmlspecialchars($row['stall_no']) ?></td>
            <td data-label="Type"><?= htmlspecialchars($row['type']) ?></td>
            <td data-label="Location"><?= htmlspecialchars($row['location']) ?></td>
            <td data-label="Picture">
              <?php if ($row['picture_path']): ?>
                <img src="<?= htmlspecialchars($row['picture_path']) ?>" alt="Stall Picture" onclick="openImageModal('<?= htmlspecialchars($row['picture_path']) ?>', '<?= htmlspecialchars($row['stall_no']) ?>')">
              <?php else: ?>
                No Picture
              <?php endif; ?>
            </td>
            <td data-label="Action">
              <a class="btn small" href="register.php">Apply</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<script>
function openImageModal(imagePath, stallNo) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'block';
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 90%; width: auto; text-align: center;">
      <span onclick="this.parentElement.parentElement.remove()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
      <h3>Stall ${stallNo}</h3>
      <img src="${imagePath}" alt="Stall Picture" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
    </div>
  `;
  document.body.appendChild(modal);
  
  modal.onclick = function(event) {
    if (event.target == modal) {
      modal.remove();
    }
  }
}
</script>

</body>
</html>
