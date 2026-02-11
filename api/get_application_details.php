<?php
// api/get_application_details.php
// Returns stall application details for a given application ID

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// Require admin role
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$appId = $_GET['id'] ?? '';

if (!$appId) {
    http_response_code(400);
    echo json_encode(['error' => 'Application ID required']);
    exit;
}

// Get application details
$stmt = $pdo->prepare("
    SELECT sa.*,
           CONCAT(u.first_name, ' ', u.last_name) AS tenant_name,
           u.email,
           u.business_name,
           u.tenant_id
    FROM stall_applications sa
    JOIN users u ON sa.tenant_id = u.id
    WHERE sa.id = ?
");
$stmt->execute([$appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    http_response_code(404);
    echo json_encode(['error' => 'Application not found']);
    exit;
}

echo json_encode([
    'id' => $application['id'],
    'tenant_name' => $application['tenant_name'],
    'tenant_id' => $application['tenant_id'],
    'email' => $application['email'],
    'business_name' => $application['business_name'],
    'business_description' => $application['business_description'],
    'business_logo_path' => $application['business_logo_path'],
    'type' => $application['type'],
    'business_permit_path' => $application['business_permit_path'],
    'valid_id_path' => $application['valid_id_path'],
    'signature_path' => $application['signature_path'],
    'status' => $application['status'],
    'created_at' => $application['created_at']
]);