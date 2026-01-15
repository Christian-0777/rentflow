<?php
// api/stalls_apply.php
// Saves application files and records application

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';
session_start();
$tenantId = $_SESSION['user']['id'] ?? 0;
$type = $_POST['type'] ?? '';
$businessName = $_POST['business_name'] ?? '';
$businessDescription = $_POST['business_description'] ?? '';

if ($tenantId && $type && $businessName && $businessDescription) {
  $permit = '/public/uploads/'.basename($_FILES['permit']['name']);
  move_uploaded_file($_FILES['permit']['tmp_name'], __DIR__.'/../public/uploads/'.basename($_FILES['permit']['name']));
  $validid = '/public/uploads/'.basename($_FILES['valid_id']['name']);
  move_uploaded_file($_FILES['valid_id']['tmp_name'], __DIR__.'/../public/uploads/'.basename($_FILES['valid_id']['name']));
  $signature = '/public/uploads/'.basename($_FILES['signature']['name']);
  move_uploaded_file($_FILES['signature']['tmp_name'], __DIR__.'/../public/uploads/'.basename($_FILES['signature']['name']));

  // Generate formatted application ID (4 digits with leading zeros)
  $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(id AS UNSIGNED)), 0) + 1 AS next_id FROM stall_applications");
  $nextId = $stmt->fetchColumn();
  $formattedAppId = str_pad($nextId, 4, '0', STR_PAD_LEFT);

  $stmt = $pdo->prepare("
    INSERT INTO stall_applications (tenant_id, type, business_name, business_description, business_permit_path, valid_id_path, signature_path, status)
    VALUES (?,?,?,?,?,?,?,'pending')
  ");
  $stmt->execute([$tenantId, $type, $businessName, $businessDescription, $permit, $validid, $signature]);
  $appId = $pdo->lastInsertId();
  
  // Update the ID to formatted version
  $pdo->prepare("UPDATE stall_applications SET id = ? WHERE id = ?")->execute([$formattedAppId, $appId]);
  $appId = $formattedAppId;

  // Update user's business_name
  $pdo->prepare("UPDATE users SET business_name = ? WHERE id = ?")->execute([$businessName, $tenantId]);

  // Get tenant name
  $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE id=?");
  $stmt->execute([$tenantId]);
  $tenantName = $stmt->fetchColumn();

  // Find an admin user (id + email) to notify; avoid FK errors if none exist
  $admin = $pdo->query("SELECT id, email FROM users WHERE role='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if ($admin && !empty($admin['id'])) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'New stall application', ?)")
        ->execute([$tenantId, $admin['id'], "Tenant {$tenantName} submitted a stall application for type {$type}. Application ID: {$appId}"]);
  }

  // Get admin email if available
  $adminEmail = $admin['email'] ?? null;

  // Send email notification to admin
  if ($adminEmail) {
    $subject = 'New Stall Application Submitted';
    $body = "
      <h2>New Stall Application</h2>
      <p><strong>Tenant:</strong> {$tenantName}</p>
      <p><strong>Stall Type:</strong> {$type}</p>
      <p>A tenant has submitted a new stall application.</p>
      <p>Please check the admin notifications or stalls page for details.</p>
    ";
    send_mail($adminEmail, $subject, $body);
  }
}
// Set a session flash message so tenant sees confirmation after redirect
$_SESSION['flash_success'] = 'Your stall application has been submitted and is pending review.';
header('Location: /rentflow/tenant/dashboard.php');
