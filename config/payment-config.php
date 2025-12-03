<?php
/**
 * Payment Configuration
 * 
 * Configure payment gateways and switch between demo/live modes
 * 
 * Developer: Benjamin NIYOMURINZI
 */

// ============================================
// PAYMENT MODE - CHANGE THIS WHEN YOU GET API KEYS
// ============================================
define('PAYMENT_MODE', 'demo'); // Options: 'demo', 'flutterwave', 'mtn_momo', 'paypack'

// ============================================
// FLUTTERWAVE CONFIGURATION
// ============================================
define('FLUTTERWAVE_MODE', 'test'); // 'test' or 'live'

// TEST KEYS (Get from: https://dashboard.flutterwave.com/dashboard/settings/apis)
define('FLUTTERWAVE_TEST_PUBLIC_KEY', 'FLWPUBK_TEST-XXXXXXXXXXXXX-X');
define('FLUTTERWAVE_TEST_SECRET_KEY', 'FLWSECK_TEST-XXXXXXXXXXXXX-X');
define('FLUTTERWAVE_TEST_ENCRYPTION_KEY', 'FLWSECK_TESTXXXXXXXX');

// LIVE KEYS (Fill these when you get production approval)
define('FLUTTERWAVE_LIVE_PUBLIC_KEY', '');
define('FLUTTERWAVE_LIVE_SECRET_KEY', '');
define('FLUTTERWAVE_LIVE_ENCRYPTION_KEY', '');

// Active keys based on mode
define('FLUTTERWAVE_PUBLIC_KEY', FLUTTERWAVE_MODE === 'live' ? FLUTTERWAVE_LIVE_PUBLIC_KEY : FLUTTERWAVE_TEST_PUBLIC_KEY);
define('FLUTTERWAVE_SECRET_KEY', FLUTTERWAVE_MODE === 'live' ? FLUTTERWAVE_LIVE_SECRET_KEY : FLUTTERWAVE_TEST_SECRET_KEY);
define('FLUTTERWAVE_ENCRYPTION_KEY', FLUTTERWAVE_MODE === 'live' ? FLUTTERWAVE_LIVE_ENCRYPTION_KEY : FLUTTERWAVE_TEST_ENCRYPTION_KEY);

define('FLUTTERWAVE_API_URL', 'https://api.flutterwave.com/v3');

// ============================================
// MTN MOBILE MONEY CONFIGURATION
// ============================================
define('MTN_MOMO_MODE', 'sandbox'); // 'sandbox' or 'production'

// SANDBOX KEYS (Get from: https://momodeveloper.mtn.com)
define('MTN_MOMO_SANDBOX_SUBSCRIPTION_KEY', 'YOUR_SANDBOX_SUBSCRIPTION_KEY');
define('MTN_MOMO_SANDBOX_API_USER', 'YOUR_SANDBOX_API_USER');
define('MTN_MOMO_SANDBOX_API_KEY', 'YOUR_SANDBOX_API_KEY');

// PRODUCTION KEYS (Fill when approved)
define('MTN_MOMO_PROD_SUBSCRIPTION_KEY', '');
define('MTN_MOMO_PROD_API_USER', '');
define('MTN_MOMO_PROD_API_KEY', '');

// Active keys based on mode
define('MTN_MOMO_SUBSCRIPTION_KEY', MTN_MOMO_MODE === 'production' ? MTN_MOMO_PROD_SUBSCRIPTION_KEY : MTN_MOMO_SANDBOX_SUBSCRIPTION_KEY);
define('MTN_MOMO_API_USER', MTN_MOMO_MODE === 'production' ? MTN_MOMO_PROD_API_USER : MTN_MOMO_SANDBOX_API_USER);
define('MTN_MOMO_API_KEY', MTN_MOMO_MODE === 'production' ? MTN_MOMO_PROD_API_KEY : MTN_MOMO_SANDBOX_API_KEY);

define('MTN_MOMO_API_URL', MTN_MOMO_MODE === 'production' 
    ? 'https://momodeveloper.mtn.com' 
    : 'https://sandbox.momodeveloper.mtn.com');

// ============================================
// PAYPACK CONFIGURATION
// ============================================
define('PAYPACK_MODE', 'test'); // 'test' or 'live'

define('PAYPACK_TEST_CLIENT_ID', 'YOUR_TEST_CLIENT_ID');
define('PAYPACK_TEST_CLIENT_SECRET', 'YOUR_TEST_CLIENT_SECRET');

define('PAYPACK_LIVE_CLIENT_ID', '');
define('PAYPACK_LIVE_CLIENT_SECRET', '');

define('PAYPACK_CLIENT_ID', PAYPACK_MODE === 'live' ? PAYPACK_LIVE_CLIENT_ID : PAYPACK_TEST_CLIENT_ID);
define('PAYPACK_CLIENT_SECRET', PAYPACK_MODE === 'live' ? PAYPACK_LIVE_CLIENT_SECRET : PAYPACK_TEST_CLIENT_SECRET);

define('PAYPACK_API_URL', 'https://payments.paypack.rw/api');

// ============================================
// GENERAL PAYMENT SETTINGS
// ============================================
define('PAYMENT_CURRENCY', 'RWF');
define('PAYMENT_CALLBACK_URL', APP_URL . '/student/payment-callback.php');
define('PAYMENT_WEBHOOK_URL', APP_URL . '/webhooks/payment-webhook.php');

// ============================================
// HELPER FUNCTION - Check if in demo mode
// ============================================
function is_demo_mode() {
    return PAYMENT_MODE === 'demo';
}

function get_payment_provider() {
    return PAYMENT_MODE;
}