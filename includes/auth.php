<?php
/**
 * Authentication & Authorization Functions
 * 
 * Handles user login, logout, session management, and role-based access control
 * 
 * Developer: Benjamin NIYOMURINZI
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ============================================
// SESSION MANAGEMENT
// ============================================

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Get current logged-in user ID
 * 
 * @return int|null
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged-in user data
 * 
 * @return array|null
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $sql = "SELECT user_id, email, full_name, phone, role, status, school_id, agent_id, language_preference 
            FROM users 
            WHERE user_id = ? AND status = 'active'";
    
    return db_fetch($sql, [get_user_id()]);
}

/**
 * Get user role
 * 
 * @return string|null
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool
 */
function has_role($roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = get_user_role();
    
    if (is_array($roles)) {
        return in_array($user_role, $roles);
    }
    
    return $user_role === $roles;
}

/**
 * Require login (redirect if not logged in)
 * 
 * @param string $redirect_url URL to redirect to after login
 */
function require_login($redirect_url = null) {
    if (!is_logged_in()) {
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        }
        set_flash_message('warning', 'Please login to continue');
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Require specific role (redirect if user doesn't have role)
 * 
 * @param string|array $roles Required role(s)
 * @param string $error_message Custom error message
 */
function require_role($roles, $error_message = 'You do not have permission to access this page') {
    require_login();
    
    if (!has_role($roles)) {
        set_flash_message('error', $error_message);
        redirect(APP_URL . '/index.php');
    }
}

// ============================================
// LOGIN & LOGOUT
// ============================================

/**
 * Login user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array ['success' => bool, 'message' => string, 'user' => array]
 */
function login_user($email, $password) {
    // Fetch user by email
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $user = db_fetch($sql, [$email]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Your account is ' . $user['status'] . '. Please contact support.'
        ];
    }
    
    // Check subscription for students
    if ($user['role'] === 'student') {
        if (!has_active_subscription($user['user_id'])) {
            return [
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.'
            ];
        }
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_language'] = $user['language_preference'];
    
    // Update last login
    $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    db_query($update_sql, [$user['user_id']]);
    
    // Log activity
    log_activity($user['user_id'], 'login', 'User logged in');
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Logout user
 */
function logout_user() {
    if (is_logged_in()) {
        // Log activity
        log_activity(get_user_id(), 'logout', 'User logged out');
        
        // Destroy session
        session_unset();
        session_destroy();
    }
    
    redirect(APP_URL . '/login.php');
}

// ============================================
// USER REGISTRATION
// ============================================

/**
 * Register new student
 * 
 * @param array $data User data
 * @return array ['success' => bool, 'message' => string, 'user_id' => int]
 */
function register_student($data) {
    // Validate required fields
    $required = ['full_name', 'email', 'phone', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
        }
    }
    
    // Validate email
    if (!is_valid_email($data['email'])) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Validate phone
    if (!is_valid_phone($data['phone'])) {
        return ['success' => false, 'message' => 'Invalid phone number format'];
    }
    
    // Validate password
    $password_check = validate_password($data['password']);
    if (!$password_check['valid']) {
        return ['success' => false, 'message' => $password_check['message']];
    }
    
    // Check if email already exists
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    if (db_fetch($check_sql, [$data['email']])) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (email, password_hash, full_name, phone, role, status, agent_id, language_preference, created_at) 
            VALUES (?, ?, ?, ?, 'student', 'active', ?, ?, NOW())";
    
    $agent_id = $data['agent_id'] ?? null;
    $language = $data['language'] ?? 'kinyarwanda';
    
    try {
        db_query($sql, [
            $data['email'],
            $password_hash,
            $data['full_name'],
            $data['phone'],
            $agent_id,
            $language
        ]);
        
        $user_id = db_last_id();
        
        // Create trial subscription (1 day free)
        create_trial_subscription($user_id);
        
        // Log activity
        log_activity($user_id, 'registration', 'Student registered successfully');
        
        return [
            'success' => true,
            'message' => 'Registration successful! You can now login.',
            'user_id' => $user_id
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// ============================================
// SUBSCRIPTION HELPERS
// ============================================

/**
 * Check if user has active subscription
 * 
 * @param int $user_id User ID
 * @return bool
 */
function has_active_subscription($user_id) {
    $sql = "SELECT subscription_id FROM subscriptions 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date >= CURDATE() 
            LIMIT 1";
    
    return db_fetch($sql, [$user_id]) !== false;
}

/**
 * Create trial subscription for new user
 * 
 * @param int $user_id User ID
 * @return bool
 */
function create_trial_subscription($user_id) {
    $sql = "INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, status, amount, created_at)
            VALUES (?, 'trial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'active', 0, NOW())";
    
    return db_query($sql, [$user_id]) !== false;
}

/**
 * Get user's current subscription
 * 
 * @param int $user_id User ID
 * @return array|null
 */
function get_user_subscription($user_id) {
    $sql = "SELECT * FROM subscriptions 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date >= CURDATE() 
            ORDER BY end_date DESC 
            LIMIT 1";
    
    return db_fetch($sql, [$user_id]);
}

// ============================================
// ACTIVITY LOGGING
// ============================================

/**
 * Log user activity
 * 
 * @param int $user_id User ID
 * @param string $activity_type Activity type
 * @param string $description Description
 * @param array $metadata Additional metadata (optional)
 * @return bool
 */
function log_activity($user_id, $activity_type, $description, $metadata = []) {
    $sql = "INSERT INTO activity_log (user_id, activity_type, description, ip_address, user_agent, metadata, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
    
    return db_query($sql, [
        $user_id,
        $activity_type,
        $description,
        $ip_address,
        $user_agent,
        $metadata_json
    ]) !== false;
}

?>