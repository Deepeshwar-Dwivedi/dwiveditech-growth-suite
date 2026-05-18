<?php
/**
 * Helper Functions - Dwivedi Tech Growth Suite
 * Utility functions for authentication, database, and business logic
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/../config.php';

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}

/**
 * Get current user ID from session
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get user data from database
 */
function getUserData($userId) {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit();
}

// ============================================
// SANITIZATION & VALIDATION
// ============================================

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (basic)
 */
function validatePhone($phone) {
    return preg_match('/^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/', $phone);
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// ============================================
// DATA FORMATTING
// ============================================

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        return '+1-' . substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    return $phone;
}

// ============================================
// ANALYTICS & STATISTICS
// ============================================

/**
 * Get total leads count
 */
function getTotalLeads($userId) {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM extracted_leads WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'] ?? 0;
}

/**
 * Get average rating
 */
function getAverageRating($userId) {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT AVG(rating_given) as avg FROM review_campaigns WHERE user_id = ? AND rating_given IS NOT NULL');
    $stmt->execute([$userId]);
    $result = $stmt->fetch()['avg'] ?? 0;
    return round($result, 1);
}

/**
 * Get total revenue (paid invoices)
 */
function getTotalRevenue($userId) {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT SUM(amount) as total FROM invoices WHERE user_id = ? AND status = ?');
    $stmt->execute([$userId, 'paid']);
    return $stmt->fetch()['total'] ?? 0;
}

/**
 * Get pending invoices count
 */
function getPendingInvoices($userId) {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND status IN (?, ?)');
    $stmt->execute([$userId, 'unpaid', 'overdue']);
    return $stmt->fetch()['count'] ?? 0;
}

// ============================================
// TOKEN & SECURITY
// ============================================

/**
 * Generate secure token
 */
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// EMAIL FUNCTIONS
// ============================================

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    // Using PHPMailer (should be installed via Composer)
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML($isHtml);
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        logError('Email Error: ' . $e->getMessage());
        return false;
    }
}

// ============================================
// LOGGING
// ============================================

/**
 * Log error to file
 */
function logError($message, $context = []) {
    $log = date('Y-m-d H:i:s') . ' | ' . $message;
    if (!empty($context)) {
        $log .= ' | ' . json_encode($context);
    }
    $log .= PHP_EOL;
    
    file_put_contents(LOG_DIR . '/errors.log', $log, FILE_APPEND);
}

/**
 * Log API request
 */
function logAPIRequest($endpoint, $method, $requestData, $responseData, $statusCode) {
    $pdo = getPDOConnection();
    $userId = getCurrentUserId();
    
    $stmt = $pdo->prepare("
        INSERT INTO api_logs (user_id, endpoint, method, request_data, response_data, status_code, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $endpoint,
        $method,
        json_encode($requestData),
        json_encode($responseData),
        $statusCode,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

// ============================================
// JSON RESPONSES
// ============================================

/**
 * Send JSON success response
 */
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Send JSON error response
 */
function jsonError($message, $statusCode = 400) {
    return jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Send JSON success response
 */
function jsonSuccess($message, $data = [], $statusCode = 200) {
    return jsonResponse(array_merge(['success' => true, 'message' => $message], $data), $statusCode);
}

?>