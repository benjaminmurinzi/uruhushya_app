<?php
/**
 * Mark Agent Payout as Paid
 * 
 * This updates agent commission records and marks them as paid
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// Require admin login
require_role('admin');

header('Content-Type: application/json');

$admin_id = get_user_id();
$payout_id = isset($_POST['payout_id']) ? (int)$_POST['payout_id'] : 0;

if ($payout_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payout ID']);
    exit;
}

try {
    // Start transaction
    db_query("START TRANSACTION");
    
    // Get payout details
    $payout = db_fetch(
        "SELECT pr.*, u.full_name, u.email, u.language_preference
         FROM payout_requests pr
         JOIN users u ON pr.agent_id = u.user_id
         WHERE pr.payout_id = ? AND pr.status = 'approved'",
        [$payout_id]
    );
    
    if (!$payout) {
        db_query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Payout not found or not approved']);
        exit;
    }
    
    // Update payout status to paid
    db_query(
        "UPDATE payout_requests 
         SET status = 'paid', 
             processed_date = NOW(),
             updated_at = NOW()
         WHERE payout_id = ?",
        [$payout_id]
    );
    
    // Mark agent's commissions as paid (up to the payout amount)
    // This marks the oldest unpaid commissions first
    db_query(
        "UPDATE referrals 
         SET commission_paid = 1, 
             payment_date = NOW()
         WHERE agent_id = ? 
         AND commission_paid = 0 
         AND status = 'active'
         AND commission_amount > 0
         ORDER BY referral_date ASC
         LIMIT (
             SELECT COUNT(*) FROM (
                 SELECT referral_id, 
                 @running_total := @running_total + commission_amount as running_total
                 FROM referrals, (SELECT @running_total := 0) AS init
                 WHERE agent_id = ? 
                 AND commission_paid = 0 
                 AND status = 'active'
                 AND commission_amount > 0
                 ORDER BY referral_date ASC
             ) as subquery
             WHERE running_total <= ?
         )",
        [$payout['agent_id'], $payout['agent_id'], $payout['amount']]
    );
    
    // Log activity
    log_activity($admin_id, 'payout_completed', "Marked payout #{$payout_id} as PAID for {$payout['full_name']} - {$payout['amount']} RWF");
    log_activity($payout['agent_id'], 'payout_completed', "Your payout of {$payout['amount']} RWF has been processed");
    
    // Send confirmation email to agent
    send_template_email(
        'payout_completed',
        $payout['email'],
        $payout['language_preference'],
        [
            'full_name' => $payout['full_name'],
            'amount' => number_format($payout['amount']),
            'payment_method' => $payout['payment_method'] === 'momo' ? 'Mobile Money' : 'Bank Transfer',
            'payment_details' => $payout['payment_method'] === 'momo' 
                ? $payout['phone_number'] 
                : $payout['bank_name'] . ' - ' . $payout['bank_account']
        ],
        $payout['agent_id']
    );
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode(['success' => true, 'message' => 'Payout marked as paid successfully']);
    
} catch (Exception $e) {
    db_query("ROLLBACK");
    error_log("Mark payout paid error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to mark payout as paid']);
}