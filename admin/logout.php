<?php
// rentflow/admin/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Redirect to admin login
header("Location: /rentflow/admin/login.php");
exit;
?>
