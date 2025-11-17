<?php
/**
 * Agent Referrals Page
 * 
 * View all referred students with detailed information
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require agent login
require_role('agent');

$agent = get_logged_in_user();
$agent_id = get_user_id();

// Get filters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where_clauses = ["r.agent_id = ?"];
$params = [$agent_id];

if ($status_filter !== 'all') {
    $where_clauses[] = "r.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = implode(' AND ', $where_clauses);

// Get all referrals
$referrals_sql = "SELECT r.*, u.full_name, u.email, u.phone,
                  s.subscription_type, s.amount as subscription_amount, s.status as subscription_status,
                  (SELECT COUNT(*) FROM exam_attempts WHERE user_id = u.user_id AND status = 'completed') as exams_completed
                  FROM referrals r
                  JOIN users u ON r.student_id = u.user_id
                  LEFT JOIN subscriptions s ON r.subscription_id = s.subscription_id
                  WHERE {$where_sql}
                  ORDER BY r.referral_date DESC";

$referrals = db_fetch_all($referrals_sql, $params);

// Get statistics
$stats = [
    'total' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ?", [$agent_id])['count'],
    'pending' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ? AND status = 'pending'", [$agent_id])['count'],
    'active' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ? AND status = 'active'", [$agent_id])['count'],
    'paid' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ? AND commission_paid = 1", [$agent_id])['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --agent-color: #FF6F00;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: var(--agent-color);
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
            color: var(--agent-color);
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
            color: var(--agent-color);
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
        
        .referrals-table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table th {
            border-top: none;
            color: var(--agent-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-pending {
            background: #ffc107;
            color: #000;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-paid {
            background: #007bff;
            color: white;
        }
        
        .badge-cancelled {
            background: #dc3545;
            color: white;
        }
        
        .commission-amount {
            font-size: 16px;
            font-weight: 700;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-user-tie"></i> <?php echo APP_NAME; ?> - Agent
                </a>
                
                <div class="d-flex align-items-center">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="referrals.php" class="nav-link">
                        <i class="fas fa-users"></i> Referrals
                    </a>
                    <a href="earnings.php" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i> Earnings
                    </a>
                    <a href="payouts.php" class="nav-link">
                        <i class="fas fa-wallet"></i> Payouts
                    </a>
                    
                    <div class="dropdown ml-3">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo explode(' ', $agent['full_name'])[0]; ?>
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
            <h1 class="page-title">
                <i class="fas fa-users"></i> My Referrals
            </h1>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Referrals</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['paid']; ?></div>
                            <div class="stat-label">Paid</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card">
                <form method="GET" action="referrals.php">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?php echo htmlspecialchars($search); ?>">
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

            <!-- Referrals Table -->
            <div class="referrals-table-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Contact</th>
                                <th>Subscription</th>
                                <th>Exams</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th>Referred</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($referrals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No referrals found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($referrals as $referral): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($referral['full_name']); ?></strong>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($referral['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($referral['phone']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($referral['subscription_type']): ?>
                                                <span class="badge badge-info">
                                                    <?php echo strtoupper($referral['subscription_type']); ?>
                                                </span><br>
                                                <small class="text-muted">
                                                    <?php echo number_format($referral['subscription_amount']); ?> RWF
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">No subscription</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $referral['exams_completed']; ?></strong> completed
                                        </td>
                                        <td>
                                            <?php if ($referral['commission_amount'] > 0): ?>
                                                <span class="commission-amount">
                                                    <?php echo number_format($referral['commission_amount']); ?> RWF
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">0 RWF</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($referral['commission_paid']): ?>
                                                <span class="badge-status badge-paid">PAID</span>
                                            <?php elseif ($referral['status'] === 'pending'): ?>
                                                <span class="badge-status badge-pending">PENDING</span>
                                            <?php elseif ($referral['status'] === 'active'): ?>
                                                <span class="badge-status badge-active">ACTIVE</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-cancelled">CANCELLED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo time_ago($referral['referral_date']); ?>
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