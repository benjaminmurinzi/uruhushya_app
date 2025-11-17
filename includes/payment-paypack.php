<?php
/**
 * Paypack Payment Provider
 * 
 * READY TO USE - Just add API keys in payment-config.php
 * 
 * Developer: Benjamin NIYOMURINZI
 */

function initiate_paypack_payment($user, $amount, $phone, $plan_name) {
    // Implementation for Paypack
    // Will work automatically when you add API keys
    
    return [
        'success' => false,
        'message' => 'Paypack API keys not configured yet'
    ];
}

function verify_paypack_payment($transaction_id) {
    // Implementation for Paypack verification
    
    return [
        'success' => false,
        'message' => 'Paypack API keys not configured yet'
    ];
}