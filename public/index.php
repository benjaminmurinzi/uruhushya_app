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

// Get all active exams (no course_id needed)
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .top-navbar {
            background: #2C3E96;
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            font-size: 22px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .logo:hover {
            color: white;
            text-decoration: none;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn-nav {
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 600;
            border: 2px solid white;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-register {
            background: white;
            color: #2C3E96;
        }
        
        .btn-register:hover {
            background: transparent;
            color: white;
            text-decoration: none;
        }
        
        .btn-login {
            background: transparent;
            color: white;
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
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 18px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .lang-menu {
            position: absolute;
            top: 45px;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            min-width: 180px;
            overflow: hidden;
        }
        
        .lang-menu.show {
            display: block;
        }
        
        .lang-menu a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .lang-menu a:hover {
            background: #f0f0f0;
        }
        
        .lang-menu a.active {
            background: #2C3E96;
            color: white;
            font-weight: 600;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #2C3E96 0%, #1a2557 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding-top: 80px;
            position: relative;
        }
        
        .hero-content h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 30px;
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 22px;
            opacity: 0.9;
            margin-bottom: 40px;
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
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .scroll-down i {
            font-size: 24px;
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
            padding: 60px 0;
            background: #f8f9fa;
        }
        
        /* Tabs */
        .custom-tabs {
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .custom-tab {
            padding: 15px 40px;
            font-size: 18px;
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .exam-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
        }
        
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(44, 62, 150, 0.15);
            border-color: #2C3E96;
        }
        
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .exam-code {
            font-size: 32px;
            font-weight: 800;
            color: #2C3E96;
        }
        
        .free-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .premium-badge {
            background: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .exam-description {
            color: #6c757d;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .exam-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .exam-meta i {
            color: #2C3E96;
            margin-right: 5px;
        }
        
        /* Course Cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(44, 62, 150, 0.15);
            border-color: #2C3E96;
        }
        
        .course-icon {
            font-size: 48px;
            color: #2C3E96;
            margin-bottom: 20px;
        }
        
        .course-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .course-description {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Pricing Section */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .price-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        
        .price-card:hover {
            transform: translateY(-10px);
            border-color: #2C3E96;
        }
        
        .price-card.featured {
            border-color: #2C3E96;
            transform: scale(1.05);
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }
        
        .plan-price {
            font-size: 48px;
            font-weight: 800;
            color: #2C3E96;
            margin-bottom: 10px;
        }
        
        .plan-price small {
            font-size: 18px;
            color: #6c757d;
        }
        
        .plan-duration {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }
        
        .plan-features li {
            padding: 10px 0;
            color: #6c757d;
        }
        
        .plan-features i {
            color: #28a745;
            margin-right: 10px;
        }
        
        .btn-choose-plan {
            background: #2C3E96;
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-choose-plan:hover {
            background: #1a2557;
            transform: scale(1.05);
        }
        
        /* Stats Bar */
        .stats-bar {
            background: white;
            padding: 40px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            color: #2C3E96;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* Footer */
        .footer {
            background: #1a2557;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .footer a:hover {
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 36px;
            }
            
            .exam-grid,
            .course-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                gap: 10px;
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
                <a href="register.php" class="btn-nav btn-register">
                    <i class="fas fa-user-plus"></i> 
                    <?php echo $lang === 'english' ? 'Iyandikishe' : 'Iyandikishe'; ?>
                </a>
                <a href="login.php" class="btn-nav btn-login">
                    <i class="fas fa-sign-in-alt"></i> 
                    <?php echo $lang === 'english' ? 'Injira' : 'Injira'; ?>
                </a>
                
                <div class="language-dropdown">
                    <button class="lang-btn" onclick="toggleLangMenu()">
                        <i class="fas fa-globe"></i>
                        <?php echo $lang === 'english' ? 'English' : 'Kinyarwanda'; ?>
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
                                $description = $lang === 'english' 
                                   ? ($course['description_en'] ?? '') 
                                   : ($course['description_rw'] ?? '');
                                echo htmlspecialchars($description ?: ($lang === 'english' ? 'Learn driving theory and practice' : 'Wige teoriya yo gutwara imodoka'));  
                                ?>
                            </div>
                            <div class="course-description">
                                <?php 
                                $description = $lang === 'english' ? $course['description_en'] : $course['description_rw'];
                                echo htmlspecialchars($description ?? ''); 
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
                        <li><a href="register.php"><?php echo $lang === 'english' ? 'Register' : 'Iyandikishe'; ?></a></li>
                        <li><a href="login.php"><?php echo $lang === 'english' ? 'Login' : 'Injira'; ?></a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5><?php echo $lang === 'english' ? 'Contact' : 'Tuvugishe'; ?></h5>
                    <p>
                        <i class="fas fa-envelope"></i> <?php echo SITE_EMAIL; ?><br>
                        <i class="fas fa-phone"></i> <?php echo SITE_PHONE; ?>
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 30px 0;">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
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