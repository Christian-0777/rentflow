<?php
// api/update_tenant_docs.php
// Allows admin to replace one or more document files on the tenant's application record

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$tenantId = (int)($_POST['tenant_id'] ?? 0);
if (!$tenantId) {
    http_response_code(400);
    echo json_encode(['error' => 'Tenant ID required']);
    exit;
}

// find latest application for this tenant
$appStmt = $pdo->prepare("SELECT * FROM stall_applications WHERE tenant_id = ? ORDER BY id DESC LIMIT 1");
$appStmt->execute([$tenantId]);
$app = $appStmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    http_response_code(404);
    echo json_encode(['error' => 'Application not found']);
    exit;
}

// upload directory same as stalls_apply
$uploadDir = __DIR__.'/../uploads/applications/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

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

$updates = [];
$params = [];

if ($path = saveUploadedFile('business_logo', $uploadDir)) {
    $updates[] = 'business_logo_path = ?';
    $params[] = $path;
}
if ($path = saveUploadedFile('business_permit', $uploadDir)) {
    $updates[] = 'business_permit_path = ?';
    $params[] = $path;
}
if ($path = saveUploadedFile('valid_id', $uploadDir)) {
    $updates[] = 'valid_id_path = ?';
    $params[] = $path;
}
if ($path = saveUploadedFile('digital_signature', $uploadDir)) {
    $updates[] = 'signature_path = ?';
    $params[] = $path;
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No files were uploaded']);
    exit;
}

try {
    $sql = "UPDATE stall_applications SET " . implode(', ', $updates) . " WHERE id = ?";
    $params[] = $app['id'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => 'Documents updated']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update documents: ' . $e->getMessage()]);
}
