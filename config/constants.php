<?php
// config/constants.php
// App-wide constants for roles, statuses, penalties

define('ROLE_TENANT', 'tenant');
define('ROLE_ADMIN', 'admin');
define('ROLE_TREASURY', 'treasury');

define('STALL_TYPES', ['wet','dry','apparel']);
define('LEASE_STATUSES', ['active','inactive','lease_ended']);

define('PENALTY_RATE', 0.02); // 2% daily penalty on amount_due
