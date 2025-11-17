<?php
/**
 * Get user's active subscription
 */
function get_user_subscription($user_id) {
    return db_fetch(
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND status = 'active' 
         ORDER BY end_date DESC 
         LIMIT 1",
        [$user_id]
    );
}

/**
 * Check if user has active subscription
 */
function has_active_subscription($user_id) {
    $subscription = get_user_subscription($user_id);
    
    if (!$subscription) {
        return false;
    }
    
    // Check if subscription has expired
    $end_date = strtotime($subscription['end_date']);
    $today = strtotime(date('Y-m-d'));
    
    if ($today > $end_date) {
        // Expire the subscription
        db_query("UPDATE subscriptions SET status = 'expired' WHERE subscription_id = ?", [$subscription['subscription_id']]);
        return false;
    }
    
    return true;
}

/**
 * Get days remaining in subscription
 */
function get_subscription_days_remaining($user_id) {
    $subscription = get_user_subscription($user_id);
    
    if (!$subscription) {
        return 0;
    }
    
    $end_date = strtotime($subscription['end_date']);
    $today = strtotime(date('Y-m-d'));
    
    $days = floor(($end_date - $today) / (60 * 60 * 24));
    
    return max(0, $days);
}

/**
 * Validate phone number (Rwanda format)
 */
function is_valid_phone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check if it's 10 digits starting with 07
    return preg_match('/^07[2-9]\d{7}$/', $phone);
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'RWF') {
    return number_format($amount) . ' ' . $currency;
}
