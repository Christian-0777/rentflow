<?php
// rentflow/public/logout.php
session_start();
session_unset();
session_destroy();

// Redirect to login or home
header("Location: /rentflow/public/login.php");
exit;
