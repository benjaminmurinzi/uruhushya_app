<?php
/**
 * Student Dashboard
 * 
 * Main dashboard for students to view progress, take exams, and manage account
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$user_id = get_user_id();

// Get user's subscription
$subscription = get_user_subscription($user_id);

// Get user analytics
$analytics_sql = "SELECT * FROM student_analytics WHERE user_id = ? LIMIT 1";
$analytics = db_fetch($analytics_sql, [$user_id]);

// If no analytics, create default
if (!$analytics) {
    $create_analytics = "INSERT INTO student_analytics (user_id) VALUES (?)";
    db_query($create_analytics, [$user_id]);
    $analytics = db_fetch($analytics_sql, [$user_id]);
}

// Get recent exam attempts
$recent_exams_sql = "SELECT ea.*, e.exam_name_en, e.exam_name_rw, e.exam_code
                     FROM exam_attempts ea
                     JOIN exams e ON ea.exam_id = e.exam_id
                     WHERE ea.user_id = ?
                     ORDER BY ea.start_time DESC
                     LIMIT 5";
$recent_exams = db_fetch_all($recent_exams_sql, [$user_id]);

// Get course progress
$course_progress_sql = "SELECT c.course_id, c.course_name_en, c.course_name_rw,
                        COUNT(DISTINCT l.lesson_id) as total_lessons,
                        COUNT(DISTINCT sp.lesson_id) as completed_lessons
                        FROM courses c
                        LEFT JOIN lessons l ON c.course_id = l.course_id
                        LEFT JOIN student_progress sp ON l.lesson_id = sp.lesson_id 
                            AND sp.user_id = ? AND sp.status = 'completed'
                        WHERE c.status = 'active'
                        GROUP BY c.course_id";
$course_progress = db_fetch_all($course_progress_sql, [$user_id]);

// Get available exams
$available_exams_sql = "SELECT * FROM exams WHERE status = 'active' ORDER BY exam_code DESC LIMIT 6";
$available_exams = db_fetch_all($available_exams_sql);

// Get language
$lang = $user['language_preference'] ?? 'kinyarwanda';

$page_title = $lang === 'english' ? 'Dashboard' : 'Dashboard';
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
            --accent-color: #4169E1;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        /* Top Navigation */
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
            transition: all 0.3s;
        }
        
        .top-nav .nav-link:hover {
            color: white;
        }
        
        .user-menu {
            color: white;
        }
        
        .user-menu .dropdown-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
        }
        
        .user-menu .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Main Content */
        .main-content {
            padding: 30px 0;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .welcome-section h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .subscription-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .subscription-badge.trial {
            background: var(--warning-color);
            color: #000;
            border-color: var(--warning-color);
        }
        
        .subscription-badge.active {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }
        
        /* Stats Cards */
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(44, 62, 150, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Progress Section */
        .progress-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .course-progress-item {
            margin-bottom: 20px;
        }
        
        .course-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .progress {
            height: 25px;
            border-radius: 15px;
            background: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 15px;
            font-weight: 600;
            font-size: 13px;
        }
        
        /* Exam Cards */
        .exam-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
            border-left: 4px solid var(--primary-color);
        }
        
        .exam-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(44, 62, 150, 0.15);
        }
        
        .exam-code {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .exam-name {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .exam-meta {
            font-size: 13px;
            color: #999;
        }
        
        .exam-meta i {
            margin-right: 5px;
        }
        
        .btn-take-exam {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-take-exam:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .free-badge {
            background: var(--success-color);
            color: white;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        /* Recent Exams */
        .exam-history-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exam-history-info h6 {
            margin: 0 0 5px;
            color: #333;
            font-weight: 600;
        }
        
        .exam-history-info small {
            color: #6c757d;
        }
        
        .exam-score {
            text-align: right;
        }
        
        .score-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .score-value.passed {
            color: var(--success-color);
        }
        
        .score-value.failed {
            color: var(--danger-color);
        }
        
        .status-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-badge.passed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
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
            <!-- Flash Messages -->
            <?php echo display_flash_message(); ?>
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>
                            <?php echo $lang === 'english' ? 'Welcome back,' : 'Murakaza neza,'; ?> 
                            <?php echo htmlspecialchars($user['full_name']); ?>! 👋
                        </h2>
                        <p>
                            <?php echo $lang === 'english' 
                                ? 'Ready to continue your learning journey?' 
                                : 'Witeguye gukomeza urugendo rwawe rwo kwiga?'; ?>
                        </p>
                        <?php if ($subscription): ?>
                            <span class="subscription-badge <?php echo $subscription['subscription_type'] === 'trial' ? 'trial' : 'active'; ?>">
                                <i class="fas fa-crown"></i> 
                                <?php 
                                    $type_label = $subscription['subscription_type'] === 'trial' ? 
                                        ($lang === 'english' ? 'Free Trial' : 'Igerageza Ry\'Ubuntu') : 
                                        ($lang === 'english' ? 'Premium' : 'Premium');
                                    echo $type_label;
                                ?> - 
                                <?php echo $lang === 'english' ? 'Expires:' : 'Birangira:'; ?> 
                                <?php echo format_date($subscription['end_date']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="exams.php" class="btn btn-light btn-lg">
                            <i class="fas fa-clipboard-check"></i> 
                            <?php echo $lang === 'english' ? 'Start Exam' : 'Tangira Ikizamini'; ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-value"><?php echo $analytics['total_lessons_completed'] ?? 0; ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Lessons Completed' : 'Amasomo Yarangiye'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $analytics['total_exams_passed'] ?? 0; ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Exams Passed' : 'Ibizamini Byatsindiwe'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value"><?php echo format_percentage($analytics['average_exam_score'] ?? 0); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Average Score' : 'Amanota Impuzandengo'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-icon purple">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="stat-value"><?php echo $analytics['current_streak_days'] ?? 0; ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Day Streak' : 'Iminsi Ikurikiranye'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <!-- Course Progress -->
                    <div class="progress-section">
                        <h3 class="section-title">
                            <i class="fas fa-tasks"></i> 
                            <?php echo $lang === 'english' ? 'Course Progress' : 'Iterambere ry\'Amasomo'; ?>
                        </h3>
                        
                        <?php if (!empty($course_progress)): ?>
                            <?php foreach ($course_progress as $course): ?>
                                <?php 
                                    $completion = $course['total_lessons'] > 0 ? 
                                        ($course['completed_lessons'] / $course['total_lessons']) * 100 : 0;
                                    $course_name = $lang === 'english' ? $course['course_name_en'] : $course['course_name_rw'];
                                ?>
                                <div class="course-progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="course-name"><?php echo htmlspecialchars($course_name); ?></div>
                                        <small class="text-muted">
                                            <?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?> 
                                            <?php echo $lang === 'english' ? 'completed' : 'byarangiye'; ?>
                                        </small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $completion; ?>%" 
                                             aria-valuenow="<?php echo $completion; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo round($completion); ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">
                                <?php echo $lang === 'english' 
                                    ? 'No course progress yet. Start learning!' 
                                    : 'Nta terambere ry\'amasomo. Tangira kwiga!'; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Exam History -->
                    <div class="progress-section">
                        <h3 class="section-title">
                            <i class="fas fa-history"></i> 
                            <?php echo $lang === 'english' ? 'Recent Exams' : 'Ibizamini Byaheruka'; ?>
                        </h3>
                        
                        <?php if (!empty($recent_exams)): ?>
                            <?php foreach ($recent_exams as $exam): ?>
                                <?php 
                                    $exam_name = $lang === 'english' ? $exam['exam_name_en'] : $exam['exam_name_rw'];
                                    $passed = $exam['passed'];
                                ?>
                                <div class="exam-history-item">
                                    <div class="exam-history-info">
                                        <h6><?php echo htmlspecialchars($exam_name); ?> (<?php echo $exam['exam_code']; ?>)</h6>
                                        <small>
                                            <i class="far fa-clock"></i> <?php echo format_datetime($exam['start_time']); ?>
                                        </small>
                                    </div>
                                    <div class="exam-score">
                                        <p class="score-value <?php echo $passed ? 'passed' : 'failed'; ?>">
                                            <?php echo round($exam['score_percentage']); ?>%
                                        </p>
                                        <span class="status-badge <?php echo $passed ? 'passed' : 'failed'; ?>">
                                            <?php echo $passed ? 
                                                ($lang === 'english' ? 'Passed' : 'Yatsinze') : 
                                                ($lang === 'english' ? 'Failed' : 'Yananiwe'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">
                                <?php echo $lang === 'english' 
                                    ? 'No exam history yet. Take your first exam!' 
                                    : 'Nta mateka y\'ibizamini. Kora ikizamini cyawe cya mbere!'; ?>
                            </p>
                            <a href="exams.php" class="btn btn-primary">
                                <i class="fas fa-clipboard-check"></i> 
                                <?php echo $lang === 'english' ? 'Browse Exams' : 'Reba Ibizamini'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-4">
                    <!-- Available Exams -->
                    <div class="progress-section">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-list"></i> 
                            <?php echo $lang === 'english' ? 'Available Exams' : 'Ibizamini Bihari'; ?>
                        </h3>
                        
                        <?php foreach ($available_exams as $exam): ?>
                            <?php 
                                $exam_name = $lang === 'english' ? $exam['exam_name_en'] : $exam['exam_name_rw'];
                            ?>
                            <div class="exam-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="exam-code"><?php echo $exam['exam_code']; ?></div>
                                    <?php if ($exam['is_free']): ?>
                                        <span class="free-badge">FREE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="exam-name"><?php echo truncate_text($exam_name, 60); ?></div>
                                <div class="exam-meta mb-3">
                                    <i class="fas fa-question-circle"></i> <?php echo $exam['total_questions']; ?> 
                                    <?php echo $lang === 'english' ? 'questions' : 'ibibazo'; ?>
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock"></i> <?php echo $exam['time_limit_minutes']; ?> 
                                    <?php echo $lang === 'english' ? 'min' : 'iminota'; ?>
                                </div>
                                <a href="exam-start.php?id=<?php echo $exam['exam_id']; ?>" class="btn btn-take-exam btn-block">
                                    <?php echo $lang === 'english' ? 'Take Exam' : 'Kora Ikizamini'; ?> →
                                </a>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="exams.php" class="btn btn-outline-primary btn-block mt-3">
                            <?php echo $lang === 'english' ? 'View All Exams' : 'Reba Ibizamini Byose'; ?> →
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