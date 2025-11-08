<?php
/**
 * Courses Listing Page
 * 
 * Display all available courses with progress
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

// Get all courses with progress
$courses_sql = "SELECT c.*, 
                COUNT(DISTINCT l.lesson_id) as total_lessons,
                COUNT(DISTINCT sp.lesson_id) as completed_lessons
                FROM courses c
                LEFT JOIN lessons l ON c.course_id = l.course_id AND l.status = 'active'
                LEFT JOIN student_progress sp ON l.lesson_id = sp.lesson_id 
                    AND sp.user_id = ? AND sp.status = 'completed'
                WHERE c.status = 'active'
                GROUP BY c.course_id
                ORDER BY c.sort_order ASC";

$courses = db_fetch_all($courses_sql, [$user_id]);

$page_title = $lang === 'english' ? 'Courses' : 'Amasomo';
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
        
        .page-subtitle {
            color: #6c757d;
            font-size: 18px;
        }
        
        .course-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 5px solid var(--primary-color);
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 62, 150, 0.15);
        }
        
        .course-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin-bottom: 20px;
        }
        
        .course-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .course-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .course-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .course-meta i {
            margin-right: 5px;
        }
        
        .progress {
            height: 25px;
            border-radius: 15px;
            background: #e9ecef;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            border-radius: 15px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .btn-start-course {
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-start-course:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .completion-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
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
                        <a class="nav-link active" href="courses.php">
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
                    <i class="fas fa-book-open"></i> 
                    <?php echo $lang === 'english' ? 'All Courses' : 'Amasomo Yose'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $lang === 'english' 
                        ? 'Choose a course to start learning' 
                        : 'Hitamo isomo kugira ngo utangire kwiga'; ?>
                </p>
            </div>

            <!-- Courses List -->
            <?php foreach ($courses as $course): ?>
                <?php 
                    $course_name = $lang === 'english' ? $course['course_name_en'] : $course['course_name_rw'];
                    $course_desc = $lang === 'english' ? $course['description_en'] : $course['description_rw'];
                    $completion = $course['total_lessons'] > 0 ? 
                        ($course['completed_lessons'] / $course['total_lessons']) * 100 : 0;
                ?>
                <div class="course-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="course-icon">
                                <i class="fas <?php echo $course['icon'] ?? 'fa-book'; ?>"></i>
                            </div>
                            
                            <h3 class="course-title"><?php echo htmlspecialchars($course_name); ?></h3>
                            
                            <p class="course-description">
                                <?php echo htmlspecialchars($course_desc ?? ''); ?>
                            </p>
                            
                            <div class="course-meta">
                                <span>
                                    <i class="fas fa-book"></i> 
                                    <?php echo $course['total_lessons']; ?> 
                                    <?php echo $lang === 'english' ? 'Lessons' : 'Amasomo'; ?>
                                </span>
                                <span>
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $course['completed_lessons']; ?> 
                                    <?php echo $lang === 'english' ? 'Completed' : 'Byarangiye'; ?>
                                </span>
                                <?php if ($completion == 100): ?>
                                    <span class="completion-badge">
                                        <i class="fas fa-trophy"></i> 
                                        <?php echo $lang === 'english' ? 'Completed' : 'Byarangiye'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">
                                        <?php echo $lang === 'english' ? 'Progress' : 'Iterambere'; ?>
                                    </small>
                                    <small class="text-muted"><?php echo round($completion); ?>%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?php echo $completion; ?>%">
                                        <?php echo round($completion); ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <a href="course-view.php?id=<?php echo $course['course_id']; ?>" class="btn btn-start-course">
                                <?php if ($completion > 0 && $completion < 100): ?>
                                    <i class="fas fa-play"></i> 
                                    <?php echo $lang === 'english' ? 'Continue Learning' : 'Komeza Kwiga'; ?>
                                <?php elseif ($completion == 100): ?>
                                    <i class="fas fa-redo"></i> 
                                    <?php echo $lang === 'english' ? 'Review Course' : 'Subiramo Isomo'; ?>
                                <?php else: ?>
                                    <i class="fas fa-play"></i> 
                                    <?php echo $lang === 'english' ? 'Start Course' : 'Tangira Isomo'; ?>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($courses)): ?>
                <div class="alert alert-info text-center">
                    <h4>
                        <i class="fas fa-info-circle"></i> 
                        <?php echo $lang === 'english' ? 'No courses available yet' : 'Nta masomo ariho ubu'; ?>
                    </h4>
                    <p><?php echo $lang === 'english' ? 'Check back later for new courses!' : 'Subirayo nyuma kugira ngo urebe amasomo mashya!'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>