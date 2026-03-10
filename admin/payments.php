<?php
// admin/payments.php
// Payments and Arrears Management

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// detect AJAX calls before any output (matches logic in auth.php functions)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ✅ Admin access only (treasury role removed)
require_role('admin');

// ============================================================
// HANDLE PAYMENT ACTIONS (Mark as Paid, Partial, Not Paid)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_receipt_data') {
        $lease_id = (int)$_POST['lease_id'];
        
        // Get lease and tenant details
        $lease_query = $pdo->prepare("
            SELECT l.*, u.first_name, u.last_name, u.business_name, s.stall_no, 
                   a.total_arrears
            FROM leases l
            JOIN users u ON l.tenant_id = u.id
            JOIN stalls s ON l.stall_id = s.id
            LEFT JOIN arrears a ON a.lease_id = l.id
            WHERE l.id = ?
        ");
        $lease_query->execute([$lease_id]);
        $lease_data = $lease_query->fetch();
        
        if (!$lease_data) {
            echo json_encode(['success' => false, 'error' => 'Lease not found']);
            exit;
        }
        
        // Get current unpaid due
        $current_due = $pdo->prepare("
            SELECT id, amount_due, due_date FROM dues 
            WHERE lease_id = ? AND paid = 0 
            ORDER BY due_date ASC LIMIT 1
        ");
        $current_due->execute([$lease_id]);
        $due_record = $current_due->fetch();
        
        if (!$due_record) {
            echo json_encode(['success' => false, 'error' => 'No unpaid due found']);
            exit;
        }
        
        // Get admin name
        $admin_query = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as admin_name FROM users WHERE id = ?");
        $admin_query->execute([$_SESSION['user']['id']]);
        $admin_data = $admin_query->fetch();
        
        echo json_encode([
            'success' => true,
            'lease_id' => $lease_id,
            'due_date' => $due_record['due_date'],
            'amount_due' => $due_record['amount_due'],
            'total_arrears' => $lease_data['total_arrears'] ?? 0,
            'monthly_rent' => $lease_data['monthly_rent'],
            'admin_name' => $admin_data['admin_name'] ?? 'Admin',
            'stall_no' => $lease_data['stall_no'],
            'business_name' => $lease_data['business_name'],
            'tenant_name' => $lease_data['first_name'] . ' ' . $lease_data['last_name']
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    $action_type = $_POST['action_type'];
    $lease_id = (int)$_POST['lease_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get lease and tenant details
        $lease_query = $pdo->prepare("
            SELECT l.*, u.id as tenant_id, u.first_name, u.last_name, u.email, u.business_name, s.stall_no, 
                   a.total_arrears
            FROM leases l
            JOIN users u ON l.tenant_id = u.id
            JOIN stalls s ON l.stall_id = s.id
            LEFT JOIN arrears a ON a.lease_id = l.id
            WHERE l.id = ?
        ");
        $lease_query->execute([$lease_id]);
        $lease_data = $lease_query->fetch();
        
        if (!$lease_data) {
            throw new Exception("Lease not found");
        }
        
        // Get current unpaid due
        $current_due = $pdo->prepare("
            SELECT id, amount_due, due_date FROM dues 
            WHERE lease_id = ? AND paid = 0 
            ORDER BY due_date ASC LIMIT 1
        ");
        $current_due->execute([$lease_id]);
        $due_record = $current_due->fetch();
        
        if (!$due_record) {
            throw new Exception("No unpaid due found for this lease");
        }
        
        // Get admin name
        $admin_query = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as admin_name FROM users WHERE id = ?");
        $admin_query->execute([$_SESSION['user']['id']]);
        $admin_data = $admin_query->fetch();
        $admin_name = $admin_data['admin_name'] ?? 'Admin';
        
        // Generate 12-digit receipt number
        $receipt_no = str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        
        $payment_date = date('Y-m-d');
        $payment_for = date('F Y', strtotime($due_record['due_date'])) . ' rent';
        $total_balance = ($lease_data['total_arrears'] ?? 0);
        
        if ($action_type === 'paid') {
            // Mark current due as paid
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);
            
            // Insert full payment record
            $pdo->prepare("
                INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                VALUES (?, ?, ?, CURDATE(), 'manual', 'Full Payment')
            ")->execute([$lease_id, $due_record['id'], $due_record['amount_due']]);
            
            // Insert receipt
            $pdo->prepare("
                INSERT INTO receipts (receipt_no, lease_id, payment_date, received_from, stall_no, business_name, payment_for, amount_paid, total_balance, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Full Payment')
            ")->execute([
                $receipt_no, $lease_id, $payment_date, $admin_name, $lease_data['stall_no'], 
                $lease_data['business_name'], $payment_for, $due_record['amount_due'], $total_balance
            ]);
            
        } elseif ($action_type === 'partial') {
            $amount_paid = (float)($_POST['amount_paid'] ?? 0);
            
            if ($amount_paid <= 0 || $amount_paid >= $due_record['amount_due']) {
                throw new Exception("Invalid partial payment amount");
            }
            
            // Insert partial payment record
            $pdo->prepare("
                INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                VALUES (?, ?, ?, CURDATE(), 'partial', 'Partial Payment')
            ")->execute([$lease_id, $due_record['id'], $amount_paid]);
            
            // Mark original due as paid (remainder is tracked separately as arrear entry)
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);
            
            // Add remaining to arrears
            $remaining = $due_record['amount_due'] - $amount_paid;
            
            // Insert arrear entry
            $pdo->prepare("
                INSERT INTO arrear_entries (lease_id, due_id, amount, source, created_on) 
                VALUES (?, ?, ?, 'partial_payment', CURDATE())
            ")->execute([$lease_id, $due_record['id'], $remaining]);
            
            // Update arrears record
            $existing_arrears = $pdo->prepare("SELECT id FROM arrears WHERE lease_id = ?");
            $existing_arrears->execute([$lease_id]);
            $arr = $existing_arrears->fetch();
            
            if ($arr) {
                $pdo->prepare("
                    UPDATE arrears 
                    SET total_arrears = total_arrears + ?, last_updated = NOW() 
                    WHERE lease_id = ?
                ")->execute([$remaining, $lease_id]);
            } else {
                $pdo->prepare("
                    INSERT INTO arrears (lease_id, total_arrears, last_updated) 
                    VALUES (?, ?, NOW())
                ")->execute([$lease_id, $remaining]);
            }
            
            // Insert receipt
            $pdo->prepare("
                INSERT INTO receipts (receipt_no, lease_id, payment_date, received_from, stall_no, business_name, payment_for, amount_paid, total_balance, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Partial Payment')
            ")->execute([
                $receipt_no, $lease_id, $payment_date, $admin_name, $lease_data['stall_no'], 
                $lease_data['business_name'], $payment_for, $amount_paid, $total_balance + $remaining
            ]);
            
        } elseif ($action_type === 'notpaid') {
            // Mark the due as processed
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);
            
            // Add amount to arrears
            $pdo->prepare("
                INSERT INTO arrear_entries (lease_id, due_id, amount, source, created_on) 
                VALUES (?, ?, ?, 'marked_not_paid', CURDATE())
            ")->execute([$lease_id, $due_record['id'], $due_record['amount_due']]);
            
            // Insert payment record
            $pdo->prepare("
                INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                VALUES (?, ?, 0, CURDATE(), 'manual', 'Marked as Not Paid')
            ")->execute([$lease_id, $due_record['id']]);
            
            // Update arrears record
            $existing_arrears = $pdo->prepare("SELECT id FROM arrears WHERE lease_id = ?");
            $existing_arrears->execute([$lease_id]);
            $arr = $existing_arrears->fetch();
            
            if ($arr) {
                $pdo->prepare("
                    UPDATE arrears 
                    SET total_arrears = total_arrears + ?, last_updated = NOW() 
                    WHERE lease_id = ?
                ")->execute([$due_record['amount_due'], $lease_id]);
            } else {
                $pdo->prepare("
                    INSERT INTO arrears (lease_id, total_arrears, last_updated) 
                    VALUES (?, ?, NOW())
                ")->execute([$lease_id, $due_record['amount_due']]);
            }
            
            // Insert receipt
            $pdo->prepare("
                INSERT INTO receipts (receipt_no, lease_id, payment_date, received_from, stall_no, business_name, payment_for, amount_paid, total_balance, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Not Paid')
            ")->execute([
                $receipt_no, $lease_id, $payment_date, $admin_name, $lease_data['stall_no'], 
                $lease_data['business_name'], $payment_for, 0, $total_balance + $due_record['amount_due']
            ]);
        }
        
        // Insert next due if provided
        if (isset($_POST['next_due_date']) && isset($_POST['next_amount_due'])) {
            $next_due_date = $_POST['next_due_date'];
            $next_amount_due = (float)$_POST['next_amount_due'];
            if ($next_due_date && $next_amount_due > 0) {
                $pdo->prepare("
                    INSERT INTO dues (lease_id, due_date, amount_due, paid) 
                    VALUES (?, ?, ?, 0)
                ")->execute([$lease_id, $next_due_date, $next_amount_due]);
            }
        }
        
        // Send notification to tenant
        $notification_message = "Payment receipt generated for $payment_for. Receipt No: $receipt_no";
        $pdo->prepare("
            INSERT INTO notifications (sender_id, receiver_id, type, title, message) 
            VALUES (?, ?, 'system', 'Payment Receipt', ?)
        ")->execute([$_SESSION['user']['id'], $lease_data['tenant_id'], $notification_message]);
        
        // Send email to tenant
        $email_subject = "RentFlow - Payment Receipt";
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #0B3C5D; color: white; padding: 10px; border-radius: 5px; text-align: center; }
                .content { padding: 20px 0; }
                .receipt-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RentFlow - Payment Receipt</h2>
                </div>
                <div class='content'>
                    <p>Hello {$lease_data['first_name']} {$lease_data['last_name']},</p>
                    <p>A payment receipt has been generated for your account:</p>
                    <div class='receipt-details'>
                        <p><strong>Receipt No:</strong> $receipt_no</p>
                        <p><strong>Date:</strong> " . date('M d, Y', strtotime($payment_date)) . "</p>
                        <p><strong>Received From:</strong> $admin_name</p>
                        <p><strong>Stall:</strong> {$lease_data['stall_no']}</p>
                        <p><strong>Business Name:</strong> {$lease_data['business_name']}</p>
                        <p><strong>Payment For:</strong> $payment_for</p>
                        <p><strong>Amount Paid:</strong> ₱" . number_format($action_type === 'paid' ? $due_record['amount_due'] : ($action_type === 'partial' ? $_POST['amount_paid'] : 0), 2) . "</p>
                        <p><strong>Status:</strong> " . ($action_type === 'paid' ? 'Full Payment' : ($action_type === 'partial' ? 'Partial Payment' : 'Not Paid')) . "</p>
                    </div>
                    <p>Please keep this receipt for your records.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " RentFlow. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        require_once __DIR__.'/../config/mailer.php';
        send_mail($lease_data['email'], $email_subject, $email_body);
        
        $pdo->commit();
        
        if ($isAjax) {
            echo json_encode(['success' => true, 'receipt_no' => $receipt_no]);
            exit;
        } else {
            header("Location: payments.php?success=1&receipt=$receipt_no");
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = $e->getMessage();
        if ($isAjax) {
            echo json_encode(['error' => $error_msg]);
            exit;
        }
    }
}
        
// generate a one‑time token for the payment modal form to prevent double submissions
$token = bin2hex(random_bytes(16));
$_SESSION['last_payment_form'] = $token;

// ============================================================
// FETCH PAYMENTS DATA
// ============================================================

$payments_rows = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        COALESCE(a.total_arrears, 0) as total_arrears,
        -- total arrears paid by tenant (entries recorded through arrear payment process)
        COALESCE((
            SELECT SUM(amount) FROM payments 
            WHERE lease_id = l.id AND remarks LIKE 'Arrear Payment%'
        ), 0) as total_arrears_paid,
        -- always show the most recent row regardless of amount (including 'Marked as Not Paid')
        COALESCE((
            SELECT payment_date FROM payments 
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), NULL) as last_payment_date,
        COALESCE((
            SELECT 
                CASE 
                    WHEN remarks = 'Marked as Not Paid' AND due_id IS NOT NULL 
                        THEN COALESCE((SELECT amount_due FROM dues WHERE id = p.due_id), 0)
                    ELSE amount
                END
            FROM payments p
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), 0) as last_payment_amount,
        COALESCE((
            SELECT remarks FROM payments 
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), 'No payments yet') as last_payment_status,
        (SELECT due_date FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_due,
        (SELECT amount_due FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_amount,
        CASE
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0 AND due_date<CURDATE())
            THEN 'Overdue'
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0)
            THEN 'Pending'
            ELSE 'Paid'
        END AS next_payment_status
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    LEFT JOIN arrears a ON a.lease_id = l.id
    WHERE u.role = 'tenant'
    ORDER BY s.stall_no ASC
")->fetchAll();

// ============================================================
// FETCH ARREARS DATA
// ============================================================

$arrears_rows = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        COALESCE(a.total_arrears, 0) as total_arrears,
        COALESCE((
            SELECT COALESCE(SUM(amount), 0)
            FROM arrear_entries
            WHERE lease_id = l.id AND source IN ('partial_payment', 'marked_not_paid', 'overdue_7days')
        ), 0) as previous_arrears,
        -- number of days passed since first unpaid arrear entry was created
        COALESCE((
            SELECT DATEDIFF(CURDATE(), MIN(created_on))
            FROM arrear_entries
            WHERE lease_id = l.id AND is_paid = 0
        ), 0) as days_since_arrears
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    LEFT JOIN arrears a ON a.lease_id=l.id
    WHERE u.role = 'tenant' AND COALESCE(a.total_arrears, 0) > 0
    ORDER BY s.stall_no ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments & Arrears - RentFlow</title>
    <link rel="icon" type="image/png" href="public/assets/img/icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table tbody td {
            text-transform: uppercase;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-button.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .btn.small {
            padding: 6px 10px;
            font-size: 12px;
        }
        .btn.small:hover {
            background-color: #0056b3;
        }
        select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .arrear-amount {
            color: #d9534f;
            font-weight: bold;
            cursor: pointer;
            text-decoration: underline;
        }
        .arrear-amount:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body class="admin">
<header class="header">
    <h1 class="site-title">RentFlow</h1>
      <nav class="navigation">
        <ul>
            <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
            <li><a href="tenants.php"><i class="material-icons">people</i>Tenants</a></li>
            <li><a href="payments.php"><i class="material-icons">payments</i>Payments</a></li>
            <li><a href="reports.php"><i class="material-icons">assessment</i>Reports</a></li>
            <li><a href="stalls.php"><i class="material-icons">store</i>Stalls</a></li>
            <li><a href="messages.php" title="Messages"><i class="material-icons">mail</i>Messages</a></li>
            <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
            <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
            <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
        </ul>
    </nav>
</header>

<main class="content">
    <?php if (isset($_GET['success'])): ?>
        <div style="background-color: #dff0d8; color: #3c763d; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            ✓ Payment recorded successfully!<?php if (isset($_GET['receipt'])): ?> Receipt No: <?= htmlspecialchars($_GET['receipt']) ?><?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div style="background-color: #f2dede; color: #a94442; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            ✗ Error: <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- TAB NAVIGATION -->
    <div class="tabs">
        <button class="tab-button active" onclick="switchTab('payments')">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">receipt</i>Payments
        </button>
        <button class="tab-button" onclick="switchTab('arrears')">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">warning</i>Arrears
        </button>
    </div>

    <!-- PAYMENTS TABLE TAB -->
    <div id="payments" class="tab-content active">
        <h2>Payment Records</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Previous Payment</th>
                    <th>Previous Status</th>
                    <th>Total Arrears Paid</th>
                    <th>Next Payment</th>
                    <th>Next Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments_rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['stall_no']) ?></td>
                    <td>
                        <a href="tenant_profile.php?id=<?= $row['tenant_id'] ?>">
                            <?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['tenant_code']) ?>)
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['business_name'] ?? '—') ?></td>
                    <td>
                        <?php
                        if ($row['last_payment_date']) {
                            echo htmlspecialchars(date('M d, Y', strtotime($row['last_payment_date'])));
                            echo '<br><small>₱' . number_format($row['last_payment_amount'], 2) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['last_payment_date']) {
                            echo '<small>' . htmlspecialchars($row['last_payment_status']) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['total_arrears_paid'] > 0) {
                            echo '<small>₱' . number_format($row['total_arrears_paid'],2) . '</small>';
                        } else {
                            echo '<small>—</small>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['next_due']) {
                            echo htmlspecialchars(date('M d, Y', strtotime($row['next_due'])));
                            echo '<br><small>₱' . number_format($row['next_amount'], 2) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['next_due']) {
                            echo '<small>';
                            if (strtotime($row['next_due']) < strtotime(date('Y-m-d'))) {
                                echo '<span style="color: #d9534f; font-weight: bold;">Overdue</span>';
                            } else {
                                echo '<span style="color: #f0ad4e; font-weight: bold;">Pending</span>';
                            }
                            echo '</small>';
                        } else {
                            echo '<small style="color: #5cb85c; font-weight: bold;">Paid</small>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="notifications.php?to=<?= $row['tenant_id'] ?>" class="btn small" style="margin-right: 5px;">Message</a>
                        <?php if ($row['next_due']): ?>
                            <select onchange="openPaymentModal(this.value, <?= $row['lease_id'] ?>)" style="margin-top: 5px;">
                                <option value="">Action</option>
                                <option value="paid">Mark as Paid</option>
                                <option value="partial">Mark as Partial</option>
                                <option value="notpaid">Mark as Not Paid</option>
                            </select>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ARREARS TABLE TAB -->
    <div id="arrears" class="tab-content">
        <h2>Arrears Records</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Previous Arrears</th>
                    <th>Days Since Arrears</th>
                    <th>Total Arrears</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrears_rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['stall_no']) ?></td>
                    <td>
                        <a href="tenant_profile.php?id=<?= $row['tenant_id'] ?>">
                            <?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['tenant_code']) ?>)
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['business_name'] ?? '—') ?></td>
                    <td>
                        <span class="arrear-amount" onclick="showArrearsHistory(<?= $row['lease_id'] ?>, <?= $row['total_arrears'] ?>)">
                            ₱<?= number_format($row['previous_arrears'], 2) ?>
                        </span>
                    </td>
                    <td><?= $row['days_since_arrears'] ? $row['days_since_arrears'].' day(s)' : '—' ?></td>
                    <td>
                        <span class="arrear-amount">₱<?= number_format($row['total_arrears'], 2) ?></span>
                    </td>
                    <td>
                        <a href="notifications.php?to=<?= $row['tenant_id'] ?>" class="btn small" style="margin-right:5px;">Message</a>
                        <button class="btn small" onclick="showArrearsHistory(<?= $row['lease_id'] ?>, <?= $row['total_arrears'] ?>)">Pay</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($arrears_rows)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">No arrears found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- PARTIAL PAYMENT AMOUNT MODAL -->
<div class="modal fade" id="partialAmountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Payment Amount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="partialAmountInput" class="form-label">Amount Paid (₱)</label>
                    <input type="number" class="form-control" id="partialAmountInput" step="0.01" min="0" placeholder="Enter amount paid">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedToReceipt('partial')">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="receipt-details p-4 border rounded">
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Receipt No:</strong></div>
                        <div class="col-sm-6" id="receiptNo"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Date:</strong></div>
                        <div class="col-sm-6" id="receiptDate"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Received From:</strong></div>
                        <div class="col-sm-6" id="receivedFrom"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Stall:</strong></div>
                        <div class="col-sm-6" id="stallNo"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Business Name:</strong></div>
                        <div class="col-sm-6" id="businessName"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Payment For:</strong></div>
                        <div class="col-sm-6" id="paymentFor"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Amount Paid:</strong></div>
                        <div class="col-sm-6" id="amountPaid"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Total Balance:</strong></div>
                        <div class="col-sm-6" id="totalBalance"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Status:</strong></div>
                        <div class="col-sm-6" id="paymentStatus"></div>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="nextDueDate" class="form-label">Next Payment Date (Optional)</label>
                    <input type="date" class="form-control" id="nextDueDateInput" name="next_due_date">
                </div>
                <div class="mt-3">
                    <label for="nextAmountDue" class="form-label">Next Payment Amount (Optional)</label>
                    <input type="number" class="form-control" id="nextAmountDueInput" name="next_amount_due" step="0.01" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitPayment()">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- ARREARS HISTORY MODAL -->
<div id="arrearsHistoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span onclick="closeArrearsHistoryModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3>Arrears History</h3>
        <div id="arrearsHistoryContent" style="margin-top: 20px;">
            <p>Loading...</p>
        </div>
    </div>
</div>

<!-- ARREAR PAYMENT MODAL -->
<div id="arrearPayModal" style="display:none; position: fixed; z-index: 1100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; width:90%; max-width:400px;">
        <span onclick="closeArrearPayModal()" style="color:#aaa; float:right; font-size:28px; cursor:pointer;">&times;</span>
        <h3>Pay Arrear</h3>
        <form id="arrearPayForm">
            <input type="hidden" id="payLeaseId" name="lease_id">
            <input type="hidden" id="payDueDate" name="due_date">
            <div style="margin-bottom:15px;">
                <label for="payAmount">Amount (₱):</label>
                <input type="number" id="payAmount" name="amount_paid" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-top:5px;" readonly>
            </div>
            <div style="margin-bottom:15px;">
                <label for="payPenalty">Penalty (₱):</label>
                <input type="number" id="payPenalty" name="penalty" step="0.01" min="0" value="0" placeholder="Enter penalty amount" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-top:5px;">
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn" onclick="submitArrearPayment()" style="flex:1;">Confirm</button>
                <button type="button" class="btn" onclick="closeArrearPayModal()" style="flex:1; background-color:#6c757d;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script src="/rentflow/public/assets/js/table.js"></script>
<script>
// ============================================================
// TAB SWITCHING
// ============================================================
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

// ============================================================
// PAYMENT MODAL FUNCTIONS
// ============================================================
// intercept submission for AJAX behavior
let currentLeaseId = null;
let currentActionType = null;
let currentAmountPaid = 0;

function openPaymentModal(action, leaseId) {
    currentLeaseId = leaseId;
    currentActionType = action;
    
    if (action === 'partial') {
        // Show partial amount modal first
        const modal = new bootstrap.Modal(document.getElementById('partialAmountModal'));
        modal.show();
    } else {
        // For paid and notpaid, go directly to receipt
        proceedToReceipt(action);
    }
}

function proceedToReceipt(action) {
    if (action === 'partial') {
        currentAmountPaid = parseFloat(document.getElementById('partialAmountInput').value) || 0;
        if (currentAmountPaid <= 0) {
            alert('Please enter a valid amount');
            return;
        }
        // Close partial modal
        bootstrap.Modal.getInstance(document.getElementById('partialAmountModal')).hide();
    }
    
    // Fetch lease data and show receipt modal
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_receipt_data&lease_id=' + currentLeaseId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            populateReceiptModal(data, action);
            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            modal.show();
        } else {
            alert('Error: ' + (data.error || 'unknown'));
        }
    })
    .catch(err => alert('Network error: ' + err.message));
}

function populateReceiptModal(data, action) {
    const receiptNo = ('000000000000' + Math.floor(Math.random() * 1000000000000)).slice(-12);
    const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const paymentFor = new Date(data.due_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) + ' rent';
    
    let amountPaid = 0;
    let status = '';
    let totalBalance = parseFloat(data.total_arrears || 0);
    
    if (action === 'paid') {
        amountPaid = parseFloat(data.amount_due);
        status = 'Full Payment';
    } else if (action === 'partial') {
        amountPaid = currentAmountPaid;
        status = 'Partial Payment';
        totalBalance += (parseFloat(data.amount_due) - currentAmountPaid);
    } else if (action === 'notpaid') {
        amountPaid = 0;
        status = 'Not Paid';
        totalBalance += parseFloat(data.amount_due);
    }
    
    document.getElementById('receiptNo').textContent = receiptNo;
    document.getElementById('receiptDate').textContent = today;
    document.getElementById('receivedFrom').textContent = data.admin_name;
    document.getElementById('stallNo').textContent = data.stall_no;
    document.getElementById('businessName').textContent = data.business_name;
    document.getElementById('paymentFor').textContent = paymentFor;
    document.getElementById('amountPaid').textContent = '₱' + amountPaid.toFixed(2);
    document.getElementById('totalBalance').textContent = '₱' + totalBalance.toFixed(2);
    document.getElementById('paymentStatus').textContent = status;
    
    // Set next month as default due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('nextDueDateInput').value = nextMonth.toISOString().split('T')[0];
    document.getElementById('nextAmountDueInput').value = data.monthly_rent || '';
}

function submitPayment() {
    const nextDueDate = document.getElementById('nextDueDateInput').value;
    const nextAmountDue = document.getElementById('nextAmountDueInput').value;
    
    const formData = new FormData();
    formData.append('form_token', '<?= htmlspecialchars($token) ?>');
    formData.append('action_type', currentActionType);
    formData.append('lease_id', currentLeaseId);
    if (currentActionType === 'partial') {
        formData.append('amount_paid', currentAmountPaid);
    }
    if (nextDueDate) {
        formData.append('next_due_date', nextDueDate);
    }
    if (nextAmountDue) {
        formData.append('next_amount_due', nextAmountDue);
    }
    
    fetch('', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: formData
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'unknown'));
        }
    })
    .catch(err => alert('Network error: ' + err.message));
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: data
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            // reload to show updated records
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'unknown'));
        }
    })
    .catch(err => alert('Network error: ' + err.message));
});

function openPaymentModal_old(action, leaseId) {
    if (!action) return;
    
    const modal = document.getElementById('paymentModal');
    const form = document.getElementById('paymentForm');
    const partialSection = document.getElementById('partialPaidSection');
    const modalTitle = document.getElementById('modalTitle');
    
    // Set values
    document.getElementById('actionType').value = action;
    document.getElementById('leaseIdInput').value = leaseId;
    
    // Set next month as default due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('nextDueDate').value = nextMonth.toISOString().split('T')[0];
    
    // Update modal title and show/hide partial payment section
    if (action === 'paid') {
        modalTitle.textContent = 'Mark as Paid';
        partialSection.style.display = 'none';
        document.getElementById('amountPaid').required = false;
    } else if (action === 'partial') {
        modalTitle.textContent = 'Mark as Partial Paid';
        partialSection.style.display = 'block';
        document.getElementById('amountPaid').required = true;
    } else if (action === 'notpaid') {
        modalTitle.textContent = 'Mark as Not Paid';
        partialSection.style.display = 'none';
        document.getElementById('amountPaid').required = false;
    }
    
    // Reset and show modal
    form.reset();
    document.getElementById('nextDueDate').value = nextMonth.toISOString().split('T')[0];
    modal.style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// ============================================================
// ARREARS HISTORY MODAL FUNCTIONS
// ============================================================
function showArrearsHistory(leaseId, previousArrears) {
    const modal = document.getElementById('arrearsHistoryModal');
    const content = document.getElementById('arrearsHistoryContent');
    
    content.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'block';
    
    fetch('/rentflow/api/arrears_history.php?lease_id=' + leaseId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = '<p style="color: #d9534f;">Error: ' + htmlEscape(data.error) + '</p>';
                return;
            }
            
            let html = '<div style="margin-bottom: 15px;">';
            html += '<p><strong>Previous Arrears:</strong> ₱' + previousArrears.toFixed(2) + '</p>';
            
            if (data.total_penalties > 0) {
                html += '<p><strong>Total Penalties Applied:</strong> ₱' + data.total_penalties.toFixed(2) + '</p>';
            }
            html += '</div>';
            
            if (data.history && data.history.length > 0) {
                html += '<table class="table" style="margin-top: 15px;">';
                html += '<thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead>';
                html += '<tbody>';
                
                data.history.forEach(item => {
                    html += '<tr>';
                    html += '<td>' + htmlEscape(item.date) + '</td>';
                    html += '<td>₱' + item.amount.toFixed(2) + '</td>';
                    html += '<td>' + htmlEscape(item.type) + '</td>';
                    
                    // allow payment for any arrear entry (non-penalty)
                    if (item.type !== 'Penalty Applied') {
                        html += '<td><button class="btn small" onclick="payArrear(' + leaseId + ', \'' + item.date + '\', ' + item.amount + ')">Pay</button></td>';
                    } else {
                        html += '<td>—</td>';
                    }
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                html += '<p>No arrears history found.</p>';
            }
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<p style="color: #d9534f;">Error loading history: ' + htmlEscape(error.message) + '</p>';
        });
}

function closeArrearsHistoryModal() {
    document.getElementById('arrearsHistoryModal').style.display = 'none';
}

// open arrear payment modal with prefilled values
function payArrear(leaseId, dueDate, amount) {
    document.getElementById('payLeaseId').value = leaseId;
    document.getElementById('payDueDate').value = dueDate;
    document.getElementById('payAmount').value = amount.toFixed(2);
    document.getElementById('payPenalty').value = '0.00';
    document.getElementById('arrearPayModal').style.display = 'block';
}

// submit arrear payment request
function submitArrearPayment() {
    const leaseId = document.getElementById('payLeaseId').value;
    const dueDate = document.getElementById('payDueDate').value;
    const amountPaid = document.getElementById('payAmount').value;
    const penalty = document.getElementById('payPenalty').value;

    fetch('/rentflow/api/pay_arrear.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'lease_id=' + leaseId + '&due_date=' + encodeURIComponent(dueDate) +
              '&amount_paid=' + encodeURIComponent(amountPaid) +
              '&penalty=' + encodeURIComponent(penalty)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Arrear payment recorded successfully!');
            closeArrearPayModal();
            showArrearsHistory(leaseId, 0);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error processing payment: ' + error.message);
    });
}

function closeArrearPayModal() {
    document.getElementById('arrearPayModal').style.display = 'none';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function htmlEscape(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const paymentModal = document.getElementById('paymentModal');
    const arrearsModal = document.getElementById('arrearsHistoryModal');
    
    if (event.target === paymentModal) {
        paymentModal.style.display = 'none';
    }
    if (event.target === arrearsModal) {
        arrearsModal.style.display = 'none';
    }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
