<?php
/**
 * View User Details
 * 
 * Detailed view of user profile, exams, and payments
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();

// Get user ID
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    set_flash_message('error', 'Invalid user ID');
    redirect('users.php');
}

// Get user details
$user = db_fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);

if (!$user) {
    set_flash_message('error', 'User not found');
    redirect('users.php');
}

// Get user subscription
$subscription = get_user_subscription($user_id);

// Get user analytics
$analytics = db_fetch("SELECT * FROM student_analytics WHERE user_id = ?", [$user_id]);

// Get exam history
$exams = db_fetch_all(
    "SELECT ea.*, e.exam_name_en, e.exam_code 
     FROM exam_attempts ea 
     JOIN exams e ON ea.exam_id = e.exam_id 
     WHERE ea.user_id = ? AND ea.status = 'completed'
     ORDER BY ea.start_time DESC 
     LIMIT 20",
    [$user_id]
);

// Get payment history
$payments = db_fetch_all(
    "SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC LIMIT 10",
    [$user_id]
);

// Get activity log
$activities = db_fetch_all(
    "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
    [$user_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - <?php echo htmlspecialchars($user['full_name']); ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --admin-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: var(--admin-color);
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
        
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-color), #c82333);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--admin-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
        }
        
        .stat-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--admin-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .btn-action {
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: 600;
            border: none;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-shield-alt"></i> <?php echo APP_NAME; ?> - Admin
                </a>
                
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle"></i> Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-inbox"></i> Applications
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo explode(' ', $admin['full_name'])[0]; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="profile.php">Profile</a>
                        <a class="dropdown-item" href="settings.php">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="../public/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="user-avatar-large">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div>
                            <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'student' ? 'success' : 'primary'); ?> mr-2">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 text-right">
                        <a href="user-edit.php?id=<?php echo $user_id; ?>" class="btn btn-action btn-warning">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <a href="users.php" class="btn btn-action btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-4">
                    <!-- User Info -->
                    <div class="info-card">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i> User Information
                        </h3>
                        
                        <div class="info-row">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Language</div>
                            <div class="info-value"><?php echo ucfirst($user['language_preference'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Registration Date</div>
                            <div class="info-value"><?php echo format_datetime($user['created_at']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Last Login</div>
                            <div class="info-value"><?php echo $user['last_login'] ? time_ago($user['last_login']) : 'Never'; ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Email Verified</div>
                            <div class="info-value">
                                <?php if ($user['email_verified_at']): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-circle"></i> Not Verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Info -->
                    <?php if ($subscription): ?>
                    <div class="info-card">
                        <h3 class="section-title">
                            <i class="fas fa-crown"></i> Subscription
                        </h3>
                        
                        <div class="info-row">
                            <div class="info-label">Type</div>
                            <div class="info-value">
                                <span class="badge badge-info"><?php echo ucfirst($subscription['subscription_type']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="badge badge-<?php echo $subscription['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($subscription['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Start Date</div>
                            <div class="info-value"><?php echo format_date($subscription['start_date']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">End Date</div>
                            <div class="info-value"><?php echo format_date($subscription['end_date']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Amount Paid</div>
                            <div class="info-value"><?php echo format_currency($subscription['amount']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div class="col-md-8">
                    <!-- Statistics -->
                    <?php if ($analytics): ?>
                    <div class="info-card">
                        <h3 class="section-title">
                            <i class="fas fa-chart-line"></i> Learning Statistics
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo $analytics['total_lessons_completed']; ?></div>
                                    <div class="stat-label">Lessons</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo $analytics['total_exams_taken']; ?></div>
                                    <div class="stat-label">Exams</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo round($analytics['average_exam_score']); ?>%</div>
                                    <div class="stat-label">Avg Score</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo $analytics['current_streak_days']; ?></div>
                                    <div class="stat-label">Day Streak</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Exam History -->
                    <div class="info-card">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-check"></i> Exam History
                        </h3>
                        
                        <?php if (empty($exams)): ?>
                            <p class="text-muted text-center py-4">No exams taken yet</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams as $exam): ?>
                                            <tr>
                                                <td><?php echo $exam['exam_code']; ?></td>
                                                <td>
                                                    <strong><?php echo round($exam['score_percentage']); ?>%</strong>
                                                    <small class="text-muted">
                                                        (<?php echo $exam['correct_answers']; ?>/<?php echo $exam['total_questions']; ?>)
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($exam['passed']): ?>
                                                        <span class="badge badge-success">Passed</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo format_date($exam['start_time']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment History -->
                    <?php if (!empty($payments)): ?>
                    <div class="info-card">
                        <h3 class="section-title">
                            <i class="fas fa-money-bill"></i> Payment History
                        </h3>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo format_date($payment['payment_date']); ?></td>
                                            <td><?php echo format_currency($payment['amount']); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>