<?php
// config/db.php
// PDO connection with error mode and UTF-8

// Load environment variables
require_once __DIR__ . '/env.php';

$DB_HOST = env('DB_HOST', 'localhost');
$DB_NAME = env('DB_NAME', 'rentflow');
$DB_USER = env('DB_USER', 'root');
$DB_PASS = env('DB_PASS', '');

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (PDOException $e) {
  die('Database connection failed.');
}
