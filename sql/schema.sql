-- Dwivedi Tech Growth Suite - Complete MySQL Database Schema
-- Version: 1.0
-- Created: 2026-05-17

-- Create database
CREATE DATABASE IF NOT EXISTS dwiveditech_saas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dwiveditech_saas_db;

-- ============================================
-- 1. USERS TABLE (Clients)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(150),
    subscription_status ENUM('trial', 'active', 'inactive') DEFAULT 'trial',
    stripe_customer_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_email (email),
    INDEX idx_subscription (subscription_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. EXTRACTED LEADS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS extracted_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    website VARCHAR(255),
    location VARCHAR(100),
    platform_source ENUM('google_maps', 'instagram', 'linkedin') DEFAULT 'google_maps',
    outreach_status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (outreach_status),
    INDEX idx_platform (platform_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. REVIEW CAMPAIGNS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS review_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    campaign_name VARCHAR(100),
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(50),
    customer_contact VARCHAR(100),
    rating_given INT,
    feedback_text TEXT,
    status ENUM('sent', 'opened', 'submitted') DEFAULT 'sent',
    unique_token VARCHAR(64) UNIQUE,
    google_review_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    response_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (unique_token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. INVOICES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100),
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
    due_date DATE,
    description TEXT,
    stripe_payment_id VARCHAR(100),
    stripe_invoice_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. EMAIL LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lead_id INT,
    recipient_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES extracted_leads(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. API LOGS TABLE (For debugging)
-- ============================================
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    request_data JSON,
    response_data JSON,
    status_code INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEMO DATA
-- ============================================

-- Demo User (Password: Demo@12345)
INSERT INTO users (name, email, password, company_name, subscription_status) 
VALUES (
    'Demo User',
    'demo@dwiveditech.com',
    '$2y$12$abc123hashedpasswordhere', -- This should be bcrypt hashed password
    'Demo Company',
    'trial'
);

-- Demo Leads
INSERT INTO extracted_leads (user_id, business_name, email, phone, website, location, platform_source, outreach_status)
VALUES
(1, 'Tech Solutions Inc', 'contact@techsolutions.com', '+1-555-0101', 'https://techsolutions.com', 'New York, NY', 'google_maps', 'pending'),
(1, 'Digital Marketing Pro', 'hello@digitalpro.com', '+1-555-0102', 'https://digitalpro.com', 'Los Angeles, CA', 'instagram', 'pending'),
(1, 'Cloud Services Ltd', 'info@cloudservices.com', '+1-555-0103', 'https://cloudservices.com', 'San Francisco, CA', 'linkedin', 'sent');

-- Demo Invoice
INSERT INTO invoices (user_id, invoice_number, client_name, client_email, amount, status, due_date)
VALUES
(1, 'INV-001', 'John Smith', 'john@example.com', 1500.00, 'unpaid', '2026-06-17'),
(1, 'INV-002', 'Jane Doe', 'jane@example.com', 2500.00, 'paid', '2026-05-10');

-- ============================================
-- STORED PROCEDURES (Optional)
-- ============================================

DELIMITER $$

-- Get user statistics
CREATE PROCEDURE IF NOT EXISTS GetUserStats(IN p_user_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM extracted_leads WHERE user_id = p_user_id) as total_leads,
        (SELECT AVG(rating_given) FROM review_campaigns WHERE user_id = p_user_id AND rating_given IS NOT NULL) as avg_rating,
        (SELECT SUM(amount) FROM invoices WHERE user_id = p_user_id AND status = 'paid') as total_revenue,
        (SELECT COUNT(*) FROM invoices WHERE user_id = p_user_id AND status = 'unpaid') as pending_invoices;
END$$

-- Get lead status summary
CREATE PROCEDURE IF NOT EXISTS GetLeadSummary(IN p_user_id INT)
BEGIN
    SELECT 
        outreach_status,
        COUNT(*) as count
    FROM extracted_leads
    WHERE user_id = p_user_id
    GROUP BY outreach_status;
END$$

DELIMITER ;
