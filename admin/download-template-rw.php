<?php
/**
 * Download Kinyarwanda Questions Template
 * 
 * Generates CSV template for Kinyarwanda questions
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="kinyarwanda_questions_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'ikibazo',
    'igisubizo_a',
    'igisubizo_b',
    'igisubizo_c',
    'igisubizo_d',
    'igisubizo_gikwiye',
    'urwego',
    'icyiciro',
    'ibisobanuro'
]);

// Write sample rows with instructions
fputcsv($output, [
    'Itara ritukura ryerekana iki?',
    'Genda',
    'Hagarara',
    'Genda buhoro',
    'Hindukira iburyo',
    'B',
    'easy',
    '1',
    'Itara ritukura risobanura ko ugomba guhagarara burundu mbere yo kugera ahasangiye inzira.'
]);

fputcsv($output, [
    'Umuvuduko ntarengwa mu turere dutuwe ni uwuhe?',
    '30 km/h',
    '40 km/h',
    '50 km/h',
    '60 km/h',
    'B',
    'medium',
    '1',
    'Umuvuduko ntarengwa mu turere dutuwe ni 40 km/h.'
]);

fputcsv($output, [
    'Ni ryari ugomba gukoresha amatara y\'imodoka?',
    'Nijoro gusa',
    'Iyo imvura iguye n\'igihu',
    'Mu gihe cy\'umugoroba n\'igitondo',
    'Ibyose byavuzwe',
    'D',
    'hard',
    '1',
    'Amatara agomba gukoreshwa nijoro, mugihe kiboneka nabi, ndetse no mugihe cy\'umugoroba n\'igitondo.'
]);

fclose($output);
exit;
?>