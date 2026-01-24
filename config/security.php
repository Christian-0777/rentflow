<?php
// config/security.php
// Database and application security configuration

require_once __DIR__ . '/env.php';

/**
 * Database Security Best Practices
 */
class DatabaseSecurity {
    
    /**
     * Prepare a statement safely using parameterized queries
     * Always use this method to prevent SQL injection
     */
    public static function prepareQuery($pdo, $query) {
        try {
            return $pdo->prepare($query);
        } catch (PDOException $e) {
            error_log('Query preparation failed: ' . $e->getMessage());
            throw new Exception('Database query error.');
        }
    }
    
    /**
     * Execute query with bound parameters
     * Usage: $result = DatabaseSecurity::executeQuery($pdo, "SELECT * FROM users WHERE id = ?", [$userId]);
     */
    public static function executeQuery($pdo, $query, $params = []) {
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query execution failed: ' . $e->getMessage());
            throw new Exception('Database operation failed.');
        }
    }
    
    /**
     * Fetch a single row with security
     */
    public static function fetchOne($pdo, $query, $params = []) {
        $stmt = self::executeQuery($pdo, $query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch multiple rows with security
     */
    public static function fetchAll($pdo, $query, $params = []) {
        $stmt = self::executeQuery($pdo, $query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validate database connection
     */
    public static function validateConnection($pdo) {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log('Database connection validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hash a password using bcrypt
     * Usage: $hash = DatabaseSecurity::hashPassword($password);
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }
    
    /**
     * Verify a password against its hash
     * Usage: if (DatabaseSecurity::verifyPassword($password, $hash)) { ... }
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Sanitize string input (use parameterized queries instead when possible)
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Security Headers Configuration
 * Call these in your main layout/header files
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Session Security Configuration
 * Call this at the beginning of your application
 */
function configureSessionSecurity() {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 3600,  // 1 hour
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => (env('APP_ENV') === 'production'),  // HTTPS only in production
        'httponly' => true,  // Not accessible via JavaScript
        'samesite' => 'Strict'  // CSRF protection
    ]);
    
    // Regenerate session ID on login
    if (!isset($_SESSION['_session_started'])) {
        session_start();
        $_SESSION['_session_started'] = true;
    }
}

/**
 * SQL Injection Prevention Checklist:
 * ✓ Always use parameterized queries with placeholders (?)
 * ✓ Use PDO prepared statements (already implemented in db.php)
 * ✓ Disable emulated prepared statements (PDO::ATTR_EMULATE_PREPARES => false)
 * ✓ Never concatenate user input into SQL queries
 * ✓ Use DatabaseSecurity::executeQuery() for all database operations
 * 
 * Example of SECURE query:
 *   $stmt = DatabaseSecurity::executeQuery($pdo, 
 *       "SELECT * FROM users WHERE email = ? AND status = ?", 
 *       [$email, 'active']
 *   );
 */
