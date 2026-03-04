<?php
// rentflow/public/api/stalls_apply.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Forward to the main API handler so application is stored and notifications are sent
require_once __DIR__ . '/../../api/stalls_apply.php';
?>
