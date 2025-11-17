<?php
/**
 * Payment Gateway Manager
 * 
 * Universal interface for all payment providers
 * Automatically routes to correct provider based on config
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/payment-config.php';
require_once 'payment-demo.php';
require_once 'payment-flutterwave.php';
require_once 'payment-mtn-momo.php';
require_once 'payment-paypack.php';

class PaymentGateway {
    
    private $provider;
    
    public function __construct() {
        $this->provider = get_payment_provider();
    }
    
    /**
     * Process payment through appropriate gateway
     */
    public function processPayment($user, $amount, $phone, $plan_key, $plan_details) {
        switch ($this->provider) {
            case 'demo':
                return $this->processDemoPayment($user['user_id'], $amount, $phone, $plan_key, $plan_details);
                
            case 'flutterwave':
                return $this->processFlutterwavePayment($user, $amount, $phone, $plan_key, $plan_details);
                
            case 'mtn_momo':
                return $this->processMtnMomoPayment($user['user_id'], $amount, $phone, $plan_key, $plan_details);
                
            case 'paypack':
                return $this->processPaypackPayment($user, $amount, $phone, $plan_key, $plan_details);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Invalid payment provider configured'
                ];
        }
    }
    
    /**
     * Verify payment through appropriate gateway
     */
    public function verifyPayment($transaction_id) {
        switch ($this->provider) {
            case 'demo':
                // Demo payments are auto-verified
                return ['success' => true, 'verified' => true];
                
            case 'flutterwave':
                return verify_flutterwave_payment($transaction_id);
                
            case 'mtn_momo':
                return verify_mtn_momo_payment($transaction_id);
                
            case 'paypack':
                return verify_paypack_payment($transaction_id);
                
            default:
                return ['success' => false, 'message' => 'Invalid payment provider'];
        }
    }
    
    /**
     * Get payment provider name for display
     */
    public function getProviderName() {
        switch ($this->provider) {
            case 'demo':
                return 'DEMO MODE';
            case 'flutterwave':
                return 'Flutterwave';
            case 'mtn_momo':
                return 'MTN Mobile Money';
            case 'paypack':
                return 'Paypack';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Check if provider is ready
     */
    public function isConfigured() {
        switch ($this->provider) {
            case 'demo':
                return true;
                
            case 'flutterwave':
                return !empty(FLUTTERWAVE_SECRET_KEY) && FLUTTERWAVE_SECRET_KEY !== 'FLWSECK_TEST-XXXXXXXXXXXXX-X';
                
            case 'mtn_momo':
                return !empty(MTN_MOMO_SUBSCRIPTION_KEY) && MTN_MOMO_SUBSCRIPTION_KEY !== 'YOUR_SANDBOX_SUBSCRIPTION_KEY';
                
            case 'paypack':
                return !empty(PAYPACK_CLIENT_ID) && PAYPACK_CLIENT_ID !== 'YOUR_TEST_CLIENT_ID';
                
            default:
                return false;
        }
    }
    
    // Private methods for each provider
    
    private function processDemoPayment($user_id, $amount, $phone, $plan_key, $plan_details) {
        simulate_payment_delay();
        return process_demo_payment($user_id, $amount, $phone, $plan_key, $plan_details);
    }
    
    private function processFlutterwavePayment($user, $amount, $phone, $plan_key, $plan_details) {
        return initiate_flutterwave_payment($user, $amount, $plan_details['name'], generate_transaction_id());
    }
    
    private function processMtnMomoPayment($user_id, $amount, $phone, $plan_key, $plan_details) {
        return initiate_mtn_momo_payment($phone, $amount, generate_transaction_id());
    }
    
    private function processPaypackPayment($user, $amount, $phone, $plan_key, $plan_details) {
        return initiate_paypack_payment($user, $amount, $phone, $plan_details['name']);
    }
}

/**
 * Generate unique transaction ID
 */
function generate_transaction_id() {
    $prefix = is_demo_mode() ? 'DEMO' : 'TXN';
    return $prefix . time() . rand(1000, 9999);
}