<?php
/**
 * Delete User (AJAX)
 * 
 * Soft delete or permanently delete user
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require admin login
if (!is_logged_in() || get_user_role() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = get_user_id();

// Get user ID
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get user details
$user = db_fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Don't allow deleting admins
if ($user['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
    exit;
}

// Don't allow deleting yourself
if ($user_id === $admin_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
    exit;
}

try {
    // Start transaction
    db_query("START TRANSACTION");
    
    // Soft delete: Update status to 'deleted'
    $delete_sql = "UPDATE users SET status = 'deleted', updated_at = NOW() WHERE user_id = ?";
    db_query($delete_sql, [$user_id]);
    
    // Deactivate all subscriptions
    $deactivate_subs = "UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ?";
    db_query($deactivate_subs, [$user_id]);
    
    // Log activity
    log_activity($admin_id, 'user_delete', "Deleted user: {$user['full_name']} (ID: {$user_id})");
    
    // Commit transaction
    db_query("COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    db_query("ROLLBACK");
    
    error_log("User delete error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the user'
    ]);
}
?>