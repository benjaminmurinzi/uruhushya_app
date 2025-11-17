<?php
/**
 * Reject Payment (AJAX)
 * 
 * Admin rejects a payment
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
$reason = isset($_POST['reason']) ? clean_input($_POST['reason']) : '';

if (!$payment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
    exit;
}

try {
    // Start transaction
    db_query("START TRANSACTION");
    
    // Get payment details
    $payment = db_fetch(
        "SELECT p.*, u.email, u.full_name, u.language_preference
         FROM payments p
         JOIN users u ON p.user_id = u.user_id
         WHERE p.payment_id = ?",
        [$payment_id]
    );
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment already processed');
    }
    
    // Update payment status to failed
    $update_payment = "UPDATE payments 
                       SET status = 'failed',
                           rejection_reason = ?,
                           rejected_by = ?,
                           rejected_at = NOW(),
                           updated_at = NOW()
                       WHERE payment_id = ?";
    
    db_query($update_payment, [$reason, $admin_id, $payment_id]);
    
    // Cancel subscription if exists
    if ($payment['subscription_id']) {
        $update_subscription = "UPDATE subscriptions 
                                SET status = 'cancelled',
                                    updated_at = NOW()
                                WHERE subscription_id = ?";
        
        db_query($update_subscription, [$payment['subscription_id']]);
    }
    
    // Log activity
    log_activity($admin_id, 'payment_rejected', "Rejected payment {$payment['transaction_id']} - Reason: {$reason}");
    
    // Send rejection email to student
    $lang = $payment['language_preference'] ?? 'english';
    
    $email_data = [
        'full_name' => $payment['full_name'],
        'transaction_id' => $payment['transaction_id'],
        'amount' => number_format($payment['amount']),
        'reason' => !empty($reason) ? $reason : ($lang === 'english' ? 'Payment could not be verified' : 'Ubwishyu ntibwashobora kwemezwa'),
        'support_email' => SITE_EMAIL,
        'support_phone' => SITE_PHONE
    ];
    
    send_template_email(
        'payment_rejected',
        $payment['email'],
        $lang,
        $email_data,
        $payment['user_id']
    );
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment rejected successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_query("ROLLBACK");
    
    error_log("Payment rejection error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>