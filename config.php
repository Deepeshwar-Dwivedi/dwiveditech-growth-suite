<?php
/**
 * Central Configuration File
 * Dwivedi Tech Growth Suite
 * All database, API keys, and environment variables
 * Made with ❤️ by Dwivedi Tech
 */

// ============================================
// ENVIRONMENT SETTINGS
// ============================================
define('APP_ENV', 'production'); // 'development' or 'production'
define('APP_URL', 'https://dwiveditech.com'); // Change to your domain
define('APP_NAME', 'Dwivedi Tech Growth Suite');

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'dwiveditech_saas_db');
define('DB_PORT', 3306);

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_HASH_COST', 12); // Bcrypt cost
define('SECURE_COOKIE', false); // Set to true in production with HTTPS
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here');

// ============================================
// STRIPE CONFIGURATION
// ============================================
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET');

// ============================================
// EMAIL CONFIGURATION (Gmail/SMTP)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password'); // Use App Password, not regular password
define('SMTP_FROM_NAME', 'Dwivedi Tech');
define('SMTP_FROM_EMAIL', 'noreply@dwiveditech.com');

// ============================================
// GOOGLE PLACES API
// ============================================
define('GOOGLE_PLACES_API_KEY', 'your-google-places-api-key');
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');

// ============================================
// API INTEGRATIONS
// ============================================
define('OUTSCRAPER_API_KEY', 'your-outscraper-api-key'); // For lead scraping
define('OPENAI_API_KEY', 'your-openai-api-key'); // For AI features

// ============================================
// ERROR HANDLING & LOGGING
// ============================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

define('LOG_DIR', __DIR__ . '/logs');
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// ============================================
// DATABASE CONNECTION (PDO)
// ============================================
function getPDOConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

// ============================================
// SESSION CONFIGURATION
// ============================================
session_start();
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'secure' => SECURE_COOKIE,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// ============================================
// SECURITY HEADERS
// ============================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');

?>