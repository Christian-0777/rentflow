<?php
// admin/contact.php
// Contact service form for admin to reach support (stored as notification to treasury/admin)

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $subject = trim($_POST['subject']);
  $message = trim($_POST['message']);
  
  // Handle file upload
  $attachmentPath = '';
  if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($_FILES['attachment']['name']);
    $targetPath = __DIR__.'/../uploads/support/' . $fileName;
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
      $attachmentPath = '/rentflow/uploads/support/' . $fileName;
      $message .= "\n\nAttachment: " . $attachmentPath;
    }
  }
  
  // Send to treasury (if exists) or fallback to admin self
  $treasuryId = $pdo->query("SELECT id FROM users WHERE role='treasury' LIMIT 1")->fetchColumn();
  $receiver = $treasuryId ?: $_SESSION['user']['id'];
  $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?,?, 'system', ?, ?)")
      ->execute([$_SESSION['user']['id'], $receiver, $subject, $message]);
  $msg = 'Message sent to service.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Service - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
      <li><a href="tenants.php"><i class="material-icons">people</i>Tenants</a></li>
      <li><a href="payments.php"><i class="material-icons">payments</i>Payments</a></li>
      <li><a href="reports.php"><i class="material-icons">assessment</i>Reports</a></li>
      <li><a href="stalls.php"><i class="material-icons">store</i>Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Contact Service</h1>
  <?php if($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="card" enctype="multipart/form-data">
    <input name="subject" placeholder="Subject" required>
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
