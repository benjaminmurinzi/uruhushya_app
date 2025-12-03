<?php
/**
 * Student Access Control
 * 
 * Determines what a student can access based on:
 * - School affiliation (school students get full access)
 * - Personal subscription (independent students must pay)
 * - Free quiz quota (3 free quizzes for independent students)
 */

/**
 * Check if student belongs to a school
 * 
 * @param int $student_id
 * @return array|false School info if student belongs to school, false otherwise
 */
function get_student_school($student_id) {
    $school = db_fetch(
        "SELECT ss.*, u.full_name as school_name, u.email as school_email,
         s.subscription_type, s.status as subscription_status, s.end_date
         FROM school_students ss
         JOIN users u ON ss.school_id = u.user_id
         LEFT JOIN subscriptions s ON ss.school_id = s.user_id AND s.status = 'active' AND s.end_date >= CURDATE()
         WHERE ss.student_id = ? AND ss.status = 'active'
         ORDER BY s.created_at DESC
         LIMIT 1",
        [$student_id]
    );
    
    return $school;
}

/**
 * Check if student has full exam access
 * 
 * @param int $student_id
 * @return bool
 */
function has_full_exam_access($student_id) {
    // Check if student belongs to a school with active subscription
    $school = get_student_school($student_id);
    if ($school && $school['subscription_status'] === 'active') {
        return true; // School students get full access
    }
    
    // Check if student has personal subscription
    $personal_subscription = get_user_subscription($student_id);
    if ($personal_subscription && $personal_subscription['status'] === 'active') {
        return true; // Student paid for themselves
    }
    
    return false; // No access to full exams
}

/**
 * Get free quiz attempts remaining
 * 
 * @param int $student_id
 * @return int Number of free quizzes remaining
 */
function get_free_quiz_remaining($student_id) {
    // School students don't need free quizzes (they have full access)
    if (get_student_school($student_id)) {
        return 0; // Not applicable
    }
    
    // Independent students get 3 free quizzes
    $used = db_fetch(
        "SELECT COUNT(*) as count 
         FROM exam_attempts 
         WHERE user_id = ? AND exam_type = 'free_practice'",
        [$student_id]
    )['count'] ?? 0;
    
    return max(0, 3 - $used);
}

/**
 * Get student access type
 * 
 * @param int $student_id
 * @return string 'school', 'subscribed', or 'free'
 */
function get_student_access_type($student_id) {
    // Check school affiliation first
    $school = get_student_school($student_id);
    if ($school && $school['subscription_status'] === 'active') {
        return 'school';
    }
    
    // Check personal subscription
    $subscription = get_user_subscription($student_id);
    if ($subscription && $subscription['status'] === 'active') {
        return 'subscribed';
    }
    
    return 'free';
}

/**
 * Check if student can take exam
 * 
 * @param int $student_id
 * @param string $exam_type 'free_practice' or 'full_exam'
 * @return array ['allowed' => bool, 'message' => string]
 */
function can_take_exam($student_id, $exam_type = 'full_exam') {
    $access_type = get_student_access_type($student_id);
    
    // School students and subscribed students can take any exam
    if ($access_type === 'school' || $access_type === 'subscribed') {
        return ['allowed' => true, 'message' => ''];
    }
    
    // Free students can only take free practice quizzes
    if ($exam_type === 'free_practice') {
        $remaining = get_free_quiz_remaining($student_id);
        if ($remaining > 0) {
            return ['allowed' => true, 'message' => ''];
        } else {
            return [
                'allowed' => false, 
                'message' => 'You have used all 3 free practice quizzes. Please subscribe to continue.'
            ];
        }
    }
    
    // Free students cannot take full exams
    return [
        'allowed' => false,
        'message' => 'Please subscribe to access full exams. You can try our 3 free practice quizzes first!'
    ];
}