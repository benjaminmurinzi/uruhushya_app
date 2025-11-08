<?php
/**
 * Save Exam Answer via AJAX
 * 
 * Saves student's answer to a question
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require student login
if (!is_logged_in() || get_user_role() !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = get_user_id();

// Get POST data
$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$choice_id = isset($_POST['choice_id']) ? (int)$_POST['choice_id'] : 0;

if (!$attempt_id || !$question_id || !$choice_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Verify attempt belongs to user
$attempt = db_fetch(
    "SELECT * FROM exam_attempts WHERE attempt_id = ? AND user_id = ? AND status = 'in_progress'",
    [$attempt_id, $user_id]
);

if (!$attempt) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam attempt']);
    exit;
}

// Check if answer already exists
$existing = db_fetch(
    "SELECT response_id FROM exam_responses WHERE attempt_id = ? AND question_id = ?",
    [$attempt_id, $question_id]
);

// Get correct answer
$correct_choice = db_fetch(
    "SELECT choice_id FROM question_choices WHERE question_id = ? AND is_correct = 1",
    [$question_id]
);

$is_correct = ($choice_id == $correct_choice['choice_id']);

if ($existing) {
    // Update existing answer
    $sql = "UPDATE exam_responses 
            SET choice_id = ?, is_correct = ?, answered_at = NOW() 
            WHERE response_id = ?";
    db_query($sql, [$choice_id, $is_correct, $existing['response_id']]);
} else {
    // Insert new answer
    $sql = "INSERT INTO exam_responses (attempt_id, question_id, choice_id, is_correct, answered_at) 
            VALUES (?, ?, ?, ?, NOW())";
    db_query($sql, [$attempt_id, $question_id, $choice_id, $is_correct]);
}

echo json_encode([
    'success' => true,
    'message' => 'Answer saved',
    'is_correct' => $is_correct
]);
?>