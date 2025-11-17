<?php
/**
 * Flutterwave Payment Provider
 * 
 * READY TO USE - Just add API keys in payment-config.php
 * 
 * Developer: Benjamin NIYOMURINZI
 */

function initiate_flutterwave_payment($user, $amount, $plan_name, $transaction_id) {
    $url = FLUTTERWAVE_API_URL . '/payments';
    
    $data = [
        'tx_ref' => $transaction_id,
        'amount' => $amount,
        'currency' => PAYMENT_CURRENCY,
        'redirect_url' => PAYMENT_CALLBACK_URL,
        'payment_options' => 'mobilemoneyrwanda',
        'customer' => [
            'email' => $user['email'],
            'phonenumber' => $user['phone'],
            'name' => $user['full_name']
        ],
        'customizations' => [
            'title' => APP_NAME,
            'description' => "Payment for {$plan_name}",
            'logo' => APP_URL . '/assets/logo.png'
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        
        if ($result['status'] === 'success') {
            return [
                'success' => true,
                'redirect_url' => $result['data']['link'],
                'transaction_id' => $transaction_id
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Failed to initiate Flutterwave payment'
    ];
}

function verify_flutterwave_payment($transaction_id) {
    $url = FLUTTERWAVE_API_URL . '/transactions/verify_by_reference?tx_ref=' . $transaction_id;
    
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $result['status'] === 'success' && $result['data']['status'] === 'successful',
        'verified' => true
    ];
}