<?php
/**
 * Change Admin Language Preference
 * 
 * Updates admin's language preference
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = isset($_POST['language']) ? clean_input($_POST['language']) : 'english';
    $redirect = isset($_POST['redirect']) ? clean_input($_POST['redirect']) : 'dashboard.php';
    
    // Validate language
    if (!in_array($language, ['english', 'kinyarwanda'])) {
        $language = 'english';
    }
    
    // Update admin language preference
    $sql = "UPDATE users SET language_preference = ?, updated_at = NOW() WHERE user_id = ?";
    db_query($sql, [$language, $admin_id]);
    
    // Update session
    $_SESSION['user_language'] = $language;
    
    // Redirect back
    redirect($redirect);
} else {
    redirect('dashboard.php');
}
?>