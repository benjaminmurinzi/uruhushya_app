<?php
/**
 * Add Student to School
 * 
 * School can add individual students
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// ==========================================
// HELPER FUNCTIONS (Must be before any use)
// ==========================================

// Flash message functions
if (!function_exists('has_flash_message')) {
    function has_flash_message() {
        return isset($_SESSION['flash_message']);
    }
}

if (!function_exists('get_flash_message')) {
    function get_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }
}

// Email validation
if (!function_exists('is_valid_email')) {
    function is_valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Check if email exists in database
if (!function_exists('email_exists')) {
    function email_exists($email) {
        $result = db_fetch("SELECT user_id FROM users WHERE email = ?", [$email]);
        return $result !== false;
    }
}

// Phone validation (Rwanda format: 07XXXXXXXX)
if (!function_exists('is_valid_phone')) {
    function is_valid_phone($phone) {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        // Check if it's 10 digits starting with 07
        return preg_match('/^07[0-9]{8}$/', $phone);
    }
}

// Clean user input
if (!function_exists('clean_input')) {
    function clean_input($data) {
        if (is_array($data)) {
            return array_map('clean_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// Hash password
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

// Log activity
if (!function_exists('log_activity')) {
    function log_activity($user_id, $activity_type, $description, $metadata = []) {
        $sql = "INSERT INTO activity_log (user_id, activity_type, description, ip_address, user_agent, metadata, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        
        try {
            db_query($sql, [
                $user_id,
                $activity_type,
                $description,
                $ip_address,
                $user_agent,
                $metadata_json
            ]);
        } catch (Exception $e) {
            // Log silently fails if activity_log table doesn't exist
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}

// ==========================================
// MAIN CODE STARTS HERE
// ==========================================

// Require school login
require_role('school');

$school = get_logged_in_user();
$school_id = get_user_id();

// Get school's subscription
$subscription = db_fetch(
    "SELECT * FROM subscriptions 
     WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() 
     ORDER BY created_at DESC LIMIT 1",
    [$school_id]
);

// Set student limit based on subscription
$student_limit = 0;
if ($subscription) {
    $sub_type = $subscription['subscription_type'];
    if ($sub_type == 'basic') {
        $student_limit = 50;
    } elseif ($sub_type == 'standard') {
        $student_limit = 150;
    } elseif ($sub_type == 'premium') {
        $student_limit = 999999;
    } else {
        $student_limit = 50; // Default
    }
}

// Get current student count
$current_students = db_fetch(
    "SELECT COUNT(*) as count FROM school_students WHERE school_id = ? AND status = 'active'", 
    [$school_id]
)['count'] ?? 0;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $language = clean_input($_POST['language'] ?? 'kinyarwanda');
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!is_valid_email($email)) {
        $error = 'Invalid email address';
    } elseif (email_exists($email)) {
        $error = 'Email already registered';
    } elseif (!is_valid_phone($phone)) {
        $error = 'Invalid phone number (format: 07XXXXXXXX)';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif ($current_students >= $student_limit) {
        $error = 'Student limit reached. Please upgrade your subscription.';
    } else {
        try {
            // Start transaction
            db_query("START TRANSACTION");
            
            // Create student account
            $hashed_password = hash_password($password);
            
            // Use password_hash column name
            $insert_user = "INSERT INTO users (full_name, email, phone, password_hash, role, language_preference, status, created_at)
                           VALUES (?, ?, ?, ?, 'student', ?, 'active', NOW())";
            
            db_query($insert_user, [$full_name, $email, $phone, $hashed_password, $language]);
            $student_id = db_last_id();
            
            // Link student to school
            $link_student = "INSERT INTO school_students (school_id, student_id, status, created_at)
                            VALUES (?, ?, 'active', NOW())";
            
            db_query($link_student, [$school_id, $student_id]);
            
            // NO TRIAL - Students must subscribe to access full exams
            // They get 3 free practice quizzes instead
            
            // Log activity
            log_activity($school_id, 'student_added', "Added student: {$full_name} (ID: {$student_id})");
            
            // Send welcome email to student
            try {
                send_template_email(
                    'school_student_welcome',
                    $email,
                    $language,
                    [
                        'full_name' => $full_name,
                        'school_name' => $school['full_name'],
                        'email' => $email,
                        'password' => $password,
                        'login_url' => APP_URL . '/public/login.php'
                    ],
                    $student_id
                );
            } catch (Exception $e) {
                // Email failure shouldn't stop student creation
                error_log("Email send error: " . $e->getMessage());
            }
            
            // Commit transaction
            db_query("COMMIT");
            
            $success = 'Student added successfully! Login credentials have been sent to their email. Student has full access to all exams under your school subscription.';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            db_query("ROLLBACK");
            $error = 'Failed to add student. Please try again.';
            error_log("Add student error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --school-color: #7B1FA2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: var(--school-color);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .top-nav .navbar-brand {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .top-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            margin: 0 10px;
            font-weight: 600;
        }
        
        .main-content {
            margin-top: 80px;
            padding: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--school-color);
            margin-bottom: 30px;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .limit-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .limit-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .btn-add-student {
            background: var(--school-color);
            color: white;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 700;
            border: none;
        }
        
        .btn-add-student:hover {
            background: #6A1B8A;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-school"></i> <?php echo APP_NAME; ?> - School
                </a>
                
                <div class="d-flex align-items-center">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-users"></i> Students
                    </a>
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    
                    <div class="dropdown ml-3">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo explode(' ', $school['full_name'])[0]; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="../public/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1 class="page-title">
                <i class="fas fa-user-plus"></i> Add New Student
            </h1>

            <div class="form-card">
                <!-- Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Student Limit Info -->
                <div class="limit-info">
                    <strong><i class="fas fa-info-circle"></i> Student Capacity:</strong>
                    You have <strong><?php echo $current_students; ?></strong> out of 
                    <strong><?php echo $student_limit == 999999 ? 'Unlimited' : $student_limit; ?></strong> students.
                    
                    <?php if ($student_limit - $current_students <= 10 && $student_limit != 999999): ?>
                        <br><span class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Only <?php echo $student_limit - $current_students; ?> slots remaining!
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($current_students >= $student_limit): ?>
                    <div class="limit-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Student Limit Reached!</strong><br>
                        You have reached your student limit. Please 
                        <a href="../student/pricing.php">upgrade your subscription</a> to add more students.
                    </div>
                <?php endif; ?>

                <!-- Add Student Form -->
                <form method="POST" id="addStudentForm">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i> Full Name *
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="full_name" 
                            name="full_name" 
                            placeholder="Enter student's full name"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address *
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="student@example.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                        <small class="form-text text-muted">
                            Login credentials will be sent to this email
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number *
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="phone" 
                            name="phone" 
                            placeholder="07XXXXXXXX"
                            pattern="[0-9]{10}"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="language">
                            <i class="fas fa-language"></i> Preferred Language *
                        </label>
                        <select class="form-control" id="language" name="language" required>
                            <option value="kinyarwanda" <?php echo ($_POST['language'] ?? '') === 'kinyarwanda' ? 'selected' : ''; ?>>
                                Kinyarwanda
                            </option>
                            <option value="english" <?php echo ($_POST['language'] ?? '') === 'english' ? 'selected' : ''; ?>>
                                English
                            </option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i> Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Minimum 6 characters"
                                    minlength="6"
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirm Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Re-enter password"
                                    minlength="6"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-gift"></i> 
                        <strong>Free Trial:</strong> Students added by your school automatically get full access to all exams under your school's subscription plan. No additional payment required from students!
                    </div>

                    <div class="text-center mt-4">
                        <button 
                            type="submit" 
                            class="btn btn-add-student"
                            <?php echo $current_students >= $student_limit ? 'disabled' : ''; ?>
                        >
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary ml-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Password match validation
            $('#addStudentForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>