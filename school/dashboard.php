<?php
/**
 * School Dashboard
 * 
 * Main dashboard for driving schools
 * Manage students and track progress
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';


// Require school login
require_role('school');

$school = get_logged_in_user();
$school_id = get_user_id();

// Get school's subscription
$subscription = get_user_subscription($school_id);

// Calculate student limits based on subscription
$student_limit = 0;
if ($subscription) {
    switch ($subscription['subscription_type']) {
        case 'basic':
            $student_limit = 50;
            break;
        case 'standard':
            $student_limit = 150;
            break;
        case 'premium':
            $student_limit = 999999; // Unlimited
            break;
    }
}

// Generate school code if not exists
if (empty($school['school_code'])) {
    $generated_code = 'SCH' . str_pad($school_id, 3, '0', STR_PAD_LEFT);
    db_query("UPDATE users SET school_code = ? WHERE user_id = ?", [$generated_code, $school_id]);
    $school['school_code'] = $generated_code;
}

// Build school info directly from users table
$school_info = [
    'school_name' => $school['full_name'],
    'school_code' => $school['school_code'],
    'location' => 'Kigali, Rwanda',
    'contact_person' => $school['full_name'],
    'email' => $school['email'],
    'phone' => $school['phone'],
    'status' => $school['status']
];

// Get statistics
$stats = [
    'total_students' => db_fetch(
        "SELECT COUNT(*) as count FROM school_students WHERE school_id = ? AND status = 'active'", 
        [$school_id]
    )['count'],
    'active_exams' => db_fetch(
        "SELECT COUNT(DISTINCT ea.user_id) as count 
         FROM exam_attempts ea 
         JOIN school_students ss ON ea.user_id = ss.student_id 
         WHERE ss.school_id = ? AND ea.status = 'in_progress'", 
        [$school_id]
    )['count'],
    'completed_exams' => db_fetch(
        "SELECT COUNT(*) as count 
         FROM exam_attempts ea 
         JOIN school_students ss ON ea.user_id = ss.student_id 
         WHERE ss.school_id = ? AND ea.status = 'completed'", 
        [$school_id]
    )['count'],
    'pass_rate' => db_fetch(
        "SELECT COALESCE(AVG(passed) * 100, 0) as rate 
         FROM exam_attempts ea 
         JOIN school_students ss ON ea.user_id = ss.student_id 
         WHERE ss.school_id = ? AND ea.status = 'completed'", 
        [$school_id]
    )['rate']
];

// Get recent students
$recent_students = db_fetch_all(
    "SELECT u.*, ss.enrollment_date, ss.status
     FROM school_students ss
     JOIN users u ON ss.student_id = u.user_id
     WHERE ss.school_id = ?
     ORDER BY ss.enrollment_date DESC
     LIMIT 5",
    [$school_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
            --school-color: #7B1FA2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: var(--school-color);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .top-nav .navbar-brand {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .top-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            margin: 0 10px;
            font-weight: 600;
        }
        
        .main-content {
            margin-top: 80px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--school-color);
        }
        
        .school-info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border-left: 5px solid var(--school-color);
        }
        
        .school-badge {
            background: var(--school-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
            display: inline-block;
            margin-right: 10px;
        }
        
        .subscription-badge {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(123, 31, 162, 0.2);
        }
        
        .stat-icon {
            font-size: 48px;
            color: var(--school-color);
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: var(--school-color);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #6A1B8A;
            color: white;
            text-decoration: none;
            transform: scale(1.05);
        }
        
        .recent-students-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .student-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--school-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-school"></i> <?php echo APP_NAME; ?> - School
                </a>
                
                <div class="d-flex align-items-center">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-users"></i> Students
                    </a>
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    
                    <div class="dropdown ml-3">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo explode(' ', $school['full_name'])[0]; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="../public/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-school"></i> School Dashboard
                </h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($school['full_name']); ?>!</p>
            </div>

            <!-- Flash Messages -->
            <?php if (has_flash_message()): ?>
                <?php $flash = get_flash_message(); ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- School Info Card -->
            <div class="school-info-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4>
                            <span class="school-badge">
                                <i class="fas fa-school"></i> <?php echo htmlspecialchars($school_info['school_code'] ?? 'N/A'); ?>
                            </span>
                            <?php echo htmlspecialchars($school_info['school_name'] ?? 'School Name'); ?>
                        </h4>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($school_info['location'] ?? 'Location'); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <?php if ($subscription): ?>
                            <span class="subscription-badge">
                                <i class="fas fa-check-circle"></i> ACTIVE
                            </span>
                            <p class="mb-0 mt-2">
                                <small>
                                    <strong>Plan:</strong> <?php echo ucfirst($subscription['subscription_type']); ?><br>
                                    <strong>Students:</strong> <?php echo $stats['total_students']; ?> / <?php echo $student_limit == 999999 ? 'Unlimited' : $student_limit; ?><br>
                                    <strong>Expires:</strong> <?php echo format_date($subscription['end_date']); ?>
                                </small>
                            </p>
                        <?php else: ?>
                            <a href="../student/pricing.php" class="btn btn-warning">
                                <i class="fas fa-exclamation-triangle"></i> No Active Subscription
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['active_exams']); ?></div>
                            <div class="stat-label">Active Exams</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['completed_exams']); ?></div>
                            <div class="stat-label">Completed Exams</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-value"><?php echo round($stats['pass_rate']); ?>%</div>
                            <div class="stat-label">Pass Rate</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h5 style="color: var(--school-color); margin-bottom: 20px;">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h5>
                <a href="add-student.php" class="action-btn">
                    <i class="fas fa-user-plus"></i> Add New Student
                </a>
                <a href="students.php" class="action-btn">
                    <i class="fas fa-users"></i> View All Students
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i> Generate Reports
                </a>
                <a href="../student/pricing.php" class="action-btn">
                    <i class="fas fa-credit-card"></i> Manage Subscription
                </a>
            </div>

            <!-- Recent Students -->
            <div class="recent-students-card">
                <h5 style="color: var(--school-color); margin-bottom: 20px;">
                    <i class="fas fa-history"></i> Recently Added Students
                </h5>
                
                <?php if (empty($recent_students)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No students yet. Add your first student!</p>
                        <a href="add-student.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Student
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_students as $student): ?>
                        <div class="student-item">
                            <div class="d-flex align-items-center">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($student['email']); ?> • 
                                        Added <?php echo time_ago($student['enrollment_date']); ?>
                                    </small>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-success"><?php echo ucfirst($student['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <a href="students.php" class="btn btn-outline-primary">
                            View All Students <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>