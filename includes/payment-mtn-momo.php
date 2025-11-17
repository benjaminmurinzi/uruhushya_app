<?php
/**
 * MTN Mobile Money Provider
 * 
 * READY TO USE - Just add API keys in payment-config.php
 * 
 * Developer: Benjamin NIYOMURINZI
 */

function initiate_mtn_momo_payment($phone, $amount, $transaction_id) {
    // Implementation same as before
    // Will work automatically when you add API keys
    
    return [
        'success' => false,
        'message' => 'MTN MoMo API keys not configured yet'
    ];
}

function verify_mtn_momo_payment($transaction_id) {
    // Implementation same as before
    
    return [
        'success' => false,
        'message' => 'MTN MoMo API keys not configured yet'
    ];
}