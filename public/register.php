<?php
/**
 * Student Registration Page
 * 
 * Allows new students to register for free trial
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// If already logged in, redirect
if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $language = clean_input($_POST['language'] ?? 'kinyarwanda');
    $agent_code = clean_input($_POST['agent_code'] ?? '');
    $terms = isset($_POST['terms']);
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!$terms) {
        $error = 'You must accept the terms and conditions';
    } else {
        // Check if agent code is provided
        $agent_id = null;
        if (!empty($agent_code)) {
            $agent = db_fetch("SELECT agent_id FROM agents WHERE agent_code = ? AND status = 'active'", [$agent_code]);
            if ($agent) {
                $agent_id = $agent['agent_id'];
            } else {
                $error = 'Invalid agent code';
            }
        }
        
        if (empty($error)) {
            // Register student
            $result = register_student([
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'language' => $language,
                'agent_id' => $agent_id
            ]);
            
            if ($result['success']) {
                // Send welcome email
                $subscription = get_user_subscription($result['user_id']);
                
                send_template_email(
                    'student_registration_welcome',
                    $email,
                    $language,
                    [
                        'full_name' => $full_name,
                        'email' => $email,
                        'subscription_type' => '1-day trial',
                        'subscription_end_date' => $subscription ? format_date($subscription['end_date']) : date('Y-m-d', strtotime('+1 day')),
                        'login_url' => APP_URL . '/login.php'
                    ]
                );
                
                $success = $result['message'];
                
                // Clear form
                $full_name = $email = $phone = $agent_code = '';
            } else {
                $error = $result['message'];
            }
        }
    }
}

$lang = $_GET['lang'] ?? 'kinyarwanda';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Register' : 'Iyandikishe'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS (same as login.php) -->
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
            --accent-color: #4169E1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        .register-container {
            max-width: 550px;
            width: 100%;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .register-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 28px;
        }
        
        .register-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .register-body {
            padding: 40px 30px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            height: 45px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 150, 0.15);
        }
        
        .btn-register {
            height: 50px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            background: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 150, 0.3);
        }
        
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .language-switcher .btn {
            background: white;
            color: var(--primary-color);
            border: 2px solid white;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .language-switcher .btn:hover {
            background: transparent;
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .trial-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher">
        <?php if ($lang === 'kinyarwanda'): ?>
            <a href="?lang=english" class="btn btn-sm">
                <i class="fas fa-globe"></i> English
            </a>
        <?php else: ?>
            <a href="?lang=kinyarwanda" class="btn btn-sm">
                <i class="fas fa-globe"></i> Kinyarwanda
            </a>
        <?php endif; ?>
    </div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <h2>
                    <i class="fas fa-user-plus"></i> 
                    <?php echo $lang === 'english' ? 'Create Account' : 'Fungura Konti'; ?>
                </h2>
                <p>
                    <?php echo $lang === 'english' 
                        ? 'Start your 1-day free trial!' 
                        : 'Tangira iminsi 1 y\'ubuntu!'; ?>
                </p>
                <span class="trial-badge">
                    <i class="fas fa-gift"></i> 
                    <?php echo $lang === 'english' ? '1 Day FREE Trial' : 'Umunsi 1 W\'UBUNTU'; ?>
                </span>
            </div>

            <!-- Body -->
            <div class="register-body">
                <!-- Error/Success Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <hr>
                        <a href="login.php" class="btn btn-success btn-sm">
                            <?php echo $lang === 'english' ? 'Login Now' : 'Injira Nonaha'; ?> →
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <?php if (empty($success)): ?>
                <form method="POST" action="" id="registerForm">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">
                            <?php echo $lang === 'english' ? 'Full Name' : 'Amazina Yombi'; ?> *
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="full_name" 
                            name="full_name" 
                            placeholder="<?php echo $lang === 'english' ? 'Enter your full name' : 'Andika amazina yawe yombi'; ?>"
                            value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                            required
                        >
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <?php echo $lang === 'english' ? 'Email Address' : 'Aderesi ya Email'; ?> *
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="<?php echo $lang === 'english' ? 'your.email@example.com' : 'email.yawe@example.com'; ?>"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            required
                        >
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">
                            <?php echo $lang === 'english' ? 'Phone Number' : 'Numero ya Telefoni'; ?> *
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="phone" 
                            name="phone" 
                            placeholder="+250788123456"
                            value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                            required
                        >
                        <small class="text-muted">
                            <?php echo $lang === 'english' ? 'Format: +250788123456' : 'Imiterere: +250788123456'; ?>
                        </small>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">
                            <?php echo $lang === 'english' ? 'Password' : 'Ijambo ry\'Ibanga'; ?> *
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="<?php echo $lang === 'english' ? 'At least 8 characters' : 'Nibura inyuguti 8'; ?>"
                            required
                        >
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">
                            <?php echo $lang === 'english' ? 'Confirm Password' : 'Emeza Ijambo ry\'Ibanga'; ?> *
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="<?php echo $lang === 'english' ? 'Re-enter password' : 'Ongeramo ijambo ry\'ibanga'; ?>"
                            required
                        >
                        <div id="passwordMatch" class="password-strength"></div>
                    </div>

                    <!-- Language Preference -->
                    <div class="form-group">
                        <label for="language">
                            <?php echo $lang === 'english' ? 'Preferred Language' : 'Ururimi Uhitamo'; ?>
                        </label>
                        <select class="form-control" id="language" name="language">
                            <option value="kinyarwanda" <?php echo ($language ?? 'kinyarwanda') === 'kinyarwanda' ? 'selected' : ''; ?>>
                                Kinyarwanda
                            </option>
                            <option value="english" <?php echo ($language ?? '') === 'english' ? 'selected' : ''; ?>>
                                English
                            </option>
                        </select>
                    </div>

                    <!-- Agent Code (Optional) -->
                    <div class="form-group">
                        <label for="agent_code">
                            <?php echo $lang === 'english' ? 'Agent Code (Optional)' : 'Kode ya Agent (Ntabwo Ari Ngombwa)'; ?>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="agent_code" 
                            name="agent_code" 
                            placeholder="<?php echo $lang === 'english' ? 'e.g., AG001' : 'urugero: AG001'; ?>"
                            value="<?php echo htmlspecialchars($agent_code ?? ''); ?>"
                        >
                        <small class="text-muted">
                            <?php echo $lang === 'english' 
                                ? 'Enter agent code if you were referred' 
                                : 'Andika kode ya agent niba wabonywe na we'; ?>
                        </small>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="terms" name="terms" required>
                            <label class="custom-control-label" for="terms">
                                <?php echo $lang === 'english' 
                                    ? 'I accept the <a href="#" target="_blank">Terms & Conditions</a>' 
                                    : 'Nemera <a href="#" target="_blank">Amategeko n\'Amabwiriza</a>'; ?>
                            </label>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn btn-primary btn-block btn-register">
                        <i class="fas fa-user-plus"></i> 
                        <?php echo $lang === 'english' ? 'Register Now' : 'Iyandikishe Nonaha'; ?>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Login Link -->
                <div class="login-link">
                    <p class="mb-0">
                        <?php echo $lang === 'english' ? 'Already have an account?' : 'Usanzwe ufite konti?'; ?>
                        <a href="login.php">
                            <?php echo $lang === 'english' ? 'Login here' : 'Injira hano'; ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Strength & Validation -->
    <script>
        // Password strength checker
        $('#password').on('keyup', function() {
            const password = $(this).val();
            const strengthDiv = $('#passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.html('');
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            if (strength <= 2) {
                strengthDiv.html('<span class="strength-weak">Weak password</span>');
            } else if (strength <= 4) {
                strengthDiv.html('<span class="strength-medium">Medium password</span>');
            } else {
                strengthDiv.html('<span class="strength-strong">Strong password</span>');
            }
        });
        
        // Password match checker
        $('#confirm_password').on('keyup', function() {
            const password = $('#password').val();
            const confirm = $(this).val();
            const matchDiv = $('#passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.html('');
                return;
            }
            
            if (password === confirm) {
                matchDiv.html('<span class="strength-strong">✓ Passwords match</span>');
            } else {
                matchDiv.html('<span class="strength-weak">✗ Passwords do not match</span>');
            }
        });
        
        // Form validation
        $('#registerForm').on('submit', function(e) {
            const password = $('#password').val();
            const confirm = $('#confirm_password').val();
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
