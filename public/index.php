<?php
/**
 * Homepage / Landing Page
 * 
 * Main landing page for Uruhushya Software
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// If logged in, redirect to appropriate dashboard
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
    <title><?php echo APP_NAME; ?> - <?php echo $lang === 'english' ? 'Driving License Exam Preparation' : 'Gukina Ikizamini cy\'Uruhushya'; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
            --accent-color: #4169E1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Navigation */
        .navbar {
            background: var(--primary-color) !important;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-size: 24px;
            font-weight: 700;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            margin: 0 10px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-register {
            background: white;
            color: var(--primary-color);
            font-weight: 600;
            padding: 8px 25px;
            border-radius: 25px;
            border: 2px solid white;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background: transparent;
            color: white;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h100v100H0z" fill="none"/><path d="M50 10L90 90H10z" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 22px;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        
        .hero-buttons .btn {
            margin: 10px;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            border: 2px solid white;
        }
        
        .btn-hero-primary:hover {
            background: transparent;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-hero-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .scroll-indicator {
            margin-top: 50px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 18px;
            color: #666;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(44, 62, 150, 0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 36px;
            color: white;
        }
        
        .feature-card h4 {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.7;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--primary-color);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .stat-item {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer h5 {
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 30px;
            padding-top: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">
                            <?php echo $lang === 'english' ? 'Features' : 'Ibisobanuro'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">
                            <?php echo $lang === 'english' ? 'Pricing' : 'Ibiciro'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">
                            <?php echo $lang === 'english' ? 'Contact' : 'Twandikire'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> 
                            <?php echo $lang === 'english' ? 'Login' : 'Injira'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-register" href="register.php">
                            <?php echo $lang === 'english' ? 'Register' : 'Iyandikishe'; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                            <i class="fas fa-globe"></i> 
                            <?php echo $lang === 'english' ? 'Language' : 'Ururimi'; ?>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="?lang=kinyarwanda">Kinyarwanda</a>
                            <a class="dropdown-item" href="?lang=english">English</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <h1 class="hero-title">
                <?php echo $lang === 'english' 
                    ? 'Start Your Driving Journey with Confidence!' 
                    : 'Tangira Urugendo Rwawe rwo Gutwara n\'Ikizere!'; ?>
            </h1>
            <p class="hero-subtitle">
                <?php echo $lang === 'english' 
                    ? 'Prepare for your provisional driving license exam with our comprehensive online platform' 
                    : 'Witegurire ikizamini cy\'uruhushya rw\'ibanze hamwe na platfome yacu yuzuye'; ?>
            </p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-hero-primary">
                    <i class="fas fa-user-plus"></i> 
                    <?php echo $lang === 'english' ? 'Get Started Free' : 'Tangira Ku Buntu'; ?>
                </a>
                <a href="login.php" class="btn btn-hero-secondary">
                    <i class="fas fa-sign-in-alt"></i> 
                    <?php echo $lang === 'english' ? 'Login' : 'Injira'; ?>
                </a>
            </div>
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down fa-2x"></i>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>
                    <?php echo $lang === 'english' ? 'Why Choose Uruhushya?' : 'Kubera Iki Hitamo Uruhushya?'; ?>
                </h2>
                <p>
                    <?php echo $lang === 'english' 
                        ? 'Everything you need to pass your driving exam' 
                        : 'Ibyo ukeneye byose kugira ngo utsinde ikizamini'; ?>
                </p>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Comprehensive Lessons' : 'Amasomo Yuzuye'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Learn road signs, regulations, safety, and driving techniques' 
                                : 'Wige ibimenyetso, amategeko, umutekano n\'uburyo bwo gutwara'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Practice Exams' : 'Ibizamini byo Kwiga'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Take unlimited mock exams similar to the real test' 
                                : 'Kora ibizamini bidahwitse bisa n\'ikizamini nyakuri'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Progress Tracking' : 'Gukurikirana Iterambere'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Monitor your performance and identify weak areas' 
                                : 'Kurikirana imikorere yawe kandi umenye ahantu hashya'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Certificates' : 'Impamyabushobozi'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Earn certificates when you complete courses and pass exams' 
                                : 'Bona impamyabushobozi iyo warangije amasomo no gutsinda ibizamini'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-language"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Bilingual Support' : 'Indimi Ebyiri'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Learn in Kinyarwanda or English - your choice' 
                                : 'Wige mu Kinyarwanda cyangwa Icyongereza - hitamo'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>
                            <?php echo $lang === 'english' ? 'Mobile Friendly' : 'Ikorera kuri Telefoni'; ?>
                        </h4>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Study anywhere, anytime on any device' 
                                : 'Wige ahantu hose, igihe cyose ku bikoresho byose'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Practice Questions' : 'Ibibazo byo Kwiga'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">4</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Course Categories' : 'Ibice by\'Amasomo'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Success Rate' : 'Igipimo cy\'Intsinzi'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Access' : 'Kwinjira'; ?>
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
                <div class="col-md-4 mb-4">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p><?php echo COMPANY_ADDRESS; ?></p>
                    <p>
                        <i class="fas fa-phone"></i> <?php echo SITE_PHONE; ?><br>
                        <i class="fas fa-envelope"></i> <?php echo SITE_EMAIL;