<?php
/**
 * Admin Dashboard
 * 
 * Main dashboard for administrators to manage the platform
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$user = get_logged_in_user();

// Get platform statistics
$stats = [];

// Total users by role
$stats['total_students'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
$stats['total_schools'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'school'")['count'];
$stats['total_agents'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'")['count'];
$stats['total_admins'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];

// Active subscriptions
$stats['active_subscriptions'] = db_fetch("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active' AND end_date >= CURDATE()")['count'];

// Total revenue
$stats['total_revenue'] = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'")['total'];

// Revenue this month
$stats['revenue_this_month'] = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")['total'];

// Total exams taken
$stats['total_exams_taken'] = db_fetch("SELECT COUNT(*) as count FROM exam_attempts WHERE status = 'completed'")['count'];

// Exams taken today
$stats['exams_today'] = db_fetch("SELECT COUNT(*) as count FROM exam_attempts WHERE DATE(start_time) = CURDATE()")['count'];

// Pending applications
$stats['pending_school_apps'] = db_fetch("SELECT COUNT(*) as count FROM school_applications WHERE status = 'pending'")['count'];
$stats['pending_agent_apps'] = db_fetch("SELECT COUNT(*) as count FROM agent_applications WHERE status = 'pending'")['count'];

// Recent users
$recent_users = db_fetch_all("SELECT user_id, email, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");

// Recent payments
$recent_payments = db_fetch_all("SELECT p.*, u.full_name, u.email FROM payments p JOIN users u ON p.user_id = u.user_id ORDER BY p.payment_date DESC LIMIT 10");

// Recent exam attempts
$recent_exams = db_fetch_all("SELECT ea.*, u.full_name, e.exam_name_en, e.exam_code FROM exam_attempts ea JOIN users u ON ea.user_id = u.user_id JOIN exams e ON ea.exam_id = e.exam_id WHERE ea.status = 'completed' ORDER BY ea.start_time DESC LIMIT 10");

$lang = $user['language_preference'] ?? 'english';
$page_title = $lang === 'english' ? 'Admin Dashboard' : 'Dashboard ya Admin';
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
    
    <!-- Custom CSS (Similar to student dashboard) -->
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
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
            transition: all 0.3s;
        }
        
        .top-nav .nav-link:hover {
            color: white;
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--admin-color) 0%, #c82333 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .welcome-section h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
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
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.15);
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
        
        .stat-icon.red { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .stat-icon.blue { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); }
        .stat-icon.teal { background: linear-gradient(135deg, #20c997 0%, #1aa179 100%); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--admin-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--admin-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table {
            font-size: 14px;
        }
        
        .badge-role {
            padding: 5px 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .badge-admin { background: #dc3545; color: white; }
        .badge-school { background: #007bff; color: white; }
        .badge-agent { background: #6f42c1; color: white; }
        .badge-student { background: #28a745; color: white; }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container">
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
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-inbox"></i> Applications
                            <?php if ($stats['pending_school_apps'] + $stats['pending_agent_apps'] > 0): ?>
                                <span class="badge badge-warning">
                                    <?php echo $stats['pending_school_apps'] + $stats['pending_agent_apps']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-inbox"></i> questions.php
                        </a>
                        <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-inbox"></i> payments.php
                        </a>
                    </li>
                    </li>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="content.php">
                            <i class="fas fa-book"></i> Content
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo explode(' ', $user['full_name'])[0]; ?>
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
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Flash Messages -->
            <?php echo display_flash_message(); ?>
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>
                    <i class="fas fa-shield-alt"></i> Admin Dashboard
                </h2>
                <p>Platform overview and management</p>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_schools']); ?></div>
                        <div class="stat-label">Schools</div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon teal">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_agents']); ?></div>
                        <div class="stat-label">Agents</div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['active_subscriptions']); ?></div>
                        <div class="stat-label">Active Subscriptions</div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['exams_today']); ?></div>
                        <div class="stat-label">Exams Today</div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value"><?php echo format_currency($stats['revenue_this_month']); ?></div>
                        <div class="stat-label">Revenue (Month)</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Users -->
                <div class="col-md-6">
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-user-plus"></i> Recent Users
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="badge-role badge-<?php echo $u['role']; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_date($u['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="col-md-6">
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-money-bill"></i> Recent Payments
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                                        <td><?php echo format_currency($p['amount']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo str_replace('_', ' ', $p['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_date($p['payment_date']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Exam Attempts -->
            <div class="row">
                <div class="col-12">
                    <div class="section-card">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-list"></i> Recent Exam Attempts
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_exams as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['full_name']); ?></td>
                                        <td><?php echo $exam['exam_code']; ?> - <?php echo htmlspecialchars($exam['exam_name_en']); ?></td>
                                        <td><?php echo round($exam['score_percentage']); ?>%</td>
                                        <td>
                                            <?php if ($exam['passed']): ?>
                                                <span class="badge badge-success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo format_datetime($exam['start_time']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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