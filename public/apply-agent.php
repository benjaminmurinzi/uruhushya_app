<?php
/**
 * Agent Application Form
 * 
 * Marketing agents can apply to earn commissions by referring students
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';

// If already logged in as agent, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'agent') {
    header('Location: ../agent/dashboard.php');
    exit;
}

$lang = isset($_GET['lang']) && $_GET['lang'] === 'english' ? 'english' : 'kinyarwanda';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $motivation = trim($_POST['motivation'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($id_number)) {
        $error = $lang === 'english' ? 'Required fields are missing' : 'Ibisabwa ntibibereye';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang === 'english' ? 'Invalid email address' : 'Email ntiyemewe';
    } elseif (strlen($password) < 6) {
        $error = $lang === 'english' ? 'Password must be at least 6 characters' : 'Ijambo ry\'ibanga rigomba kuba rifite imibare 6';
    } elseif ($password !== $confirm_password) {
        $error = $lang === 'english' ? 'Passwords do not match' : 'Amagambo y\'ibanga ntahuye';
    } else {
        // Check if email already exists
        $check_email = db_fetch("SELECT user_id FROM users WHERE email = ?", [$email]);
        
        if ($check_email) {
            $error = $lang === 'english' ? 'Email already registered' : 'Email yemeyewe';
        } else {
            try {
                db_query("START TRANSACTION");
                
                // Generate unique agent code
                $agent_code = 'AG' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                
                // Ensure agent code is unique
                while (db_fetch("SELECT user_id FROM users WHERE agent_code = ?", [$agent_code])) {
                    $agent_code = 'AG' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                }
                
                // Create user account
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_user = "INSERT INTO users (email, password_hash, full_name, phone, role, agent_code, status, language_preference, created_at)
                               VALUES (?, ?, ?, ?, 'agent', ?, 'pending', ?, NOW())";
                
                db_query($insert_user, [$email, $password_hash, $full_name, $phone, $agent_code, $lang]);
                $user_id = db_last_id();
                
                // Create agent application
                $insert_application = "INSERT INTO agent_applications 
                                      (agent_id, full_name, email, phone, id_number, address, city, district, 
                                       experience, motivation, agent_code, status, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                db_query($insert_application, [
                    $user_id, $full_name, $email, $phone, $id_number, 
                    $address, $city, $district, $experience, $motivation, $agent_code
                ]);
                
                db_query("COMMIT");
                
                $success = $lang === 'english' 
                    ? 'Application submitted successfully! Your agent code is: <strong>' . $agent_code . '</strong>. We will review and contact you within 2-3 business days.' 
                    : 'Icyifuzo cyoherejwe neza! Kode yanyu ni: <strong>' . $agent_code . '</strong>. Tuzakibona maze tuguhagararire mu minsi 2-3 y\'akazi.';
                
                $_POST = [];
                
            } catch (Exception $e) {
                db_query("ROLLBACK");
                $error = $lang === 'english' ? 'Application failed. Please try again.' : 'Icyifuzo cyanze. Ongera ugerageze.';
                error_log("Agent application error: " . $e->getMessage());
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
    <title><?php echo $lang === 'english' ? 'Agent Application' : 'Gusaba Kuba Umuhagarariye'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #FF6F00;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .benefits-box {
            background: #fff3e0;
            border-left: 4px solid #FF6F00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .benefits-box h5 {
            color: #FF6F00;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .benefits-box ul {
            margin-bottom: 0;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #FF6F00;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn-submit {
            background: #FF6F00;
            color: white;
            padding: 15px 50px;
            border-radius: 25px;
            font-weight: 700;
            border: none;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #E65100;
            color: white;
        }
        
        .lang-switcher {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .lang-switcher a {
            margin: 0 10px;
            color: #FF6F00;
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
                <i class="fas fa-user-tie"></i>
                <?php echo $lang === 'english' ? 'Agent Application' : 'Gusaba Kuba Umuhagarariye'; ?>
            </h1>
            <p class="page-subtitle">
                <?php echo $lang === 'english' 
                    ? 'Earn commission by referring students to our platform' 
                    : 'Injiza amafaranga mu gushyikiriza abanyeshuri kuri iyi platform'; ?>
            </p>
            
            <!-- Benefits Box -->
            <div class="benefits-box">
                <h5>
                    <i class="fas fa-gift"></i>
                    <?php echo $lang === 'english' ? 'Agent Benefits:' : 'Inyungu z\'Umuhagarariye:'; ?>
                </h5>
                <ul>
                    <li><?php echo $lang === 'english' ? 'Earn 10% commission on every student subscription' : 'Injiza 10% ku buri munyeshuri wishyuza'; ?></li>
                    <li><?php echo $lang === 'english' ? 'Monthly payouts via Mobile Money or Bank Transfer' : 'Ubwishyu bwa buri kwezi binyuze kuri Mobile Money cyangwa Banki'; ?></li>
                    <li><?php echo $lang === 'english' ? 'Track your earnings in real-time' : 'Kureba amafaranga yawe mu gihe nyacyo'; ?></li>
                    <li><?php echo $lang === 'english' ? 'Work from anywhere, anytime' : 'Kora aho ushaka, igihe cyose'; ?></li>
                    <li><?php echo $lang === 'english' ? 'Get your unique agent code instantly' : 'Habona kode yawe y\'umuhagarariye ako kanya'; ?></li>
                </ul>
            </div>
            
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
            <form method="POST" id="agentApplicationForm">
                
                <!-- Personal Information -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-user"></i>
                        <?php echo $lang === 'english' ? 'Personal Information' : 'Amakuru Bwite'; ?>
                    </h5>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'Full Name' : 'Amazina Yombi'; ?> *</label>
                        <input type="text" class="form-control" name="full_name" required 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Email Address' : 'Aderesi ya Email'; ?> *</label>
                                <input type="email" class="form-control" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo $lang === 'english' ? 'Phone Number' : 'Numero ya Telefone'; ?> *</label>
                                <input type="tel" class="form-control" name="phone" required
                                       placeholder="07XXXXXXXX"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'ID Number' : 'Numero y\'Indangamuntu'; ?> *</label>
                        <input type="text" class="form-control" name="id_number" required
                               placeholder="1199XXXXXXXXXX"
                               value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Location -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo $lang === 'english' ? 'Location' : 'Aho Uherereye'; ?>
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
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Experience & Motivation -->
                <div class="form-section">
                    <h5 class="section-title">
                        <i class="fas fa-briefcase"></i>
                        <?php echo $lang === 'english' ? 'Experience & Motivation' : 'Uburambe n\'Impamvu'; ?>
                    </h5>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'Previous Marketing Experience' : 'Uburambe mu Kwamamaza'; ?></label>
                        <textarea class="form-control" name="experience" rows="3"
                                  placeholder="<?php echo $lang === 'english' ? 'Tell us about your marketing or sales experience' : 'Tubwire ku burambe bwawe mu kwamamaza cyangwa kugurisha'; ?>"><?php echo htmlspecialchars($_POST['experience'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo $lang === 'english' ? 'Why do you want to become an agent?' : 'Kuki ushaka kuba umuhagarariye?'; ?></label>
                        <textarea class="form-control" name="motivation" rows="3"
                                  placeholder="<?php echo $lang === 'english' ? 'Tell us your motivation' : 'Tubwire impamvu yawe'; ?>"><?php echo htmlspecialchars($_POST['motivation'] ?? ''); ?></textarea>
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