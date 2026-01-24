<?php
// config/db.php
// PDO connection with enhanced security measures

// Load environment variables
require_once __DIR__ . '/env.php';

$DB_HOST = env('DB_HOST', 'localhost');
$DB_PORT = env('DB_PORT', '3306');
$DB_NAME = env('DB_NAME', 'rentflow');
$DB_USER = env('DB_USER', 'rentflow_team');
$DB_PASS = env('DB_PASS', '');
$DB_CHARSET = env('DB_CHARSET', 'utf8mb4');

try {
  // Build DSN with security options
  $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=$DB_CHARSET";
  
  // PDO options with enhanced security
  $options = [
    // Error handling
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Security settings
    PDO::ATTR_EMULATE_PREPARES => false,  // Use native prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_PERSISTENT => false,  // Disable persistent connections for security
    
    // Connection timeout
    PDO::ATTR_TIMEOUT => 5
  ];
  
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
  
  // Set additional security settings
  $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';");
  
} catch (PDOException $e) {
  // Log error securely without exposing database details
  error_log('Database connection failed: ' . $e->getMessage());
  die('Database connection failed. Please try again later.');
}
