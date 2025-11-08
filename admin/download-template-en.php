<?php
/**
 * Download English Questions Template
 * 
 * Generates CSV template for English questions
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="english_questions_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'question_text',
    'choice_a',
    'choice_b',
    'choice_c',
    'choice_d',
    'correct_answer',
    'difficulty',
    'course_id',
    'explanation'
]);

// Write sample rows with instructions
fputcsv($output, [
    'What does a red traffic light mean?',
    'Go',
    'Stop',
    'Slow down',
    'Turn right',
    'B',
    'easy',
    '1',
    'A red light means you must stop completely before the intersection.'
]);

fputcsv($output, [
    'What is the speed limit in residential areas in Rwanda?',
    '30 km/h',
    '40 km/h',
    '50 km/h',
    '60 km/h',
    'B',
    'medium',
    '1',
    'The speed limit in residential areas is 40 km/h.'
]);

fputcsv($output, [
    'When must you use your vehicle headlights?',
    'Only at night',
    'In rain and fog',
    'During sunset and sunrise',
    'All of the above',
    'D',
    'hard',
    '1',
    'Headlights must be used at night, in poor visibility conditions, and during twilight hours.'
]);

fclose($output);
exit;
?>