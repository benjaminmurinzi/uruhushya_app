<?php
/**
 * Admin Users Management
 * 
 * View and manage all platform users
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$user = get_logged_in_user();

// Get filters
$role_filter = isset($_GET['role']) ? clean_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total users
$count_sql = "SELECT COUNT(*) as total FROM users u $where_clause";
$total_users = db_fetch($count_sql, $params)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users with subscription info
$users_sql = "SELECT u.*, 
              s.subscription_type, s.status as subscription_status, s.end_date as subscription_end,
              (SELECT COUNT(*) FROM exam_attempts WHERE user_id = u.user_id AND status = 'completed') as exams_taken
              FROM users u
              LEFT JOIN subscriptions s ON u.user_id = s.user_id AND s.status = 'active'
              $where_clause
              ORDER BY u.created_at DESC
              LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;

$users = db_fetch_all($users_sql, $params);

// Get role counts for filter badges
$role_counts = [
    'all' => db_fetch("SELECT COUNT(*) as count FROM users")['count'],
    'student' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'],
    'school' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'school'")['count'],
    'agent' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'")['count'],
    'admin' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    
    <style>
        :root {
            --admin-color: #dc3545;
            --admin-dark: #c82333;
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
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--admin-color);
            margin: 0;
        }
        
        .btn-add {
            background: var(--admin-color);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
        }
        
        .btn-add:hover {
            background: var(--admin-dark);
            color: white;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .role-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .role-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .role-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }
        
        .role-badge.all {
            background: #6c757d;
            color: white;
        }
        
        .role-badge.student {
            background: #28a745;
            color: white;
        }
        
        .role-badge.school {
            background: #007bff;
            color: white;
        }
        
        .role-badge.agent {
            background: #6f42c1;
            color: white;
        }
        
        .role-badge.admin {
            background: var(--admin-color);
            color: white;
        }
        
        .role-badge.active {
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transform: scale(1.05);
        }
        
        .users-table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table {
            font-size: 14px;
        }
        
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            color: var(--admin-color);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-color), var(--admin-dark));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            margin-right: 10px;
        }
        
        .badge-role {
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .badge-role.student { background: #d4edda; color: #155724; }
        .badge-role.school { background: #cce5ff; color: #004085; }
        .badge-role.agent { background: #e7d6f5; color: #4a148c; }
        .badge-role.admin { background: #f8d7da; color: #721c24; }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .badge-status.active { background: #d4edda; color: #155724; }
        .badge-status.suspended { background: #fff3cd; color: #856404; }
        .badge-status.inactive { background: #f8d7da; color: #721c24; }
        
        .subscription-badge {
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .subscription-badge.trial { background: #fff3cd; color: #856404; }
        .subscription-badge.monthly { background: #cce5ff; color: #004085; }
        .subscription-badge.yearly { background: #d4edda; color: #155724; }
        .subscription-badge.expired { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-view:hover {
            background: #0056b3;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            color: #000;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        
        .pagination {
            margin-top: 20px;
        }
        
        .pagination .page-link {
            color: var(--admin-color);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--admin-color);
            border-color: var(--admin-color);
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-users"></i> User Management
                </h1>
                <a href="user-add.php" class="btn btn-add">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <!-- Role Filter Badges -->
                <div class="role-filter">
                    <a href="users.php" class="role-badge all <?php echo empty($role_filter) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> All Users
                        <span class="badge badge-light"><?php echo $role_counts['all']; ?></span>
                    </a>
                    <a href="users.php?role=student" class="role-badge student <?php echo $role_filter === 'student' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Students
                        <span class="badge badge-light"><?php echo $role_counts['student']; ?></span>
                    </a>
                    <a href="users.php?role=school" class="role-badge school <?php echo $role_filter === 'school' ? 'active' : ''; ?>">
                        <i class="fas fa-school"></i> Schools
                        <span class="badge badge-light"><?php echo $role_counts['school']; ?></span>
                    </a>
                    <a href="users.php?role=agent" class="role-badge agent <?php echo $role_filter === 'agent' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Agents
                        <span class="badge badge-light"><?php echo $role_counts['agent']; ?></span>
                    </a>
                    <a href="users.php?role=admin" class="role-badge admin <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Admins
                        <span class="badge badge-light"><?php echo $role_counts['admin']; ?></span>
                    </a>
                </div>

                <!-- Search and Status Filter -->
                <form method="GET" action="users.php" class="form-inline">
                    <?php if ($role_filter): ?>
                        <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group mr-3">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="Search by name, email, or phone..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="min-width: 300px;"
                        >
                    </div>
                    
                    <div class="form-group mr-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Users Table -->
            <div class="users-table-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Subscription</th>
                                <th>Exams</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No users found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($u['full_name'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge-role <?php echo $u['role']; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?php echo $u['status']; ?>">
                                                <?php echo ucfirst($u['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['subscription_type']): ?>
                                                <span class="subscription-badge <?php echo $u['subscription_type']; ?>">
                                                    <?php echo ucfirst($u['subscription_type']); ?>
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo format_date($u['subscription_end']); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="subscription-badge expired">No Subscription</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $u['exams_taken']; ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo format_date($u['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="user-view.php?id=<?php echo $u['user_id']; ?>" class="btn btn-action btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="user-edit.php?id=<?php echo $u['user_id']; ?>" class="btn btn-action btn-edit" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($u['role'] !== 'admin'): ?>
                                                <button class="btn btn-action btn-delete" onclick="deleteUser(<?php echo $u['user_id']; ?>)" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Results Info -->
                <div class="text-center text-muted">
                    <small>
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_users); ?> of <?php echo $total_users; ?> users
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                // Send AJAX request to delete
                fetch('user-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + userId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user');
                });
            }
        }
    </script>
</body>
</html>