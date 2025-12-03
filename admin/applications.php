<?php
/**
 * Applications Management
 * 
 * Review and manage school & agent applications
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();
$lang = $admin['language_preference'] ?? 'english';

// Get active tab
$active_tab = isset($_GET['tab']) ? clean_input($_GET['tab']) : 'schools';

// Get school applications - FIXED: use school_id
$school_apps = db_fetch_all(
    "SELECT sa.*, u.full_name, u.email, u.phone, u.created_at as user_created
     FROM school_applications sa
     JOIN users u ON sa.school_id = u.user_id
     ORDER BY 
        CASE sa.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        sa.created_at DESC"
);

// Get agent applications - FIXED: use agent_id
$agent_apps = db_fetch_all(
    "SELECT aa.*, u.full_name, u.email, u.phone, u.created_at as user_created
     FROM agent_applications aa
     JOIN users u ON aa.agent_id = u.user_id
     ORDER BY 
        CASE aa.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        aa.created_at DESC"
);

// Count pending applications
$pending_schools = 0;
$pending_agents = 0;

foreach ($school_apps as $app) {
    if ($app['status'] === 'pending') $pending_schools++;
}

foreach ($agent_apps as $app) {
    if ($app['status'] === 'pending') $pending_agents++;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Applications' : 'Amasaba'; ?> - <?php echo APP_NAME; ?></title>
    
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
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--admin-color);
            margin: 0;
        }
        
        .stats-cards {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-icon.schools { color: #007bff; }
        .stat-icon.agents { color: #6f42c1; }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .pending-badge {
            background: #ffc107;
            color: #000;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 10px;
        }
        
        .applications-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 600;
            padding: 15px 30px;
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--admin-color);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--admin-color);
            border-bottom-color: var(--admin-color);
            background: transparent;
        }
        
        .application-item {
            background: #f8f9fa;
            border-left: 5px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .application-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .application-item.pending {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        
        .application-item.approved {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        
        .application-item.rejected {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .applicant-info {
            margin-bottom: 15px;
        }
        
        .applicant-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .applicant-contact {
            color: #6c757d;
            font-size: 14px;
        }
        
        .application-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-view:hover {
            background: #0056b3;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <a href="dashboard.php" style="color: white; text-decoration: none; font-size: 24px; font-weight: 700;">
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
                        <i class="fas fa-users"></i> <?php echo $lang === 'english' ? 'Users' : 'Abakoresha'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="questions.php">
                        <i class="fas fa-question-circle"></i> <?php echo $lang === 'english' ? 'Questions' : 'Ibibazo'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="applications.php">
                        <i class="fas fa-inbox"></i> <?php echo $lang === 'english' ? 'Applications' : 'Amasaba'; ?>
                        <?php if ($pending_schools + $pending_agents > 0): ?>
                            <span class="badge badge-warning ml-1"><?php echo $pending_schools + $pending_agents; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payments.php">
                        <i class="fas fa-credit-card"></i> <?php echo $lang === 'english' ? 'Payments' : 'Kwishyura'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="agent-payouts.php">
                        <i class="fas fa-wallet"></i> <?php echo $lang === 'english' ? 'Payouts' : 'Kwishyuza'; ?>
                    </a>
                </li>
            </ul>
            
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-user-shield"></i> System
                </button>
                <div class="dropdown-menu dropdown-menu-right">
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
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-inbox"></i> 
                    <?php echo $lang === 'english' ? 'Applications Management' : 'Gucunga Amasaba'; ?>
                </h1>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon schools">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="stat-value"><?php echo count($school_apps); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'School Applications' : 'Amasaba y\'Amashuri'; ?>
                                <?php if ($pending_schools > 0): ?>
                                    <span class="pending-badge"><?php echo $pending_schools; ?> pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon agents">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-value"><?php echo count($agent_apps); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Agent Applications' : 'Amasaba y\'Abahagarariye'; ?>
                                <?php if ($pending_agents > 0): ?>
                                    <span class="pending-badge"><?php echo $pending_agents; ?> pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications Card -->
            <div class="applications-card">
                <!-- Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'schools' ? 'active' : ''; ?>" 
                           href="?tab=schools">
                            <i class="fas fa-school"></i> 
                            <?php echo $lang === 'english' ? 'School Applications' : 'Amasaba y\'Amashuri'; ?>
                            <?php if ($pending_schools > 0): ?>
                                <span class="badge badge-warning ml-2"><?php echo $pending_schools; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab === 'agents' ? 'active' : ''; ?>" 
                           href="?tab=agents">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo $lang === 'english' ? 'Agent Applications' : 'Amasaba y\'Abahagarariye'; ?>
                            <?php if ($pending_agents > 0): ?>
                                <span class="badge badge-warning ml-2"><?php echo $pending_agents; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Schools Tab -->
                    <?php if ($active_tab === 'schools'): ?>
                        <?php if (empty($school_apps)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4><?php echo $lang === 'english' ? 'No school applications yet' : 'Nta masaba y\'ishuri aha'; ?></h4>
                                <p class="text-muted">
                                    <?php echo $lang === 'english' 
                                        ? 'School applications will appear here when submitted' 
                                        : 'Amasaba y\'amashuri azagaragara hano iyo yatanzwe'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($school_apps as $app): ?>
                                <div class="application-item <?php echo $app['status']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="applicant-info">
                                                <div class="applicant-name">
                                                    <?php echo htmlspecialchars($app['school_name']); ?>
                                                </div>
                                                <div class="applicant-contact">
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($app['full_name']); ?> | 
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email']); ?> | 
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['phone']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="application-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">School Code</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($app['school_code'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Location</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($app['location'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Applied On</span>
                                                    <span class="detail-value"><?php echo format_date($app['created_at']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Status</span>
                                                    <span class="status-badge <?php echo $app['status']; ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-right">
                                            <div class="action-buttons justify-content-end">
                                                <?php if ($app['status'] === 'pending'): ?>
                                                    <button class="btn btn-action btn-approve" onclick="approveApplication('school', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-action btn-reject" onclick="rejectApplication('school', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-action btn-view" onclick="viewApplication('school', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Agents Tab -->
                    <?php if ($active_tab === 'agents'): ?>
                        <?php if (empty($agent_apps)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4><?php echo $lang === 'english' ? 'No agent applications yet' : 'Nta masaba y\'abahagarariye aha'; ?></h4>
                                <p class="text-muted">
                                    <?php echo $lang === 'english' 
                                        ? 'Agent applications will appear here when submitted' 
                                        : 'Amasaba y\'abahagarariye azagaragara hano iyo yatanzwe'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($agent_apps as $app): ?>
                                <div class="application-item <?php echo $app['status']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="applicant-info">
                                                <div class="applicant-name">
                                                    <?php echo htmlspecialchars($app['full_name']); ?>
                                                </div>
                                                <div class="applicant-contact">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email']); ?> | 
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['phone']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="application-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Agent Code</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($app['agent_code'] ?? 'Pending'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">ID Number</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($app['id_number'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Applied On</span>
                                                    <span class="detail-value"><?php echo format_date($app['created_at']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Status</span>
                                                    <span class="status-badge <?php echo $app['status']; ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-right">
                                            <div class="action-buttons justify-content-end">
                                                <?php if ($app['status'] === 'pending'): ?>
                                                    <button class="btn btn-action btn-approve" onclick="approveApplication('agent', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-action btn-reject" onclick="rejectApplication('agent', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-action btn-view" onclick="viewApplication('agent', <?php echo $app['application_id']; ?>)">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function approveApplication(type, applicationId) {
            if (confirm('Are you sure you want to APPROVE this application?')) {
                fetch('application-approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'type=' + type + '&application_id=' + applicationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
        
        function rejectApplication(type, applicationId) {
            const reason = prompt('Please provide a reason for rejection (optional):');
            
            if (reason !== null) { // User clicked OK (even if empty)
                fetch('application-reject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'type=' + type + '&application_id=' + applicationId + '&reason=' + encodeURIComponent(reason)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application rejected');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
        
        function viewApplication(type, applicationId) {
            window.location.href = 'application-view.php?type=' + type + '&id=' + applicationId;
        }
    </script>
</body>
</html>