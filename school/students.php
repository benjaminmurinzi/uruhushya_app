<?php
/**
 * View All Students
 * 
 * School can view and manage all their students
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require school login
require_role('school');

$school = get_logged_in_user();
$school_id = get_user_id();

// Get filters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where_clauses = ["ss.school_id = ?"];
$params = [$school_id];

if ($status_filter !== 'all') {
    $where_clauses[] = "ss.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = implode(' AND ', $where_clauses);

// Get students with their exam stats
$students_sql = "SELECT u.*, ss.enrollment_date, ss.status as enrollment_status,
                 (SELECT COUNT(*) FROM exam_attempts WHERE user_id = u.user_id) as total_exams,
                 (SELECT COUNT(*) FROM exam_attempts WHERE user_id = u.user_id AND status = 'completed') as completed_exams,
                 (SELECT COUNT(*) FROM exam_attempts WHERE user_id = u.user_id AND status = 'completed' AND passed = 1) as passed_exams,
                 (SELECT status FROM subscriptions WHERE user_id = u.user_id ORDER BY created_at DESC LIMIT 1) as subscription_status
                 FROM school_students ss
                 JOIN users u ON ss.student_id = u.user_id
                 WHERE {$where_sql}
                 ORDER BY ss.enrollment_date DESC";

$students = db_fetch_all($students_sql, $params);

// Get statistics
$stats = [
    'total' => db_fetch("SELECT COUNT(*) as count FROM school_students WHERE school_id = ?", [$school_id])['count'],
    'active' => db_fetch("SELECT COUNT(*) as count FROM school_students WHERE school_id = ? AND status = 'active'", [$school_id])['count'],
    'inactive' => db_fetch("SELECT COUNT(*) as count FROM school_students WHERE school_id = ? AND status = 'inactive'", [$school_id])['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
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
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--school-color);
            margin-bottom: 30px;
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--school-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .filters-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .students-table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table th {
            border-top: none;
            color: var(--school-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--school-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-inactive {
            background: #6c757d;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-subscription {
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-subscription.active {
            background: #28a745;
            color: white;
        }
        
        .badge-subscription.expired {
            background: #dc3545;
            color: white;
        }
        
        .badge-subscription.trial {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view {
            background: var(--school-color);
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            border: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-view:hover {
            background: #6A1B8A;
            color: white;
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
                            <a class="dropdown-item" href="../public/logout.php">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">
                    <i class="fas fa-users"></i> All Students
                </h1>
                <a href="add-student.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Student
                </a>
            </div>

            <!-- Flash Messages -->
            <?php if (has_flash_message()): ?>
                <?php $flash = get_flash_message(); ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['inactive']; ?></div>
                            <div class="stat-label">Inactive</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card">
                <form method="GET" action="students.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="students-table-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Contact</th>
                                <th>Exams</th>
                                <th>Pass Rate</th>
                                <th>Subscription</th>
                                <th>Status</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No students found</p>
                                        <a href="add-student.php" class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i> Add Your First Student
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo $student['completed_exams']; ?></strong> / <?php echo $student['total_exams']; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $pass_rate = $student['completed_exams'] > 0 
                                                ? round(($student['passed_exams'] / $student['completed_exams']) * 100) 
                                                : 0;
                                            ?>
                                            <strong><?php echo $pass_rate; ?>%</strong>
                                        </td>
                                        <td>
                                            <?php if ($student['subscription_status']): ?>
                                                <span class="badge-subscription <?php echo $student['subscription_status']; ?>">
                                                    <?php echo strtoupper($student['subscription_status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['enrollment_status'] === 'active'): ?>
                                                <span class="badge-active">ACTIVE</span>
                                            <?php else: ?>
                                                <span class="badge-inactive">INACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo time_ago($student['enrollment_date']); ?>
                                        </td>
                                        <td>
                                            <a href="student-details.php?id=<?php echo $student['user_id']; ?>" class="btn btn-view btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>