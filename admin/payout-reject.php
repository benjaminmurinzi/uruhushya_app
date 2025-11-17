<?php
/**
 * Reject Agent Payout Request
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
$reason = isset($_POST['reason']) ? clean_input($_POST['reason']) : '';

if ($payout_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payout ID']);
    exit;
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason required']);
    exit;
}

try {
    // Get payout details
    $payout = db_fetch(
        "SELECT pr.*, u.full_name, u.email, u.language_preference
         FROM payout_requests pr
         JOIN users u ON pr.agent_id = u.user_id
         WHERE pr.payout_id = ? AND pr.status = 'pending'",
        [$payout_id]
    );
    
    if (!$payout) {
        echo json_encode(['success' => false, 'message' => 'Payout not found or already processed']);
        exit;
    }
    
    // Update payout status to rejected
    db_query(
        "UPDATE payout_requests 
         SET status = 'rejected', 
             processed_by = ?, 
             processed_date = NOW(),
             rejection_reason = ?,
             updated_at = NOW()
         WHERE payout_id = ?",
        [$admin_id, $reason, $payout_id]
    );
    
    // Log activity
    log_activity($admin_id, 'payout_rejected', "Rejected payout request #{$payout_id} for {$payout['full_name']} - Reason: {$reason}");
    log_activity($payout['agent_id'], 'payout_rejected', "Your payout request for {$payout['amount']} RWF was rejected: {$reason}");
    
    // Send email notification to agent
    send_template_email(
        'payout_rejected',
        $payout['email'],
        $payout['language_preference'],
        [
            'full_name' => $payout['full_name'],
            'amount' => number_format($payout['amount']),
            'reason' => $reason
        ],
        $payout['agent_id']
    );
    
    echo json_encode(['success' => true, 'message' => 'Payout rejected']);
    
} catch (Exception $e) {
    error_log("Payout rejection error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to reject payout']);
}