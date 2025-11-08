<?php
/**
 * Exams Listing Page
 * 
 * Display all available exams for students
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

// Get all active exams
$exams_sql = "SELECT e.*,
              (SELECT COUNT(*) FROM exam_attempts WHERE user_id = ? AND exam_id = e.exam_id AND status = 'completed') as attempts_count,
              (SELECT MAX(score_percentage) FROM exam_attempts WHERE user_id = ? AND exam_id = e.exam_id AND status = 'completed') as best_score
              FROM exams e
              WHERE e.status = 'active'
              ORDER BY e.exam_code DESC";

$exams = db_fetch_all($exams_sql, [$user_id, $user_id]);

$page_title = $lang === 'english' ? 'Exams' : 'Ibizamini';
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
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .exam-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 5px solid var(--primary-color);
        }
        
        .exam-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(44, 62, 150, 0.15);
        }
        
        .exam-code {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .exam-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .exam-description {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .exam-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .exam-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
        }
        
        .exam-meta-item i {
            color: var(--primary-color);
        }
        
        .btn-take-exam {
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-take-exam:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .badge-free {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-premium {
            background: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .best-score {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        
        .attempts-count {
            color: #6c757d;
            font-size: 14px;
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
                        <a class="nav-link active" href="exams.php">
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
                        <a class="dropdown-item" href="profile.php">
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list"></i> 
                    <?php echo $lang === 'english' ? 'Available Exams' : 'Ibizamini Bihari'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $lang === 'english' 
                        ? 'Test your knowledge with our practice exams' 
                        : 'Gerageza ubumenyi bwawe hamwe n\'ibizamini byacu byo kwiga'; ?>
                </p>
            </div>

            <!-- Exams List -->
            <div class="row">
                <?php foreach ($exams as $exam): ?>
                    <?php 
                        $exam_name = $lang === 'english' ? $exam['exam_name_en'] : $exam['exam_name_rw'];
                        $exam_desc = $lang === 'english' ? $exam['description_en'] : $exam['description_rw'];
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="exam-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="exam-code"><?php echo $exam['exam_code']; ?></div>
                                <?php if ($exam['is_free']): ?>
                                    <span class="badge-free">FREE</span>
                                <?php else: ?>
                                    <span class="badge-premium">PREMIUM</span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="exam-title"><?php echo htmlspecialchars($exam_name); ?></h3>
                            
                            <?php if (!empty($exam_desc)): ?>
                                <p class="exam-description">
                                    <?php echo htmlspecialchars(truncate_text($exam_desc, 100)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="exam-meta">
                                <div class="exam-meta-item">
                                    <i class="fas fa-question-circle"></i>
                                    <span>
                                        <?php echo $exam['total_questions']; ?> 
                                        <?php echo $lang === 'english' ? 'Questions' : 'Ibibazo'; ?>
                                    </span>
                                </div>
                                <div class="exam-meta-item">
                                    <i class="far fa-clock"></i>
                                    <span>
                                        <?php echo $exam['time_limit_minutes']; ?> 
                                        <?php echo $lang === 'english' ? 'Minutes' : 'Iminota'; ?>
                                    </span>
                                </div>
                                <div class="exam-meta-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>
                                        <?php echo $exam['passing_score']; ?>/<?php echo $exam['total_questions']; ?> 
                                        <?php echo $lang === 'english' ? 'to Pass' : 'kugira Ngo Utsinde'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($exam['attempts_count'] > 0): ?>
                                <div class="mb-3">
                                    <div class="best-score">
                                        <i class="fas fa-trophy"></i> 
                                        <?php echo $lang === 'english' ? 'Best Score:' : 'Amanota Meza:'; ?> 
                                        <?php echo round($exam['best_score']); ?>%
                                    </div>
                                    <div class="attempts-count mt-2">
                                        <i class="fas fa-redo"></i> 
                                        <?php echo $exam['attempts_count']; ?> 
                                        <?php echo $lang === 'english' ? 'attempts' : 'ibigeragezo'; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <a href="exam-start.php?id=<?php echo $exam['exam_id']; ?>" class="btn btn-take-exam">
                                <i class="fas fa-play-circle"></i> 
                                <?php if ($exam['attempts_count'] > 0): ?>
                                    <?php echo $lang === 'english' ? 'Retake Exam' : 'Ongeramo Ikizamini'; ?>
                                <?php else: ?>
                                    <?php echo $lang === 'english' ? 'Take Exam' : 'Kora Ikizamini'; ?>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($exams)): ?>
                <div class="alert alert-info text-center">
                    <h4>
                        <i class="fas fa-info-circle"></i> 
                        <?php echo $lang === 'english' ? 'No exams available yet' : 'Nta bizamini bihari ubu'; ?>
                    </h4>
                    <p><?php echo $lang === 'english' ? 'Check back later for new exams!' : 'Subirayo nyuma kugira ngo urebe ibizamini bishya!'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>