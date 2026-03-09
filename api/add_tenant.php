<?php
// api/add_tenant.php
// Add a new tenant with account

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name']);
$business_name = trim($_POST['business_name']);
$email = trim($_POST['email']);
$stall_id = (int)$_POST['stall_id'];
$lease_start = $_POST['lease_start'];
$lease_end = $_POST['lease_end'];
$monthly_rent = (float)$_POST['monthly_rent'];

if (empty($name) || empty($business_name) || empty($email) || !$stall_id || empty($lease_start) || empty($lease_end) || $monthly_rent <= 0) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM tenant_accounts WHERE email=?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Email already exists']);
    exit;
}

// Check if stall is available
$stmt = $pdo->prepare("SELECT id FROM stalls WHERE id=? AND status='available'");
$stmt->execute([$stall_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Stall not available']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Generate 7-digit code
    $code = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    $code_hash = password_hash($code, PASSWORD_DEFAULT);

    // Insert into tenant_accounts
    $stmt = $pdo->prepare("INSERT INTO tenant_accounts (email, code_hash) VALUES (?, ?)");
    $stmt->execute([$email, $code_hash]);
    $account_id = $pdo->lastInsertId();

    // Split name
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0];
    $last_name = $name_parts[1] ?? '';

    // Generate tenant_id
    $tenant_id = 'T' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

    // Insert into users
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, role, email, first_name, last_name, business_name, status, confirmed, password_hash) VALUES (?, 'tenant', ?, ?, ?, ?, 'active', 1, NULL)");
    $stmt->execute([$tenant_id, $email, $first_name, $last_name, $business_name]);

    $user_id = $pdo->lastInsertId();

    // Insert lease
    $stmt = $pdo->prepare("INSERT INTO leases (tenant_id, stall_id, lease_start, lease_end, monthly_rent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $stall_id, $lease_start, $lease_end, $monthly_rent]);
    $lease_id = $pdo->lastInsertId();

    // Create first due based on lease start date and monthly rent
    $stmt = $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?, ?, ?, 0)");
    $stmt->execute([$lease_id, $lease_start, $monthly_rent]);

    // Update stall status
    $stmt = $pdo->prepare("UPDATE stalls SET status='occupied' WHERE id=?");
    $stmt->execute([$stall_id]);

    $pdo->commit();

    // Send email with code and details
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: #0B3C5D; color: white; padding: 10px; border-radius: 5px; text-align: center; }
            .content { padding: 20px 0; }
            .code-box { background-color: #f8f9fa; border: 2px solid #0B3C5D; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; font-family: monospace; }
            .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>RentFlow - Welcome!</h2>
            </div>
            <div class='content'>
                <p>Hello $first_name,</p>
                <p>Your tenant account has been created. Here are your login details:</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Login Code:</strong></p>
                <div class='code-box'>$code</div>
                <p><strong>Lease Details:</strong></p>
                <p>Monthly Rent: ₱" . number_format($monthly_rent, 2) . "</p>
                <p>Lease Start: $lease_start</p>
                <p>Lease End: $lease_end</p>
                <p>Please keep this code secure. You will need it to log in to your account.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " RentFlow. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    send_mail($email, 'RentFlow - Account Created', $body);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>