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

// Helper functions
if (!function_exists('clean_input')) {
    function clean_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('log_activity')) {
    function log_activity($user_id, $activity_type, $description) {
        try {
            $sql = "INSERT INTO activity_log (user_id, activity_type, description, created_at)
                    VALUES (?, ?, ?, NOW())";
            db_query($sql, [$user_id, $activity_type, $description]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}

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
        // Get school application - FIXED: use school_id
        $app = db_fetch(
            "SELECT sa.*, u.user_id, u.email, u.full_name, u.language_preference 
             FROM school_applications sa 
             JOIN users u ON sa.school_id = u.user_id 
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
                       SET status = 'approved'
                       WHERE application_id = ?";
        
        db_query($update_app, [$application_id]);
        
        // Update user status to active
        $update_user = "UPDATE users SET status = 'active' WHERE user_id = ?";
        db_query($update_user, [$app['user_id']]);
        
        // Grant 30-day trial subscription
        $subscription_sql = "INSERT INTO subscriptions 
                            (user_id, subscription_type, status, start_date, end_date, amount, created_at)
                            VALUES (?, 'basic', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 50000, NOW())";
        
        db_query($subscription_sql, [$app['user_id']]);
        
        // Log activity
        log_activity($admin_id, 'school_approved', "Approved school: {$app['school_name']}");
        
        // Send approval email (if email template exists)
        try {
            send_template_email(
                'school_application_approved',
                $app['email'],
                $app['language_preference'] ?? 'english',
                [
                    'full_name' => $app['full_name'],
                    'school_name' => $app['school_name'],
                    'login_url' => APP_URL . '/public/login.php'
                ],
                $app['user_id']
            );
        } catch (Exception $e) {
            // Email error shouldn't stop approval
            error_log("Email error: " . $e->getMessage());
        }
        
    } else { // agent
        // Get agent application - FIXED: use agent_id
        $app = db_fetch(
            "SELECT aa.*, u.user_id, u.email, u.full_name, u.language_preference 
             FROM agent_applications aa 
             JOIN users u ON aa.agent_id = u.user_id 
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
                       SET status = 'approved'
                       WHERE application_id = ?";
        
        db_query($update_app, [$application_id]);
        
        // Update user status to active
        $update_user = "UPDATE users SET status = 'active' WHERE user_id = ?";
        db_query($update_user, [$app['user_id']]);
        
        // Log activity
        log_activity($admin_id, 'agent_approved', "Approved agent: {$app['full_name']} (Code: {$app['agent_code']})");
        
        // Send approval email (if email template exists)
        try {
            send_template_email(
                'agent_application_approved',
                $app['email'],
                $app['language_preference'] ?? 'english',
                [
                    'full_name' => $app['full_name'],
                    'agent_code' => $app['agent_code'],
                    'commission_rate' => '10%',
                    'login_url' => APP_URL . '/public/login.php'
                ],
                $app['user_id']
            );
        } catch (Exception $e) {
            // Email error shouldn't stop approval
            error_log("Email error: " . $e->getMessage());
        }
    }
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' application approved successfully!'
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