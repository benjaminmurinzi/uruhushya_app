<?php
/**
 * Verify Payment (AJAX)
 * 
 * Admin verifies a payment and activates subscription
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// Set JSON header
header('Content-Type: application/json');

// Require admin login
if (!is_logged_in() || get_user_role() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = get_user_id();
$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;

if (!$payment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
    exit;
}

try {
    // Start transaction
    db_query("START TRANSACTION");
    
    // Get payment details
    $payment = db_fetch(
        "SELECT p.*, u.email, u.full_name, u.language_preference,
         s.subscription_id, s.start_date, s.end_date, s.subscription_type
         FROM payments p
         JOIN users u ON p.user_id = u.user_id
         LEFT JOIN subscriptions s ON p.subscription_id = s.subscription_id
         WHERE p.payment_id = ?",
        [$payment_id]
    );
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment already processed');
    }
    
    // Update payment status to completed
    $update_payment = "UPDATE payments 
                       SET status = 'completed',
                           verified_by = ?,
                           verified_at = NOW(),
                           updated_at = NOW()
                       WHERE payment_id = ?";
    
    db_query($update_payment, [$admin_id, $payment_id]);
    
    // Activate subscription
    if ($payment['subscription_id']) {
        $update_subscription = "UPDATE subscriptions 
                                SET status = 'active',
                                    updated_at = NOW()
                                WHERE subscription_id = ?";
        
        db_query($update_subscription, [$payment['subscription_id']]);
    }
    
    // Log activity
    log_activity($admin_id, 'payment_verified', "Verified payment {$payment['transaction_id']} - {$payment['amount']} RWF");
    
    // Send confirmation email to student
    $lang = $payment['language_preference'] ?? 'english';
    
    $email_data = [
        'full_name' => $payment['full_name'],
        'transaction_id' => $payment['transaction_id'],
        'amount' => number_format($payment['amount']),
        'payment_method' => $payment['payment_method'] === 'momo' ? 'MTN Mobile Money' : 'Bank Transfer',
        'subscription_type' => ucfirst($payment['subscription_type'] ?? 'monthly'),
        'start_date' => format_date($payment['start_date']),
        'end_date' => format_date($payment['end_date']),
        'login_url' => APP_URL . '/public/login.php'
    ];
    
    send_template_email(
        'payment_confirmed',
        $payment['email'],
        $lang,
        $email_data,
        $payment['user_id']
    );
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified and subscription activated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_query("ROLLBACK");
    
    error_log("Payment verification error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>