<?php
/**
 * Login Page
 * 
 * Allows users to login to the system
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    $role = get_user_role();
    switch ($role) {
        case 'admin':
            redirect(APP_URL . '/../admin/dashboard.php');
            break;
        case 'school':
            redirect(APP_URL . '/../school/dashboard.php');
            break;
        case 'agent':
            redirect(APP_URL . '/../agent/dashboard.php');
            break;
        case 'student':
            redirect(APP_URL . '/../student/dashboard.php');
            break;
        default:
            redirect(APP_URL . '/index.php');
    }
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Attempt login
        $result = login_user($email, $password);
        
        if ($result['success']) {
            // Check if there's a redirect URL stored
            $redirect_url = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            
            // Redirect based on role
            $role = get_user_role();
            
            if ($redirect_url) {
                redirect($redirect_url);
            } else {
                switch ($role) {
                    case 'admin':
                        redirect(APP_URL . '/../admin/dashboard.php');
                        break;
                    case 'school':
                        redirect(APP_URL . '/../school/dashboard.php');
                        break;
                    case 'agent':
                        redirect(APP_URL . '/../agent/dashboard.php');
                        break;
                    case 'student':
                        redirect(APP_URL . '/../student/dashboard.php');
                        break;
                    default:
                        redirect(APP_URL . '/index.php');
                }
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Get language preference
$lang = $_GET['lang'] ?? 'kinyarwanda';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Login' : 'Injira'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2C3E96;  /* ikizamini.com blue */
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
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 28px;
        }
        
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 150, 0.15);
        }
        
        .btn-login {
            height: 50px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            background: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .input-icon .form-control {
            padding-left: 45px;
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

    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h2>
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </h2>
                <p>
                    <?php echo $lang === 'english' 
                        ? 'Driving License Exam Preparation' 
                        : 'Gukina Ikizamini cy\'Uruhushya rwo Gutwara'; ?>
                </p>
            </div>

            <!-- Body -->
            <div class="login-body">
                <!-- Error/Success Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="">
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <?php echo $lang === 'english' ? 'Email Address' : 'Aderesi ya Email'; ?>
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                placeholder="<?php echo $lang === 'english' ? 'Enter your email' : 'Andika email yawe'; ?>"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">
                            <?php echo $lang === 'english' ? 'Password' : 'Ijambo ry\'Ibanga'; ?>
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="<?php echo $lang === 'english' ? 'Enter your password' : 'Andika ijambo ry\'ibanga'; ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                            <label class="custom-control-label" for="remember">
                                <?php echo $lang === 'english' ? 'Remember me' : 'Nyibuka'; ?>
                            </label>
                        </div>
                        <a href="#" class="text-muted small">
                            <?php echo $lang === 'english' ? 'Forgot password?' : 'Wibagiwe ijambo ry\'ibanga?'; ?>
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn btn-primary btn-block btn-login">
                        <i class="fas fa-sign-in-alt"></i> 
                        <?php echo $lang === 'english' ? 'Login' : 'Injira'; ?>
                    </button>
                </form>

                <!-- Register Link -->
                <div class="register-link">
                    <p class="mb-0">
                        <?php echo $lang === 'english' ? "Don't have an account?" : "Ntufite konti?"; ?>
                        <a href="register.php">
                            <?php echo $lang === 'english' ? 'Register here' : 'Iyandikishe hano'; ?>
                        </a>
                    </p>
                    <p class="mt-2 mb-0">
                        <small class="text-muted">
                            <?php echo $lang === 'english' ? 'Are you a driving school or agent?' : 'Uri ishuri ry\'umuhanda cyangwa agent?'; ?>
                            <a href="apply-school.php">
                                <?php echo $lang === 'english' ? 'Apply here' : 'Saba hano'; ?>
                            </a>
                        </small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Test Credentials (Development Only) -->
        <?php if (ENVIRONMENT === 'development'): ?>
            <div class="card mt-3" style="background: rgba(255, 255, 255, 0.9);">
                <div class="card-body">
                    <h6 class="text-danger"><i class="fas fa-info-circle"></i> Test Credentials (Dev Only)</h6>
                    <small>
                        <strong>Admin:</strong> admin@uruhushya.rw / Admin@2025<br>
                        <strong>School:</strong> school@example.rw / School@2025<br>
                        <strong>Agent:</strong> agent@example.rw / Agent@2025<br>
                        <strong>Student:</strong> student@example.rw / Student@2025
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>