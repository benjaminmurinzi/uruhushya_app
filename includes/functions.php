<?php
/**
 * Helper Functions
 * 
 * Common functions used throughout the application
 * 
 * Developer: Benjamin NIYOMURINZI
 */

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data Input data
 * @return string Sanitized data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Rwanda format)
 * 
 * @param string $phone Phone number
 * @return bool
 */
function is_valid_phone($phone) {
    // Rwanda phone: +250788123456 or 0788123456
    $pattern = '/^(\+?250|0)?[7][0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? 'Password is strong' : implode('. ', $errors)
    ];
}

/**
 * Generate CSRF token
 * 
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string
 */
function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// ============================================
// DATE & TIME FUNCTIONS
// ============================================

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Format (default: 'd M Y')
 * @return string
 */
function format_date($date, $format = 'd M Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @return string
 */
function format_datetime($datetime) {
    if (empty($datetime)) return '';
    return date('d M Y H:i', strtotime($datetime));
}

/**
 * Get time ago (e.g., "2 hours ago")
 * 
 * @param string $datetime Datetime string
 * @return string
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' seconds ago';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' minutes ago';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' hours ago';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' days ago';
    } else {
        return format_date($datetime);
    }
}

// ============================================
// STRING FUNCTIONS
// ============================================

/**
 * Truncate text to specific length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add (default: '...')
 * @return string
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate slug from text
 * 
 * @param string $text Text to convert
 * @return string
 */
function create_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// ============================================
// FILE FUNCTIONS
// ============================================

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get file extension
 * 
 * @param string $filename Filename
 * @return string
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $allowed_types Allowed file extensions
 * @param int $max_size Maximum file size in bytes
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_file_upload($file, $allowed_types, $max_size = UPLOAD_MAX_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'File upload error'];
    }
    
    $extension = get_file_extension($file['name']);
    
    if (!in_array($extension, $allowed_types)) {
        return ['valid' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'message' => 'File too large. Maximum: ' . format_file_size($max_size)];
    }
    
    return ['valid' => true, 'message' => 'File is valid'];
}

// ============================================
// NUMBER FUNCTIONS
// ============================================

/**
 * Format currency (Rwandan Francs)
 * 
 * @param float $amount Amount
 * @return string
 */
function format_currency($amount) {
    return number_format($amount, 0, '.', ',') . ' RWF';
}

/**
 * Format percentage
 * 
 * @param float $value Value
 * @param int $decimals Decimal places
 * @return string
 */
function format_percentage($value, $decimals = 1) {
    return number_format($value, $decimals) . '%';
}

// ============================================
// REDIRECT & URL FUNCTIONS
// ============================================

/**
 * Redirect to another page
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get current page URL
 * 
 * @return string
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Build URL with query parameters
 * 
 * @param string $base_url Base URL
 * @param array $params Query parameters
 * @return string
 */
function build_url($base_url, $params = []) {
    if (empty($params)) {
        return $base_url;
    }
    return $base_url . '?' . http_build_query($params);
}

// ============================================
// ALERT/MESSAGE FUNCTIONS
// ============================================

/**
 * Set flash message in session
 * 
 * @param string $type Type (success, error, warning, info)
 * @param string $message Message text
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 * 
 * @return string
 */
function display_flash_message() {
    $message = get_flash_message();
    if (!$message) {
        return '';
    }
    
    $alert_class = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alert_class[$message['type']] ?? 'alert-info';
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . clean_input($message['message']) . '
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>';
}

// ============================================
// LANGUAGE FUNCTIONS
// ============================================

/**
 * Get user's preferred language
 * 
 * @return string
 */
function get_user_language() {
    if (isset($_SESSION['user_language'])) {
        return $_SESSION['user_language'];
    }
    return DEFAULT_LANGUAGE;
}

/**
 * Set user's preferred language
 * 
 * @param string $language Language code
 */
function set_user_language($language) {
    if (in_array($language, AVAILABLE_LANGUAGES)) {
        $_SESSION['user_language'] = $language;
    }
}

/**
 * Get text based on language
 * 
 * @param string $text_en English text
 * @param string $text_rw Kinyarwanda text
 * @return string
 */
function get_translated_text($text_en, $text_rw) {
    $language = get_user_language();
    return $language === 'english' ? $text_en : $text_rw;
}

?>