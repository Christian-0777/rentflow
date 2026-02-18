<?php
// rentflow/public/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Redirect to login or home
header("Location: /rentflow/public/index.php");
exit;
