<?php
/**
 * Submit and Grade Exam
 * 
 * Calculates score and marks exam as completed
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$user_id = get_user_id();
$lang = $user['language_preference'] ?? 'kinyarwanda';

// Get attempt ID
$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;

if (!$attempt_id) {
    set_flash_message('error', 'Invalid exam attempt');
    redirect('exams.php');
}

// Get attempt details
$attempt = db_fetch(
    "SELECT ea.*, e.* FROM exam_attempts ea 
     JOIN exams e ON ea.exam_id = e.exam_id 
     WHERE ea.attempt_id = ? AND ea.user_id = ?",
    [$attempt_id, $user_id]
);

if (!$attempt) {
    set_flash_message('error', 'Exam attempt not found');
    redirect('exams.php');
}

// Check if already submitted
if ($attempt['status'] === 'completed') {
    redirect('exam-results.php?attempt_id=' . $attempt_id);
}

// Calculate score
$responses = db_fetch_all(
    "SELECT * FROM exam_responses WHERE attempt_id = ?",
    [$attempt_id]
);

$total_questions = $attempt['total_questions'];
$correct_answers = 0;
$wrong_answers = 0;

foreach ($responses as $response) {
    if ($response['is_correct']) {
        $correct_answers++;
    } else {
        $wrong_answers++;
    }
}

$unanswered = $total_questions - count($responses);
$score_percentage = ($correct_answers / $total_questions) * 100;
$passed = ($correct_answers >= $attempt['passing_score']);

// Calculate time taken
$start_time = strtotime($attempt['start_time']);
$time_taken_seconds = time() - $start_time;

// Update exam attempt
$update_sql = "UPDATE exam_attempts 
               SET end_time = NOW(),
                   correct_answers = ?,
                   wrong_answers = ?,
                   unanswered = ?,
                   score_percentage = ?,
                   status = 'completed',
                   time_taken_seconds = ?,
                   passed = ?
               WHERE attempt_id = ?";

db_query($update_sql, [
    $correct_answers,
    $wrong_answers,
    $unanswered,
    $score_percentage,
    $time_taken_seconds,
    $passed,
    $attempt_id
]);

// Update student analytics
$analytics = db_fetch("SELECT * FROM student_analytics WHERE user_id = ?", [$user_id]);

if ($analytics) {
    // Update existing analytics
    $new_total = $analytics['total_exams_taken'] + 1;
    $new_passed = $analytics['total_exams_passed'] + ($passed ? 1 : 0);
    $new_failed = $analytics['total_exams_failed'] + ($passed ? 0 : 1);
    
    // Calculate new average
    $old_avg = $analytics['average_exam_score'];
    $old_total = $analytics['total_exams_taken'];
    $new_avg = (($old_avg * $old_total) + $score_percentage) / $new_total;
    
    // Update best score
    $best_score = max($analytics['best_exam_score'], $score_percentage);
    
    $update_analytics = "UPDATE student_analytics 
                        SET total_exams_taken = ?,
                            total_exams_passed = ?,
                            total_exams_failed = ?,
                            average_exam_score = ?,
                            best_exam_score = ?,
                            last_activity_date = CURDATE(),
                            updated_at = NOW()
                        WHERE user_id = ?";
    
    db_query($update_analytics, [$new_total, $new_passed, $new_failed, $new_avg, $best_score, $user_id]);
} else {
    // Create analytics
    $create_analytics = "INSERT INTO student_analytics 
                        (user_id, total_exams_taken, total_exams_passed, total_exams_failed, 
                         average_exam_score, best_exam_score, last_activity_date)
                        VALUES (?, 1, ?, ?, ?, ?, CURDATE())";
    
    db_query($create_analytics, [
        $user_id,
        $passed ? 1 : 0,
        $passed ? 0 : 1,
        $score_percentage,
        $score_percentage
    ]);
}

// Log activity
log_activity($user_id, 'exam_complete', 'Completed exam: ' . $attempt['exam_code'] . ' - Score: ' . round($score_percentage) . '%');

// Send email notification
$exam_name = $lang === 'english' ? $attempt['exam_name_en'] : $attempt['exam_name_rw'];

$email_template = $passed ? 'exam_passed_notification' : 'exam_failed_notification';

$minutes = floor($time_taken_seconds / 60);
$seconds = $time_taken_seconds % 60;
$time_taken_display = $minutes . ' ' . ($lang === 'english' ? 'min' : 'iminota') . ' ' . $seconds . ' ' . ($lang === 'english' ? 'sec' : 'amasegonda');

$email_vars = [
    'full_name' => $user['full_name'],
    'exam_name' => $exam_name,
    'score' => round($score_percentage),
    'correct_answers' => $correct_answers,
    'total_questions' => $total_questions,
    'time_taken' => $time_taken_display,
    'results_url' => APP_URL . '/../student/exam-results.php?attempt_id=' . $attempt_id
];

if ($passed) {
    $email_vars['certificate_message'] = $lang === 'english' 
        ? 'Keep up the great work! Continue practicing to improve your score even more.'
        : 'Komeza gukora neza! Komeza wige kugira ngo wongere amanota yawe.';
} else {
    // Add weak areas for failed exam
    $email_vars['passing_score'] = round(($attempt['passing_score'] / $total_questions) * 100);
    $email_vars['weak_areas'] = $lang === 'english'
        ? 'Review the lessons and try again. You can do it!'
        : 'Subiramo amasomo hanyuma ugerageze. Ushobora!';
}

send_template_email($email_template, $user['email'], $lang, $email_vars, $user_id);

// Redirect to results
redirect('exam-results.php?attempt_id=' . $attempt_id);
?>