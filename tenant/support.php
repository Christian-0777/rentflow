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
      <li><a href="support.php" title="Contact Support"><i  class="material-icons">contact_support</i></a></li>
      <li><a href="/rentflow/public/logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Customer Service</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="card" enctype="multipart/form-data">
    <textarea name="message" placeholder="Describe your issue..." rows="6" required></textarea>
    <input type="file" name="attachment" accept="image/*" placeholder="Upload screenshot or image">
    <button class="btn">Send</button>
  </form>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

</body>
</html>
 
