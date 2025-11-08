<?php
/**
 * Student Profile Page
 * 
 * View and edit student profile information
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$user_id = get_user_id();
$lang = $user['language_preference'] ?? 'kinyarwanda';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $language = clean_input($_POST['language'] ?? 'kinyarwanda');
    
    // Validate
    if (empty($full_name) || empty($phone)) {
        $error = $lang === 'english' ? 'Please fill in all fields' : 'Uzuza ahantu hose';
    } elseif (!is_valid_phone($phone)) {
        $error = $lang === 'english' ? 'Invalid phone number format' : 'Numero ya telefoni ntago imeze neza';
    } else {
        // Update user
        $sql = "UPDATE users SET full_name = ?, phone = ?, language_preference = ?, updated_at = NOW() WHERE user_id = ?";
        $result = db_query($sql, [$full_name, $phone, $language, $user_id]);
        
        if ($result) {
            // Update session
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_language'] = $language;
            
            // Log activity
            log_activity($user_id, 'profile_update', 'User updated profile information');
            
            $success = $lang === 'english' ? 'Profile updated successfully!' : 'Umwirondoro wawe wahinduwe neza!';
            
            // Refresh user data
            $user = get_logged_in_user();
        } else {
            $error = $lang === 'english' ? 'Failed to update profile' : 'Ntibyashobotse guhindura umwirondoro';
        }
    }
}

// Get subscription info
$subscription = get_user_subscription($user_id);

// Get user statistics
$stats = db_fetch("SELECT * FROM student_analytics WHERE user_id = ?", [$user_id]);

$page_title = $lang === 'english' ? 'My Profile' : 'Umwirondoro Wanjye';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: var(--primary-color);
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
        }
        
        .user-menu .dropdown-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 20px;
            font-weight: 700;
        }
        
        .info-row {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 18px;
            color: #333;
            margin-top: 5px;
        }
        
        .badge-subscription {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .btn-update {
            background: var(--primary-color);
            color: white;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-update:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </a>
                
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> <?php echo $lang === 'english' ? 'Dashboard' : 'Dashboard'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book"></i> <?php echo $lang === 'english' ? 'Courses' : 'Amasomo'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exams.php">
                            <i class="fas fa-clipboard-check"></i> <?php echo $lang === 'english' ? 'Exams' : 'Ibizamini'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="certificates.php">
                            <i class="fas fa-certificate"></i> <?php echo $lang === 'english' ? 'Certificates' : 'Impamyabushobozi'; ?>
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo explode(' ', $user['full_name'])[0]; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item active" href="profile.php">
                            <i class="fas fa-user"></i> <?php echo $lang === 'english' ? 'Profile' : 'Umwirondoro'; ?>
                        </a>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog"></i> <?php echo $lang === 'english' ? 'Settings' : 'Igenamiterere'; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="../public/logout.php">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $lang === 'english' ? 'Logout' : 'Sohoka'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Flash Messages -->
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

            <div class="row">
                <!-- Left Column: Profile Info -->
                <div class="col-md-4">
                    <div class="profile-card text-center">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <?php if ($subscription): ?>
                            <span class="badge badge-subscription <?php echo $subscription['subscription_type'] === 'trial' ? 'badge-warning' : 'badge-success'; ?>">
                                <?php 
                                    if ($subscription['subscription_type'] === 'trial') {
                                        echo $lang === 'english' ? 'Free Trial' : 'Igerageza Ry\'Ubuntu';
                                    } else {
                                        echo $lang === 'english' ? 'Premium Member' : 'Premium';
                                    }
                                ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="info-row text-left mt-4">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Member Since' : 'Wiyandikishije'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo format_date($user['created_at']); ?>
                            </div>
                        </div>
                        
                        <?php if ($subscription): ?>
                        <div class="info-row text-left">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Subscription Expires' : 'Inyandiko Irangira'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo format_date($subscription['end_date']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row text-left">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Last Login' : 'Igihe Winjiye Bwa Nyuma'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo $user['last_login'] ? time_ago($user['last_login']) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <div class="profile-card">
                        <h5 class="section-title" style="font-size: 18px;">
                            <?php echo $lang === 'english' ? 'My Statistics' : 'Imibare Yanjye'; ?>
                        </h5>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Lessons Completed' : 'Amasomo Yarangiye'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo $stats['total_lessons_completed'] ?? 0; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Exams Passed' : 'Ibizamini Byatsindiwe'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo $stats['total_exams_passed'] ?? 0; ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Average Score' : 'Amanota Impuzandengo'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo format_percentage($stats['average_exam_score'] ?? 0); ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">
                                <?php echo $lang === 'english' ? 'Current Streak' : 'Iminsi Ikurikiranye'; ?>
                            </div>
                            <div class="info-value">
                                <?php echo $stats['current_streak_days'] ?? 0; ?> 
                                <?php echo $lang === 'english' ? 'days' : 'iminsi'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Edit Profile Form -->
                <div class="col-md-8">
                    <div class="profile-card">
                        <h3 class="section-title">
                            <i class="fas fa-edit"></i> 
                            <?php echo $lang === 'english' ? 'Edit Profile' : 'Hindura Umwirondoro'; ?>
                        </h3>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name">
                                            <?php echo $lang === 'english' ? 'Full Name' : 'Amazina Yombi'; ?> *
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="full_name" 
                                            name="full_name" 
                                            value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">
                                            <?php echo $lang === 'english' ? 'Phone Number' : 'Numero ya Telefoni'; ?> *
                                        </label>
                                        <input 
                                            type="tel" 
                                            class="form-control" 
                                            id="phone" 
                                            name="phone" 
                                            value="<?php echo htmlspecialchars($user['phone']); ?>"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">
                                            <?php echo $lang === 'english' ? 'Email Address' : 'Aderesi ya Email'; ?>
                                        </label>
                                        <input 
                                            type="email" 
                                            class="form-control" 
                                            id="email" 
                                            value="<?php echo htmlspecialchars($user['email']); ?>"
                                            disabled
                                        >
                                        <small class="text-muted">
                                            <?php echo $lang === 'english' ? 'Email cannot be changed' : 'Email ntishobora guhindurwa'; ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="language">
                                            <?php echo $lang === 'english' ? 'Preferred Language' : 'Ururimi Uhitamo'; ?>
                                        </label>
                                        <select class="form-control" id="language" name="language">
                                            <option value="kinyarwanda" <?php echo $user['language_preference'] === 'kinyarwanda' ? 'selected' : ''; ?>>
                                                Kinyarwanda
                                            </option>
                                            <option value="english" <?php echo $user['language_preference'] === 'english' ? 'selected' : ''; ?>>
                                                English
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-update">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $lang === 'english' ? 'Save Changes' : 'Bika Impinduka'; ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Section -->
                    <div class="profile-card">
                        <h3 class="section-title">
                            <i class="fas fa-key"></i> 
                            <?php echo $lang === 'english' ? 'Change Password' : 'Hindura Ijambo ry\'Ibanga'; ?>
                        </h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <?php echo $lang === 'english' 
                                ? 'For security reasons, changing your password requires email verification.' 
                                : 'Kubw\'umutekano, guhindura ijambo ry\'ibanga bisaba kwemeza email.'; ?>
                        </div>
                        
                        <a href="change-password.php" class="btn btn-outline-primary">
                            <i class="fas fa-key"></i> 
                            <?php echo $lang === 'english' ? 'Change Password' : 'Hindura Ijambo ry\'Ibanga'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>