<?php
/**
 * Public Homepage - Professional Design
 * 
 * Modern homepage inspired by ikizamini.com
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Handle language switching for public users
$lang = 'kinyarwanda'; // Default to Kinyarwanda

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'english' ? 'english' : 'kinyarwanda';
    $_SESSION['public_language'] = $lang;
} elseif (isset($_SESSION['public_language'])) {
    $lang = $_SESSION['public_language'];
}

// Check if user is already logged in
if (is_logged_in()) {
    $role = get_user_role();
    switch ($role) {
        case 'admin':
            redirect('../admin/dashboard.php');
            break;
        case 'school':
            redirect('../school/dashboard.php');
            break;
        case 'agent':
            redirect('../agent/dashboard.php');
            break;
        case 'student':
            redirect('../student/dashboard.php');
            break;
        default:
            redirect('login.php');
    }
}

// Get all active exams
$exams = db_fetch_all(
    "SELECT * FROM exams 
     WHERE status = 'active'
     ORDER BY exam_code DESC
     LIMIT 20"
);

// Get all active courses
$courses = db_fetch_all(
    "SELECT * FROM courses WHERE status = 'active' ORDER BY course_name_en"
);

// Get statistics
$stats = [
    'total_students' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'],
    'total_exams' => count($exams),
    'total_questions' => db_fetch("SELECT COUNT(*) as count FROM questions WHERE status = 'active'")['count']
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $lang === 'english' ? 'Rwanda Driving License Exam' : 'Ikizamini cy\'Uruhushya rwo Gutwara Imodoka'; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }
        
        /* Navbar - Compact Version */
        .top-navbar {
            background: #2C3E96;
            padding: 10px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .top-navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            color: white;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0;
        }
        
        .logo:hover {
            color: white;
            text-decoration: none;
        }
        
        .nav-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn-nav {
            padding: 6px 16px;
            border-radius: 5px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 13px;
        }
        
        .btn-register,
        .dropdown-toggle.btn-nav {
            background: #0066cc;
            color: white;
        }
        
        .btn-register:hover,
        .dropdown-toggle.btn-nav:hover {
            background: #0052a3;
            color: white;
            text-decoration: none;
        }
        
        .btn-login {
            background: transparent;
            color: white;
            border: 1px solid white;
        }
        
        .btn-login:hover {
            background: white;
            color: #2C3E96;
            text-decoration: none;
        }
        
        .language-dropdown {
            position: relative;
        }
        
        .lang-btn {
            background: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .lang-menu {
            position: absolute;
            top: 38px;
            right: 0;
            background: white;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
            min-width: 160px;
            overflow: hidden;
        }
        
        .lang-menu.show {
            display: block;
        }
        
        .lang-menu a {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 13px;
        }
        
        .lang-menu a:hover {
            background: #f0f0f0;
        }
        
        .lang-menu a.active {
            background: #2C3E96;
            color: white;
            font-weight: 600;
        }
        
        /* Dropdown menu styling */
        .dropdown-menu {
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: none;
            padding: 6px 0;
            min-width: 220px;
            margin-top: 2px;
        }
        
        .dropdown-item {
            padding: 8px 16px;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
        }
        
        .dropdown-item i {
            width: 18px;
            margin-right: 8px;
            font-size: 12px;
        }
        
        .dropdown-header {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #6c757d;
            padding: 6px 16px 4px;
        }
        
        .dropdown-divider {
            margin: 6px 0;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #2C3E96 0%, #1a2557 100%);
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding-top: 60px;
            position: relative;
        }
        
        .hero-content h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 25px;
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 20px;
            opacity: 0.95;
            margin-bottom: 40px;
            font-weight: 400;
        }
        
        .scroll-down {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            animation: bounce 2s infinite;
        }
        
        .scroll-down-circle {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .scroll-down i {
            font-size: 20px;
            color: white;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) translateX(-50%);
            }
            40% {
                transform: translateY(-10px) translateX(-50%);
            }
            60% {
                transform: translateY(-5px) translateX(-50%);
            }
        }
        
        /* Content Section */
        .content-section {
            padding: 50px 0;
            background: #f8f9fa;
        }
        
        /* Tabs */
        .custom-tabs {
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 35px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .custom-tab {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            color: #6c757d;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .custom-tab:hover {
            color: #2C3E96;
        }
        
        .custom-tab.active {
            color: #2C3E96;
            border-bottom-color: #2C3E96;
        }
        
        .tab-content-area {
            display: none;
        }
        
        .tab-content-area.active {
            display: block;
        }
        
        /* Exam Cards */
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .exam-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 62, 150, 0.15);
            border-color: #2C3E96;
        }
        
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .exam-code {
            font-size: 28px;
            font-weight: 700;
            color: #2C3E96;
        }
        
        .free-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .premium-badge {
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .exam-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        
        .exam-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #6c757d;
        }
        
        .exam-meta i {
            color: #2C3E96;
            margin-right: 5px;
        }
        
        /* Course Cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 62, 150, 0.15);
            border-color: #2C3E96;
        }
        
        .course-icon {
            font-size: 42px;
            color: #2C3E96;
            margin-bottom: 15px;
        }
        
        .course-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .course-description {
            color: #6c757d;
            font-size: 13px;
        }
        
        /* Pricing Section */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .price-card {
            background: white;
            border-radius: 15px;
            padding: 35px 25px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        
        .price-card:hover {
            transform: translateY(-8px);
            border-color: #2C3E96;
        }
        
        .price-card.featured {
            border-color: #2C3E96;
            transform: scale(1.03);
        }
        
        .plan-name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 18px;
        }
        
        .plan-price {
            font-size: 42px;
            font-weight: 700;
            color: #2C3E96;
            margin-bottom: 8px;
        }
        
        .plan-price small {
            font-size: 16px;
            color: #6c757d;
        }
        
        .plan-duration {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 25px;
        }
        
        .plan-features li {
            padding: 8px 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .plan-features i {
            color: #28a745;
            margin-right: 8px;
        }
        
        .btn-choose-plan {
            background: #2C3E96;
            color: white;
            padding: 12px 35px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            font-size: 14px;
        }
        
        .btn-choose-plan:hover {
            background: #1a2557;
            transform: scale(1.03);
        }
        
        /* Stats Bar */
        .stats-bar {
            background: white;
            padding: 35px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 38px;
            font-weight: 700;
            color: #2C3E96;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* Footer */
        .footer {
            background: #1a2557;
            color: white;
            padding: 35px 0 18px;
            margin-top: 50px;
        }
        
        .footer h5 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .footer p {
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 32px;
            }
            
            .hero-content p {
                font-size: 16px;
            }
            
            .exam-grid,
            .course-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                gap: 5px;
            }
            
            .btn-nav {
                padding: 5px 12px;
                font-size: 12px;
            }
            
            .logo {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="container">
            <a href="index.php" class="logo"><?php echo strtolower(APP_NAME); ?></a>
            
            <div class="nav-buttons">
                <!-- Dropdown for Registration Options -->
                <div class="dropdown" style="display: inline-block;">
                    <button class="btn-nav btn-register dropdown-toggle" type="button" id="registerDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background: white; color: #2C3E96; border: none; cursor: pointer;">
                        <i class="fas fa-user-plus"></i> 
                        <?php echo $lang === 'english' ? 'Join Us' : 'Twinjire'; ?>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="registerDropdown">
                        <a class="dropdown-item" href="register.php">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo $lang === 'english' ? 'Student Registration' : 'Iyandikishe - Umunyeshuri'; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header"><?php echo $lang === 'english' ? 'For Partners' : 'Ku Bafatanyabikorwa'; ?></h6>
                        <a class="dropdown-item" href="apply-school.php">
                            <i class="fas fa-school"></i> 
                            <?php echo $lang === 'english' ? 'School Application' : 'Gusaba - Ishuri'; ?>
                        </a>
                        <a class="dropdown-item" href="apply-agent.php">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo $lang === 'english' ? 'Agent Application' : 'Gusaba - Umuhagarariye'; ?>
                        </a>
                    </div>
                </div>
                
                <a href="login.php" class="btn-nav btn-login">
                    <i class="fas fa-sign-in-alt"></i> 
                    <?php echo $lang === 'english' ? 'Login' : 'Injira'; ?>
                </a>
                
                <div class="language-dropdown">
                    <button class="lang-btn" onclick="toggleLangMenu()">
                        <i class="fas fa-globe"></i>
                        <?php echo $lang === 'english' ? 'EN' : 'RW'; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="lang-menu" id="langMenu">
                        <a href="?lang=kinyarwanda" class="<?php echo $lang === 'kinyarwanda' ? 'active' : ''; ?>">
                            <i class="fas fa-flag"></i> Kinyarwanda
                        </a>
                        <a href="?lang=english" class="<?php echo $lang === 'english' ? 'active' : ''; ?>">
                            <i class="fas fa-flag"></i> English
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>
                <?php echo $lang === 'english' 
                    ? 'What will you study today?' 
                    : 'Uyu munsi uriga iki?'; ?>
            </h1>
            <p>
                <?php echo $lang === 'english' 
                    ? 'Practice and pass your Rwanda driving license exam with confidence' 
                    : 'Wige kandi utsinde ikizamini cy\'uruhushya rwo gutwara imodoka mu Rwanda'; ?>
            </p>
        </div>
        
        <div class="scroll-down" onclick="document.getElementById('content').scrollIntoView({behavior: 'smooth'})">
            <div class="scroll-down-circle">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content-section" id="content">
        <div class="container">
            <!-- Tabs -->
            <div class="custom-tabs">
                <button class="custom-tab active" onclick="switchTab('exams')">
                    <?php echo $lang === 'english' ? 'Exams' : 'Isuzumabumenyi'; ?>
                </button>
                <button class="custom-tab" onclick="switchTab('courses')">
                    <?php echo $lang === 'english' ? 'Courses' : 'Amasomo'; ?>
                </button>
                <button class="custom-tab" onclick="switchTab('pricing')">
                    <?php echo $lang === 'english' ? 'Pricing' : 'Ibiciro'; ?>
                </button>
            </div>

            <!-- Exams Tab -->
            <div id="exams-tab" class="tab-content-area active">
                <div class="exam-grid">
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-card" onclick="requireLogin()">
                            <div class="exam-header">
                                <div class="exam-code"><?php echo htmlspecialchars($exam['exam_code']); ?></div>
                                <?php if ($exam['is_free']): ?>
                                    <span class="free-badge">FREE</span>
                                <?php else: ?>
                                    <span class="premium-badge">PREMIUM</span>
                                <?php endif; ?>
                            </div>
                            <div class="exam-description">
                                <?php 
                                $exam_name = $lang === 'english' 
                                    ? ($exam['exam_name_en'] ?? $exam['exam_name_rw']) 
                                    : ($exam['exam_name_rw'] ?? $exam['exam_name_en']);
                                echo htmlspecialchars($exam_name); 
                                ?>
                            </div>
                            <div class="exam-meta">
                                <span>
                                    <i class="fas fa-question-circle"></i> 
                                    20 <?php echo $lang === 'english' ? 'questions' : 'ibibazo'; ?>
                                </span>
                                <span>
                                    <i class="far fa-clock"></i> 
                                    <?php echo $exam['time_limit_minutes']; ?> min
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Courses Tab -->
            <div id="courses-tab" class="tab-content-area">
                <div class="course-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card" onclick="requireLogin()">
                            <div class="course-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="course-name">
                                <?php 
                                $course_name = $lang === 'english' 
                                   ? ($course['course_name_en'] ?? '') 
                                   : ($course['course_name_rw'] ?? '');
                                echo htmlspecialchars($course_name ?: ($lang === 'english' ? 'Driving Course' : 'Isomo ry\'Imodoka'));  
                                ?>
                            </div>
                            <div class="course-description">
                                <?php 
                                $description = $lang === 'english' ? ($course['description_en'] ?? '') : ($course['description_rw'] ?? '');
                                echo htmlspecialchars($description ?: ($lang === 'english' ? 'Learn driving theory and practice' : 'Wige teoriya yo gutwara imodoka')); 
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pricing Tab -->
            <div id="pricing-tab" class="tab-content-area">
                <div class="pricing-grid">
                    <!-- Monthly Plan -->
                    <div class="price-card">
                        <div class="plan-name">
                            <?php echo $lang === 'english' ? 'Monthly' : 'Ukwezi'; ?>
                        </div>
                        <div class="plan-price">
                            5,000 <small>RWF</small>
                        </div>
                        <div class="plan-duration">
                            <?php echo $lang === 'english' ? '30 days' : 'Iminsi 30'; ?>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'All practice exams' : 'Ibizamini byose'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Unlimited attempts' : 'Ibigeragezo bitagira iherezo'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Progress tracking' : 'Gukurikirana iterambere'; ?></li>
                        </ul>
                        <button class="btn-choose-plan" onclick="window.location.href='register.php'">
                            <?php echo $lang === 'english' ? 'Choose Plan' : 'Hitamo'; ?>
                        </button>
                    </div>

                    <!-- 3-Month Plan -->
                    <div class="price-card featured">
                        <div class="plan-name">
                            <?php echo $lang === 'english' ? '3 Months' : 'Amezi 3'; ?>
                        </div>
                        <div class="plan-price">
                            12,000 <small>RWF</small>
                        </div>
                        <div class="plan-duration">
                            <?php echo $lang === 'english' ? '90 days - Save 3,000 RWF' : 'Iminsi 90 - Kizanira 3,000'; ?>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'All Monthly features' : 'Ibyose byo kukwezi'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Priority support' : 'Ubufasha bwihuse'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Best value' : 'Agaciro keza'; ?></li>
                        </ul>
                        <button class="btn-choose-plan" onclick="window.location.href='register.php'">
                            <?php echo $lang === 'english' ? 'Choose Plan' : 'Hitamo'; ?>
                        </button>
                    </div>

                    <!-- Yearly Plan -->
                    <div class="price-card">
                        <div class="plan-name">
                            <?php echo $lang === 'english' ? 'Yearly' : 'Umwaka'; ?>
                        </div>
                        <div class="plan-price">
                            40,000 <small>RWF</small>
                        </div>
                        <div class="plan-duration">
                            <?php echo $lang === 'english' ? '365 days - Save 20,000 RWF' : 'Iminsi 365 - Kizanira 20,000'; ?>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'All features' : 'Ibyose byose'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Free certificate' : 'Impamyabushobozi ubuntu'; ?></li>
                            <li><i class="fas fa-check"></i> <?php echo $lang === 'english' ? 'Maximum savings' : 'Kizanira byinshi'; ?></li>
                        </ul>
                        <button class="btn-choose-plan" onclick="window.location.href='register.php'">
                            <?php echo $lang === 'english' ? 'Choose Plan' : 'Hitamo'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="stats-bar">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($stats['total_students']); ?>+</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Students' : 'Abanyeshuri'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($stats['total_exams']); ?></div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Exams' : 'Ibizamini'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($stats['total_questions']); ?>+</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Questions' : 'Ibibazo'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section style="background: white; padding: 50px 0;">
        <div class="container">
            <div class="text-center mb-4">
                <h2 style="font-size: 32px; font-weight: 700; color: #2C3E96; margin-bottom: 12px;">
                    <?php echo $lang === 'english' ? 'Join Our Network' : 'Duhererane'; ?>
                </h2>
                <p style="font-size: 16px; color: #6c757d;">
                    <?php echo $lang === 'english' 
                        ? 'Are you a driving school or marketing agent? Partner with us!' 
                        : 'Uri ishuri ry\'imodoka cyangwa umuhagarariye? Dufatanye!'; ?>
                </p>
            </div>
            
            <div class="row">
                <!-- For Schools -->
                <div class="col-md-6 mb-4">
                    <div style="background: linear-gradient(135deg, #2C3E96 0%, #1a2557 100%); border-radius: 15px; padding: 35px; color: white; text-align: center; height: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        <div style="font-size: 56px; margin-bottom: 18px;">
                            <i class="fas fa-school"></i>
                        </div>
                        <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 18px;">
                            <?php echo $lang === 'english' ? 'For Driving Schools' : 'Ku Mashuri y\'Imodoka'; ?>
                        </h3>
                        <p style="opacity: 0.9; margin-bottom: 25px; font-size: 15px;">
                            <?php echo $lang === 'english' 
                                ? 'Manage your students, track their progress, and help them succeed.' 
                                : 'Genzura abanyeshuri bawe, kurikirana iterambere ryabo.'; ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin-bottom: 25px; text-align: left;">
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Manage up to 150 students' : 'Genzura abanyeshuri 150'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Track student progress' : 'Kurikirana iterambere'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Generate reports' : 'Kora raporo'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Special pricing' : 'Ibiciro byihariye'; ?>
                            </li>
                        </ul>
                        <a href="apply-school.php" style="background: white; color: #2C3E96; padding: 12px 35px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s; font-size: 14px;">
                            <?php echo $lang === 'english' ? 'Apply as School' : 'Saba kuba Ishuri'; ?>
                            <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    </div>
                </div>
                
                <!-- For Agents -->
                <div class="col-md-6 mb-4">
                    <div style="background: linear-gradient(135deg, #FF6F00 0%, #E65100 100%); border-radius: 15px; padding: 35px; color: white; text-align: center; height: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        <div style="font-size: 56px; margin-bottom: 18px;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 18px;">
                            <?php echo $lang === 'english' ? 'For Marketing Agents' : 'Ku Bahagarariye'; ?>
                        </h3>
                        <p style="opacity: 0.9; margin-bottom: 25px; font-size: 15px;">
                            <?php echo $lang === 'english' 
                                ? 'Earn commission by referring students. Work from anywhere!' 
                                : 'Injiza amafaranga mu gushyikiriza abanyeshuri.'; ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin-bottom: 25px; text-align: left;">
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Earn 10% commission' : 'Injiza 10%'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Monthly payouts' : 'Ubwishyu bwa buri kwezi'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Track your earnings' : 'Kureba amafaranga'; ?>
                            </li>
                            <li style="padding: 8px 0; font-size: 14px;"><i class="fas fa-check" style="color: #28a745; margin-right: 8px;"></i> 
                                <?php echo $lang === 'english' ? 'Work remotely' : 'Kora aho ushaka'; ?>
                            </li>
                        </ul>
                        <a href="apply-agent.php" style="background: white; color: #FF6F00; padding: 12px 35px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s; font-size: 14px;">
                            <?php echo $lang === 'english' ? 'Apply as Agent' : 'Saba kuba Umuhagarariye'; ?>
                            <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo strtolower(APP_NAME); ?></h5>
                    <p><?php echo $lang === 'english' 
                        ? 'Rwanda\'s #1 Driving License Exam Platform' 
                        : 'Urubuga rwa #1 rw\'Ikizamini cy\'Uruhushya mu Rwanda'; ?></p>
                </div>
                <div class="col-md-4">
                    <h5><?php echo $lang === 'english' ? 'Quick Links' : 'Ihuza Ryihuse'; ?></h5>
                    <ul class="list-unstyled">
                        <li style="margin-bottom: 8px;"><a href="register.php">
                            <i class="fas fa-user-graduate"></i> 
                            <?php echo $lang === 'english' ? 'Student Registration' : 'Iyandikishe'; ?>
                        </a></li>
                        <li style="margin-bottom: 8px;"><a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> 
                            <?php echo $lang === 'english' ? 'Login' : 'Injira'; ?>
                        </a></li>
                        <li style="margin: 12px 0 8px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <a href="apply-school.php">
                                <i class="fas fa-school"></i> 
                                <?php echo $lang === 'english' ? 'School Application' : 'Gusaba - Ishuri'; ?>
                            </a>
                        </li>
                        <li style="margin-bottom: 8px;"><a href="apply-agent.php">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo $lang === 'english' ? 'Agent Application' : 'Gusaba - Umuhagarariye'; ?>
                        </a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5><?php echo $lang === 'english' ? 'Contact' : 'Tuvugishe'; ?></h5>
                    <p>
                        <i class="fas fa-envelope"></i> info@uruhushya.rw<br>
                        <i class="fas fa-phone"></i> +250 788 123 456
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 25px 0;">
            <div class="text-center">
                <p style="font-size: 13px; margin: 0;">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Language menu toggle
        function toggleLangMenu() {
            document.getElementById('langMenu').classList.toggle('show');
        }
        
        // Close language menu when clicking outside
        document.addEventListener('click', function(event) {
            const langDropdown = document.querySelector('.language-dropdown');
            if (!langDropdown.contains(event.target)) {
                document.getElementById('langMenu').classList.remove('show');
            }
        });
        
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content-area').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.custom-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active to clicked button
            event.target.classList.add('active');
        }
        
        // Require login to access exams/courses
        function requireLogin() {
            if (confirm('<?php echo $lang === "english" 
                ? "You need to login or register to access this content. Would you like to register now?" 
                : "Ugomba kwinjira cyangwa kwiyandikisha kugira ngo ubone ibi. Urashaka kwiyandikisha?"; ?>')) {
                window.location.href = 'register.php';
            } else {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>