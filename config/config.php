<?php
/**
 * Uruhushya Software - Main Configuration File
 * 
 * SECURITY WARNING: This file contains sensitive credentials.
 * NEVER commit this file to Git!
 * 
 * Developer: Benjamin NIYOMURINZI
 * Date: 2025-01-07
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'uruhushya');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default, or your MySQL password
define('DB_CHARSET', 'utf8mb4');

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Uruhushya Software');
define('APP_URL', 'http://localhost/uruhushya_app/public');
define('SITE_EMAIL', 'info@uruhushya.rw');
define('SITE_PHONE', '+250789733274');
define('COMPANY_NAME', 'Uruhushya Software Ltd');
define('COMPANY_TIN', '122944192');
define('COMPANY_ADDRESS', 'Remera, Ikaro Plaza, Floor 2, Kigali-Gasabo');

// ============================================
// PATHS
// ============================================
define('BASE_PATH', dirname(__DIR__));  // Project root directory
define('PUBLIC_PATH', BASE_PATH . '/public');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('CERTIFICATE_PATH', PUBLIC_PATH . '/certificates');

// ============================================
// SESSION CONFIGURATION
// ============================================
define('SESSION_LIFETIME', 3600);  // 1 hour in seconds
define('SESSION_NAME', 'uruhushya_session');
define('MAX_DEVICES_PER_USER', 3);  // Maximum concurrent login devices

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('UPLOAD_MAX_SIZE', 5242880);  // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// ============================================
// EMAIL CONFIGURATION
// ============================================
// For development, we'll use PHP mail() function
// For production, configure SMTP below
define('EMAIL_METHOD', 'php_mail');  // Options: 'php_mail' or 'smtp'
define('EMAIL_FROM', 'noreply@uruhushya.rw');
define('EMAIL_FROM_NAME', 'Uruhushya Software');

// SMTP Configuration (uncomment and configure for production)
/*
define('EMAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');  // 'tls' or 'ssl'
*/

// ============================================
// SMS CONFIGURATION (Twilio - Optional)
// ============================================
define('SMS_ENABLED', false);
// Uncomment and configure when ready to use SMS
/*
define('TWILIO_SID', 'your_account_sid');
define('TWILIO_TOKEN', 'your_auth_token');
define('TWILIO_PHONE', '+1234567890');
*/

// ============================================
// PAYMENT GATEWAY CONFIGURATION
// ============================================
// These are placeholders - configure with real credentials in production
define('MTN_MOMO_ENABLED', false);
define('AIRTEL_MONEY_ENABLED', false);
define('CARD_PAYMENT_ENABLED', false);

// For development, we'll simulate payments
define('PAYMENT_SIMULATION_MODE', true);

// ============================================
// SECURITY SETTINGS
// ============================================
define('ENABLE_CSRF_PROTECTION', true);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// ============================================
// CERTIFICATE SETTINGS
// ============================================
define('CERTIFICATE_CODE_PREFIX', 'URU');
define('CERTIFICATE_YEAR_FORMAT', 'Y');

// ============================================
// SUBSCRIPTION PRICES (in RWF)
// ============================================
define('PRICE_TRIAL', 0);
define('PRICE_INDIVIDUAL_WEEK', 2000);
define('PRICE_INDIVIDUAL_MONTH', 5000);
define('PRICE_SCHOOL_MONTH', 50000);
define('PRICE_SCHOOL_3MONTH', 135000);   // 10% discount
define('PRICE_SCHOOL_6MONTH', 255000);   // 15% discount
define('PRICE_SCHOOL_YEAR', 480000);     // 20% discount

// ============================================
// AGENT COMMISSION
// ============================================
define('AGENT_COMMISSION_RATE', 15.00);  // 15% default

// ============================================
// PAGINATION
// ============================================
define('ITEMS_PER_PAGE', 20);
define('QUESTIONS_PER_PAGE', 10);

// ============================================
// ENVIRONMENT & ERROR HANDLING
// ============================================
define('ENVIRONMENT', 'development');  // 'development' or 'production'

if (ENVIRONMENT === 'development') {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Hide errors in production
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Africa/Kigali');

// ============================================
// LANGUAGE SETTINGS
// ============================================
define('DEFAULT_LANGUAGE', 'kinyarwanda');
define('AVAILABLE_LANGUAGES', ['kinyarwanda', 'english']);

// ============================================
// SESSION SECURITY
// ============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);  // Set to 1 when using HTTPS in production

// ============================================
// AUTO-LOAD CORE FILES
// ============================================
// This ensures database and functions are always available
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/functions.php';

?>