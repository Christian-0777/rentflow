<?php
// api/edit_tenant.php
// Edit tenant details

require_once __DIR__.'/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$tenant_id = (int)$_POST['tenant_id'];
$name = trim($_POST['name']);
$business_name = trim($_POST['business_name']);
$email = trim($_POST['email']);
$monthly_rent = (float)$_POST['monthly_rent'];
$lease_start = $_POST['lease_start'];
$lease_end = $_POST['lease_end'];

if (!$tenant_id || empty($name) || empty($business_name) || empty($email) || $monthly_rent <= 0 || empty($lease_start) || empty($lease_end)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Split name
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0];
    $last_name = $name_parts[1] ?? '';

    // Update user
    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, business_name=?, email=? WHERE id=? AND role='tenant'");
    $stmt->execute([$first_name, $last_name, $business_name, $email, $tenant_id]);

    // Update lease
    $stmt = $pdo->prepare("UPDATE leases SET monthly_rent=?, lease_start=?, lease_end=? WHERE tenant_id=?");
    $stmt->execute([$monthly_rent, $lease_start, $lease_end, $tenant_id]);

    // Update tenant_accounts email if changed
    $stmt = $pdo->prepare("UPDATE tenant_accounts SET email=? WHERE email=(SELECT email FROM users WHERE id=?)");
    $stmt->execute([$email, $tenant_id]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>