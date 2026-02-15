<?php
// config/constants.php
// App-wide constants for roles, statuses, penalties

// Load environment variables
require_once __DIR__ . '/env.php';

define('ROLE_TENANT', 'tenant');
define('ROLE_ADMIN', 'admin');
// Treasury role removed from project

define('STALL_TYPES', ['wet','dry','apparel']);
define('LEASE_STATUSES', ['active','inactive','lease_ended']);

define('PENALTY_RATE', floatval(env('PENALTY_RATE', 0.02))); // 2% daily penalty on amount_due
