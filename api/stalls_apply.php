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

if (!$tenantId || !$type || !$businessName || !$businessDescription) {
    $_SESSION['flash_error'] = 'Missing required application fields.';
    header('Location: /rentflow/tenant/stalls.php');
    exit;
}

// Upload directory
$uploadDir = __DIR__.'/../uploads/applications/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Function to save uploaded file securely
function saveUploadedFile($fileInputName, $uploadDir) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$fileInputName];
    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return '/rentflow/uploads/applications/' . $fileName;
    }
    return null;
}

// Save uploaded files
$businessLogoPath = saveUploadedFile('business_logo', $uploadDir);
$businessPermitPath = saveUploadedFile('business_permit', $uploadDir);
$validIdPath = saveUploadedFile('valid_id', $uploadDir);
$digitalSignaturePath = saveUploadedFile('digital_signature', $uploadDir);

// Validate required files
if (!$businessPermitPath || !$validIdPath || !$digitalSignaturePath) {
    $_SESSION['flash_error'] = 'Failed to upload required documents. Please try again.';
    header('Location: /rentflow/tenant/stalls.php');
    exit;
}

try {
    // Generate formatted application ID (4 digits with leading zeros)
    $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(id AS UNSIGNED)), 0) + 1 AS next_id FROM stall_applications");
    $nextId = $stmt->fetchColumn();
    $formattedAppId = str_pad($nextId, 4, '0', STR_PAD_LEFT);

    // Insert application
    $stmt = $pdo->prepare("
        INSERT INTO stall_applications (tenant_id, type, business_name, business_logo_path, business_description, business_permit_path, valid_id_path, signature_path, status)
        VALUES (?,?,?,?,?,?,?,?,'pending')
    ");
    $stmt->execute([$tenantId, $type, $businessName, $businessLogoPath, $businessDescription, $businessPermitPath, $validIdPath, $digitalSignaturePath]);
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

    // Find an admin user (id + email) to notify
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
            <p><strong>Business Name:</strong> {$businessName}</p>
            <p><strong>Stall Type:</strong> {$type}</p>
            <p>A tenant has submitted a new stall application.</p>
            <p>Please check the admin panel to review and process this application.</p>
        ";
        send_mail($adminEmail, $subject, $body);
    }

    $_SESSION['flash_success'] = 'Your stall application has been submitted and is pending review.';
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'An error occurred while submitting your application. Please try again.';
    error_log('Stall application error: ' . $e->getMessage());
}

header('Location: /rentflow/tenant/stalls.php');
exit;
