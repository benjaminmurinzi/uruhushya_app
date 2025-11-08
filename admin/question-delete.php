<?php
/**
 * Delete Question (AJAX)
 * 
 * Soft delete a question
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

// Get question ID
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
    exit;
}

// Get question details
$question = db_fetch("SELECT * FROM questions WHERE question_id = ?", [$question_id]);

if (!$question) {
    echo json_encode(['success' => false, 'message' => 'Question not found']);
    exit;
}

try {
    // Soft delete: Update status to 'deleted'
    $delete_sql = "UPDATE questions SET status = 'deleted', updated_at = NOW() WHERE question_id = ?";
    db_query($delete_sql, [$question_id]);
    
    // Log activity
    log_activity($admin_id, 'question_delete', "Deleted question ID: {$question_id}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Question deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Question delete error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the question'
    ]);
}
?>