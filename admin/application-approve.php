<?php
/**
 * Approve Application (AJAX)
 * 
 * Approve school or agent application
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
        
        // Generate school code if not exists
        $school_code = $app['school_code'];
        if (empty($school_code)) {
            // Generate unique school code: SCH + timestamp + random
            $school_code = 'SCH' . time() . rand(100, 999);
        }
        
        // Update application status
        $update_app = "UPDATE school_applications 
                       SET status = 'approved', 
                           school_code = ?,
                           approved_by = ?,
                           approved_at = NOW(),
                           updated_at = NOW()
                       WHERE application_id = ?";
        
        db_query($update_app, [$school_code, $admin_id, $application_id]);
        
        // Update user role to school
        $update_user = "UPDATE users SET role = 'school', updated_at = NOW() WHERE user_id = ?";
        db_query($update_user, [$app['user_id']]);
        
        // Grant 30-day subscription
        $subscription_sql = "INSERT INTO subscriptions 
                            (user_id, subscription_type, status, start_date, end_date, amount, currency, created_at)
                            VALUES (?, 'monthly', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, 'RWF', NOW())";
        
        db_query($subscription_sql, [$app['user_id']]);
        
        // Log activity
        log_activity($admin_id, 'school_approved', "Approved school application: {$app['school_name']} (Code: {$school_code})");
        
        // Send approval email
        $lang = $app['language_preference'] ?? 'english';
        
        send_template_email(
            'school_application_approved',
            $app['email'],
            $lang,
            [
                'full_name' => $app['full_name'],
                'school_name' => $app['school_name'],
                'school_code' => $school_code,
                'login_url' => APP_URL . '/login.php'
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
        
        // Generate agent code if not exists
        $agent_code = $app['agent_code'];
        if (empty($agent_code)) {
            // Generate unique agent code: AG + 3 digit number
            $last_agent = db_fetch("SELECT agent_code FROM agent_applications WHERE agent_code IS NOT NULL ORDER BY application_id DESC LIMIT 1");
            
            if ($last_agent && preg_match('/AG(\d+)/', $last_agent['agent_code'], $matches)) {
                $next_num = (int)$matches[1] + 1;
            } else {
                $next_num = 1;
            }
            
            $agent_code = 'AG' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        }
        
        // Update application status
        $update_app = "UPDATE agent_applications 
                       SET status = 'approved', 
                           agent_code = ?,
                           approved_by = ?,
                           approved_at = NOW(),
                           updated_at = NOW()
                       WHERE application_id = ?";
        
        db_query($update_app, [$agent_code, $admin_id, $application_id]);
        
        // Update user role to agent
        $update_user = "UPDATE users SET role = 'agent', agent_code = ?, updated_at = NOW() WHERE user_id = ?";
        db_query($update_user, [$agent_code, $app['user_id']]);
        
        // Log activity
        log_activity($admin_id, 'agent_approved', "Approved agent application: {$app['full_name']} (Code: {$agent_code})");
        
        // Send approval email
        $lang = $app['language_preference'] ?? 'english';
        
        send_template_email(
            'agent_application_approved',
            $app['email'],
            $lang,
            [
                'full_name' => $app['full_name'],
                'agent_code' => $agent_code,
                'commission_rate' => '10%',
                'login_url' => APP_URL . '/login.php'
            ],
            $app['user_id']
        );
    }
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' application approved successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_query("ROLLBACK");
    
    error_log("Application approval error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>