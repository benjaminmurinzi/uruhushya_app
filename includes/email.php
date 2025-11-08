<?php
/**
 * Email Sending System
 * 
 * Handles sending emails using templates from database
 * 
 * Developer: Benjamin NIYOMURINZI
 */

/**
 * Send email using template
 * 
 * @param string $template_name Template name from email_templates table
 * @param string $recipient_email Recipient email address
 * @param string $language Language ('english' or 'kinyarwanda')
 * @param array $variables Variables to replace in template
 * @return array ['success' => bool, 'message' => string]
 */
function send_template_email($template_name, $recipient_email, $language, $variables = []) {
    // Get template from database
    $sql = "SELECT * FROM email_templates WHERE template_name = ? AND status = 'active' LIMIT 1";
    $template = db_fetch($sql, [$template_name]);
    
    if (!$template) {
        return ['success' => false, 'message' => 'Email template not found'];
    }
    
    // Get subject and body based on language
    $subject = $language === 'english' ? $template['subject_en'] : $template['subject_rw'];
    $body = $language === 'english' ? $template['body_en'] : $template['body_rw'];
    
    // Replace variables in subject and body
    foreach ($variables as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $subject = str_replace($placeholder, $value, $subject);
        $body = str_replace($placeholder, $value, $body);
    }
    
    // Send email
    $result = send_email($recipient_email, $subject, $body);
    
    // Log notification
    log_notification(
        null,
        'email',
        $recipient_email,
        $subject,
        $body,
        $result['success'] ? 'sent' : 'failed',
        $result['success'] ? null : $result['message']
    );
    
    return $result;
}

/**
 * Send email (raw)
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body
 * @return array ['success' => bool, 'message' => string]
 */
function send_email($to, $subject, $message) {
    // Validate email
    if (!is_valid_email($to)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Email headers
    $headers = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'Reply-To: ' . SITE_EMAIL,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    // For development, we'll just log the email instead of sending
    if (ENVIRONMENT === 'development') {
        // In development, log email to file for testing
        $log_message = "======================\n";
        $log_message .= "TO: $to\n";
        $log_message .= "SUBJECT: $subject\n";
        $log_message .= "MESSAGE:\n$message\n";
        $log_message .= "======================\n\n";
        
        // Create logs directory if it doesn't exist
        if (!file_exists(BASE_PATH . '/logs')) {
            mkdir(BASE_PATH . '/logs', 0755, true);
        }
        
        file_put_contents(BASE_PATH . '/logs/emails.log', $log_message, FILE_APPEND);
        
        return [
            'success' => true,
            'message' => 'Email logged (development mode)'
        ];
    }
    
    // Send email using PHP mail() function
    $success = mail($to, $subject, $message, implode("\r\n", $headers));
    
    return [
        'success' => $success,
        'message' => $success ? 'Email sent successfully' : 'Failed to send email'
    ];
}

/**
 * Log notification to database
 * 
 * @param int|null $user_id User ID
 * @param string $type Notification type ('email' or 'sms')
 * @param string $recipient Recipient (email or phone)
 * @param string $subject Subject
 * @param string $message Message
 * @param string $status Status ('pending', 'sent', 'failed')
 * @param string|null $error_message Error message if failed
 * @return bool
 */
function log_notification($user_id, $type, $recipient, $subject, $message, $status, $error_message = null) {
    $sql = "INSERT INTO notifications (user_id, notification_type, recipient, subject, message, status, error_message, created_at, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), " . ($status === 'sent' ? 'NOW()' : 'NULL') . ")";
    
    return db_query($sql, [
        $user_id,
        $type,
        $recipient,
        $subject,
        $message,
        $status,
        $error_message
    ]) !== false;
}

?>