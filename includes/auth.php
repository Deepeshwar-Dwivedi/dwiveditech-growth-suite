<?php
/**
 * Authentication Helper Functions
 * Dwivedi Tech Growth Suite
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

/**
 * Register new user
 */
function registerUser($name, $email, $password, $companyName) {
    // Validate inputs
    $name = sanitize($name);
    $email = sanitize($email);
    $companyName = sanitize($companyName);
    
    if (strlen($name) < 2) {
        return ['success' => false, 'message' => 'Name must be at least 2 characters'];
    }
    
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    $pdo = getPDOConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    try {
        $hashedPassword = hashPassword($password);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, company_name, subscription_status)
            VALUES (?, ?, ?, ?, 'trial')
        ");
        
        $stmt->execute([$name, $email, $hashedPassword, $companyName]);
        return ['success' => true, 'message' => 'Registration successful! Please login.'];
    } catch (Exception $e) {
        logError('Registration Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $email = sanitize($email);
    
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Update last login
    $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['company_name'] = $user['company_name'];
    
    return ['success' => true, 'message' => 'Login successful!', 'user_id' => $user['id']];
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $name, $company_name) {
    $name = sanitize($name);
    $company_name = sanitize($company_name);
    
    if (strlen($name) < 2) {
        return ['success' => false, 'message' => 'Name must be at least 2 characters'];
    }
    
    $pdo = getPDOConnection();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET name = ?, company_name = ? WHERE id = ?
        ");
        
        $stmt->execute([$name, $company_name, $userId]);
        
        // Update session
        $_SESSION['user_name'] = $name;
        $_SESSION['company_name'] = $company_name;
        
        return ['success' => true, 'message' => 'Profile updated successfully!'];
    } catch (Exception $e) {
        logError('Update Profile Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Update failed. Please try again.'];
    }
}

/**
 * Change password
 */
function changePassword($userId, $oldPassword, $newPassword) {
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters'];
    }
    
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($oldPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Incorrect password'];
    }
    
    try {
        $hashedPassword = hashPassword($newPassword);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully!'];
    } catch (Exception $e) {
        logError('Change Password Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Change password failed. Please try again.'];
    }
}

?>