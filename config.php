<?php
/**
 * Configuration File - Dwivedi Tech Growth Suite
 * Central hub for database credentials, API keys, and settings
 * Made with ❤️ by Dwivedi Tech
 */

// Environment Detection
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Application Settings
define('APP_NAME', 'Dwivedi Tech Growth Suite');
define('APP_VERSION', '1.0.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('APP_DOMAIN', parse_url(APP_URL, PHP_URL_HOST));

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'dwiveditech_saas_db');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_CHARSET', 'utf8mb4');

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'dwivedi_tech_session');
define('SECURE_COOKIE', ENVIRONMENT === 'production');
define('PASSWORD_HASH_COST', 12);
define('TOKEN_LENGTH', 64);

// ============================================
// STRIPE PAYMENT INTEGRATION
// ============================================
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_your_key_here');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your_key_here');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// ============================================
// EMAIL CONFIGURATION (SMTP)
// ============================================
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@dwiveditech.com');
define('SMTP_FROM_NAME', 'Dwivedi Tech Growth Suite');

// ============================================
// API INTEGRATIONS
// ============================================
// Google Places API
define('GOOGLE_PLACES_API_KEY', getenv('GOOGLE_PLACES_API_KEY') ?: 'your_google_places_key');

// Outscraper / Lead Scraper API
define('OUTSCRAPER_API_KEY', getenv('OUTSCRAPER_API_KEY') ?: 'your_outscraper_key');

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('LOG_DIR', __DIR__ . '/logs');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png']);

// ============================================
// PAGINATION & LIMITS
// ============================================
define('ITEMS_PER_PAGE', 20);
define('MAX_API_CALLS_PER_MINUTE', 60);

// ============================================
// TIME & TIMEZONE
// ============================================
define('TIMEZONE', getenv('TIMEZONE') ?: 'America/New_York');
date_default_timezone_set(TIMEZONE);

// ============================================
// DATABASE CONNECTION - PDO
// ============================================
function getPDOConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . htmlspecialchars($e->getMessage()));
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
    
    return $pdo;
}

// ============================================
// SESSION MANAGEMENT
// ============================================
ini_set('session.name', SESSION_NAME);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', SECURE_COOKIE);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session timeout
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// ============================================
// ERROR & EXCEPTION HANDLING
// ============================================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logToFile("[$errno] $errstr in $errfile:$errline", 'ERROR');
    if (DEBUG_MODE) {
        echo "<pre>Error: $errstr in $errfile at line $errline</pre>";
    }
});

set_exception_handler(function($e) {
    logToFile("Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine(), 'ERROR');
    if (DEBUG_MODE) {
        echo "<pre>Exception: " . $e->getMessage() . "</pre>";
    }
});

// ============================================
// SECURITY HEADERS
// ============================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (ENVIRONMENT === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com;');
}

// ============================================
// UTILITY FUNCTION - Logging
// ============================================
function logToFile($message, $level = 'INFO') {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $logFile = LOG_DIR . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// ============================================
// VERIFY INSTALLATION
// ============================================
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

logToFile('Config loaded successfully', 'INFO');
?>
