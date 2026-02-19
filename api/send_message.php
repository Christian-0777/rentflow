<?php
// api/send_message.php
// Send a message to admin or reply from admin to tenant
// Used by both admin and tenant roles

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$senderId = $_SESSION['user']['id'] ?? 0;
$senderRole = $_SESSION['user']['role'] ?? '';
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$senderEmail = trim($_POST['sender_email'] ?? '');
$fromAdmin = (int)($_POST['from_admin'] ?? 0);
$fromTenant = (int)($_POST['from_tenant'] ?? 0);

// Validation
if (!$senderId || !$receiverId || !$message) {
  echo json_encode(['error' => 'Missing required fields']);
  exit;
}

if ($receiverId === $senderId) {
  echo json_encode(['error' => 'Cannot send message to yourself']);
  exit;
}

try {
  // Insert message into messages table
  $stmt = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, sender_email, created_at)
    VALUES (?, ?, ?, ?, NOW())
  ");
  
  $stmt->execute([$senderId, $receiverId, $message, $senderEmail ?: null]);
  $messageId = $pdo->lastInsertId();

  // Get receiver's name and email
  $receiverStmt = $pdo->prepare("
    SELECT email, first_name, last_name, notify_email_on_messages, role
    FROM users
    WHERE id = ?
  ");
  $receiverStmt->execute([$receiverId]);
  $receiver = $receiverStmt->fetch();

  // Get sender's name
  $senderStmt = $pdo->prepare("
    SELECT first_name, last_name, email
    FROM users
    WHERE id = ?
  ");
  $senderStmt->execute([$senderId]);
  $sender = $senderStmt->fetch();

  $receiverName = $receiver['first_name'] . ' ' . $receiver['last_name'];
  $senderName = $sender['first_name'] . ' ' . $sender['last_name'];

  // Create notification entry
  $notifTitle = $fromAdmin ? 'Reply from Admin' : 'New Message';
  $notifMessage = $message;

  $notifStmt = $pdo->prepare("
    INSERT INTO notifications (sender_id, receiver_id, type, title, message, message_id, created_at)
    VALUES (?, ?, 'chat', ?, ?, ?, NOW())
  ");
  $notifStmt->execute([$senderId, $receiverId, $notifTitle, $notifMessage, $messageId]);

  // Send email notification if tenant provided email or if setting is enabled
  $shouldSendEmail = false;
  
  if ($fromTenant && $senderEmail) {
    // Tenant provided email - send notification to admin
    // (Admin will see it in their messages interface)
    $shouldSendEmail = false; // Admin already sees in UI
  } elseif ($fromAdmin && $receiver['notify_email_on_messages']) {
    // Admin sending reply - send to tenant if they have notifications enabled
    $shouldSendEmail = true;
  }

  if ($shouldSendEmail && $receiver['email']) {
    try {
      $subject = 'Message from RentFlow Support';
      $htmlBody = "
        <h2>New Message from Admin</h2>
        <p>Hello {$receiverName},</p>
        <p>You have received a new message:</p>
        <hr>
        <p><strong>" . htmlspecialchars($message) . "</strong></p>
        <hr>
        <p>Reply to this message by logging into your RentFlow account and visiting your notifications.</p>
        <p>Best regards,<br>RentFlow Support Team</p>
      ";

      send_mail($receiver['email'], $subject, $htmlBody);
    } catch (Exception $e) {
      // Log email error but don't fail the message send
      error_log('Email send failed: ' . $e->getMessage());
    }
  }

  // If tenant included their email, send notification to admin
  if ($fromTenant && $senderEmail) {
    try {
      // Send email to admin about new tenant message
      $adminEmail = $sender['email']; // This is actually admin's email in this case
      // Get actual sender email (tenant)
      $tenantEmail = $senderEmail;
      
      // Find all admins
      $adminStmt = $pdo->prepare("
        SELECT email, first_name, last_name
        FROM users
        WHERE role = 'admin'
        LIMIT 1
      ");
      $adminStmt->execute();
      $admin = $adminStmt->fetch();

      if ($admin && $admin['email']) {
        $subject = 'New Message from Tenant: ' . $senderName;
        $htmlBody = "
          <h2>New Message from Tenant</h2>
          <p>Hello Administrator,</p>
          <p>You have received a new message from tenant <strong>{$senderName}</strong>.</p>
          <p><strong>Tenant Email:</strong> {$tenantEmail}</p>
          <hr>
          <p><strong>Message:</strong></p>
          <p>" . nl2br(htmlspecialchars($message)) . "</p>
          <hr>
          <p>Log into RentFlow to reply to this message.</p>
          <p>Best regards,<br>RentFlow System</p>
        ";

        send_mail($admin['email'], $subject, $htmlBody);
      }
    } catch (Exception $e) {
      error_log('Admin email notification failed: ' . $e->getMessage());
    }
  }

  echo json_encode([
    'success' => true,
    'message_id' => $messageId,
    'message' => 'Message sent successfully'
  ]);

} catch (PDOException $e) {
  error_log('Database error: ' . $e->getMessage());
  echo json_encode(['error' => 'Failed to send message']);
}
