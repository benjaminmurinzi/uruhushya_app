<?php
/**
 * Reject Application (AJAX)
 * 
 * Reject school or agent application
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

// Get parameters
$type = isset($_POST['type']) ? clean_input($_POST['type']) : '';
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$reason = isset($_POST['reason']) ? clean_input($_POST['reason']) : '';

if (!in_array($type, ['school', 'agent']) || !$application_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Start transaction
    db_query("START TRANSACTION");
    
    if ($type === 'school') {
        // Get school application
        $app = db_fetch(
            "SELECT sa.*, u.email, u.full_name, u.language_preference 
             FROM school_applications sa 
             JOIN users u ON sa.user_id = u.user_id 
             WHERE sa.application_id = ?",
            [$application_id]
        );
        
        if (!$app) {
            throw new Exception('Application not found');
        }
        
        if ($app['status'] !== 'pending') {
            throw new Exception('Application already processed');
        }
        
        // Update application status
        $update_app = "UPDATE school_applications 
                       SET status = 'rejected', 
                           rejection_reason = ?,
                           rejected_by = ?,
                           rejected_at = NOW(),
                           updated_at = NOW()
                       WHERE application_id = ?";
        
        db_query($update_app, [$reason, $admin_id, $application_id]);
        
        // Log activity
        log_activity($admin_id, 'school_rejected', "Rejected school application: {$app['school_name']}");
        
        // Send rejection email
        $lang = $app['language_preference'] ?? 'english';
        
        send_template_email(
            'school_application_rejected',
            $app['email'],
            $lang,
            [
                'full_name' => $app['full_name'],
                'school_name' => $app['school_name'],
                'reason' => !empty($reason) ? $reason : ($lang === 'english' ? 'Application requirements not met' : 'Ibisabwa ntibyujuje'),
                'support_email' => SITE_EMAIL
            ],
            $app['user_id']
        );
        
    } else { // agent
        // Get agent application
        $app = db_fetch(
            "SELECT aa.*, u.email, u.full_name, u.language_preference 
             FROM agent_applications aa 
             JOIN users u ON aa.user_id = u.user_id 
             WHERE aa.application_id = ?",
            [$application_id]
        );
        
        if (!$app) {
            throw new Exception('Application not found');
        }
        
        if ($app['status'] !== 'pending') {
            throw new Exception('Application already processed');
        }
        
        // Update application status
        $update_app = "UPDATE agent_applications 
                       SET status = 'rejected', 
                           rejection_reason = ?,
                           rejected_by = ?,
                           rejected_at = NOW(),
                           updated_at = NOW()
                       WHERE application_id = ?";
        
        db_query($update_app, [$reason, $admin_id, $application_id]);
        
        // Log activity
        log_activity($admin_id, 'agent_rejected', "Rejected agent application: {$app['full_name']}");
        
        // Send rejection email
        $lang = $app['language_preference'] ?? 'english';
        
        send_template_email(
            'agent_application_rejected',
            $app['email'],
            $lang,
            [
                'full_name' => $app['full_name'],
                'reason' => !empty($reason) ? $reason : ($lang === 'english' ? 'Application requirements not met' : 'Ibisabwa ntibyujuje'),
                'support_email' => SITE_EMAIL
            ],
            $app['user_id']
        );
    }
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' application rejected'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_query("ROLLBACK");
    
    error_log("Application rejection error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>