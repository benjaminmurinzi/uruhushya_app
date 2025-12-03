<?php
/**
 * School Application Form
 * 
 * Driving schools can apply to get access to the platform
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';

// If already logged in as school, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'school') {
    header('Location: ../school/dashboard.php');
    exit;
}

$lang = isset($_GET['lang']) && $_GET['lang'] === 'english' ? 'english' : 'kinyarwanda';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $tin_number = trim($_POST['tin_number'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $total_students = (int)($_POST['total_students'] ?? 0);
    $total_instructors = (int)($_POST['total_instructors'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($school_name) || empty($contact_person) || empty($contact_email) || empty($contact_phone)) {
        $error = $lang === 'english' ? 'Required fields are missing' : 'Ibisabwa ntibibereye';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang === 'english' ? 'Invalid email address' : 'Email ntiyemewe';
    } elseif (strlen($password) < 6) {
        $error = $lang === 'english' ? 'Password must be at least 6 characters' : 'Ijambo ry\'ibanga rigomba kuba rifite imibare 6';
    } elseif ($password !== $confirm_password) {
        $error = $lang === 'english' ? 'Passwords do not match' : 'Amagambo y\'ibanga ntahuye';
    } else {
        // Check if email already exists
        $check_email = db_fetch("SELECT user_id FROM users WHERE email = ?", [$contact_email]);
        
        if ($check_email) {
            $error = $lang === 'english' ? 'Email already registered' : 'Email yemeyewe';
        } else {
            try {
                db_query("START TRANSACTION");
                
                // Create user account
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $school_code = 'SCH' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $insert_user = "INSERT INTO users (email, password_hash, full_name, phone, role, school_code, status, language_preference, created_at)
                               VALUES (?, ?, ?, ?, 'school', ?, 'pending', ?, NOW())";
                
                db_query($insert_user, [$contact_email, $password_hash, $school_name, $contact_phone, $school_code, $lang]);
                $user_id = db_last_id();
                
                // Create school application
                $insert_application = "INSERT INTO school_applications 
                                      (school_id, school_name, tin_number, contact_person, contact_email, contact_phone, 
                                       address, city, district, website, total_students, total_instructors, status, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                db_query($insert_application, [
                    $user_id, $school_name, $tin_number, $contact_person, $contact_email, $contact_phone,
                    $address, $city, $district, $website, $total_students, $total_instructors
                ]);
                
                db_query("COMMIT");
                
                $success = $lang === 'english' 
                    ? 'Application submitted successfully! We will review and contact you within 2-3 business days.' 
                    : 'Icyifuzo cyoherejwe neza! Tuzakibona maze tuguhagararire mu minsi 2-3 y\'akazi.';
                
                $_POST = [];
                
            } catch (Exception $e) {
                db_query("ROLLBACK");
                $error = $lang === 'english' ? 'Application failed. Please try again.' : 'Icyifuzo cyanze. Ongera ugerageze.';
                error_log("School application error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'School Application' : 'Gusaba Kuba Ishuri'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .application-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            text-align: center;
            color: #7B1FA2;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #7B1FA2;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn-submit {
            background: #7B1FA2;
            color: white;
            padding: 15px 50px;
            border-radius: 25px;
            font-weight: 700;
            border: none;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #6A1B8A;
            color: white;
        }
        
        .lang-switcher {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .lang-switcher a {
            margin: 0 10px;
            color: #7B1FA2;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="application-card">
            <!-- Language Switcher -->
            <div class="lang-switcher">
                <a href="?lang=kinyarwanda">Kinyarwanda</a> | 
                <a href="?lang=english">English</a>
            </div>
            
            <!-- Title -->
            <h1 class="page-title">
                <i class="fas fa-school"></i>
                <?php echo $lang === 'english' ? 'Driving School Application' : 'Gusaba Kuba Ishuri'; ?>
            </h1>
            <p class="page-subtitle">
                <?php echo $lang === 'english' 
                    ? 'Join our platform and manage your students efficiently' 
                    : 'Kwitondera kuri iyi platform maze ukore neza abanyeshuri bawe'; ?>
            </p>
            
            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <hr>
                    <a href="login.php" class="btn btn-success">
                        <?php echo $lang === 'english' ? 'Go to Login' : 'Jya ku Kwinjira'; ?>
                    </a>
                </div>
            <?php else: ?>
            
            <!-- Application Form -->
            <form method="POST" id="schoolApplicationForm">
                
                <!-- School Information -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-building"></i>
                        <?php echo $lang === 'english' ? 'School Information' : 'Amakuru y\'Ishuri'; ?>
                    </h5>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'School Name' : 'Izina ry\'Ishuri'; ?> *</label>
                        <input type="text" class="form-control" name="school_name" required 
                               value="<?php echo htmlspecialchars($_POST['school_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'TIN Number' : 'Numero ya TIN'; ?></label>
                                <input type="text" class="form-control" name="tin_number"
                                       value="<?php echo htmlspecialchars($_POST['tin_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Website (Optional)' : 'Website (Ntiyahora)'; ?></label>
                                <input type="url" class="form-control" name="website"
                                       value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-user"></i>
                        <?php echo $lang === 'english' ? 'Contact Information' : 'Amakuru yo Guhamagara'; ?>
                    </h5>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'Contact Person Name' : 'Izina ry\'Umuntu wo Guhamagara'; ?> *</label>
                        <input type="text" class="form-control" name="contact_person" required
                               value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Email Address' : 'Aderesi ya Email'; ?> *</label>
                                <input type="email" class="form-control" name="contact_email" required
                                       value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Phone Number' : 'Numero ya Telefone'; ?> *</label>
                                <input type="tel" class="form-control" name="contact_phone" required
                                       placeholder="07XXXXXXXX"
                                       value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Location -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo $lang === 'english' ? 'Location' : 'Aho Ishuri Riherereye'; ?>
                    </h5>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'Address' : 'Aderesi'; ?></label>
                        <input type="text" class="form-control" name="address"
                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'City' : 'Umujyi'; ?></label>
                                <input type="text" class="form-control" name="city"
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? 'Kigali'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'District' : 'Akarere'; ?></label>
                                <select class="form-control" name="district">
                                    <option value="">Select District</option>
                                    <option value="Gasabo">Gasabo</option>
                                    <option value="Kicukiro">Kicukiro</option>
                                    <option value="Nyarugenge">Nyarugenge</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-chart-bar"></i>
                        <?php echo $lang === 'english' ? 'School Statistics' : 'Imibare y\'Ishuri'; ?>
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Total Students' : 'Abanyeshuri Bose'; ?></label>
                                <input type="number" class="form-control" name="total_students" min="0"
                                       value="<?php echo htmlspecialchars($_POST['total_students'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Total Instructors' : 'Abarimu Bose'; ?></label>
                                <input type="number" class="form-control" name="total_instructors" min="0"
                                       value="<?php echo htmlspecialchars($_POST['total_instructors'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Login Credentials -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-lock"></i>
                        <?php echo $lang === 'english' ? 'Create Login Credentials' : 'Kora Amakuru yo Kwinjira'; ?>
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Password' : 'Ijambo ry\'Ibanga'; ?> *</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Confirm Password' : 'Emeza Ijambo ry\'Ibanga'; ?> *</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $lang === 'english' ? 'Submit Application' : 'Ohereza Icyifuzo'; ?>
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted">
                        <?php echo $lang === 'english' ? 'Already have an account?' : 'Usanzwe ufite konti?'; ?>
                        <a href="login.php"><?php echo $lang === 'english' ? 'Login here' : 'Injira hano'; ?></a>
                    </p>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>