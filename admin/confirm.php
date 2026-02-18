<?php
// admin/confirm.php
// Email confirmation handler for admin registration
require_once __DIR__.'/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to register.php where confirmation happens
header('Location: register.php');
exit;
