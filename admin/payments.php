<?php
/**
 * Admin Payment Management
 * 
 * Manage and verify student payments
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();

// Get filter parameters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$method_filter = isset($_GET['method']) ? clean_input($_GET['method']) : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($method_filter !== 'all') {
    $where_clauses[] = "p.payment_method = ?";
    $params[] = $method_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(p.transaction_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get payments
$payments_sql = "SELECT p.*, u.full_name, u.email, u.phone,
                 s.subscription_type, s.plan_name
                 FROM payments p
                 JOIN users u ON p.user_id = u.user_id
                 LEFT JOIN subscriptions s ON p.subscription_id = s.subscription_id
                 {$where_sql}
                 ORDER BY p.created_at DESC";

$payments = db_fetch_all($payments_sql, $params);

// Get statistics
$stats = [
    'total_payments' => db_fetch("SELECT COUNT(*) as count FROM payments")['count'],
    'pending_payments' => db_fetch("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'],
    'completed_payments' => db_fetch("SELECT COUNT(*) as count FROM payments WHERE status = 'completed'")['count'],
    'total_revenue' => db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'")['total']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
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
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: var(--secondary-color);
            padding-top: 70px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 25px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 90px 30px 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-icon {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
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
        
        .payments-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table th {
            border-top: none;
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .badge-pending {
            background: #ffc107;
            color: #000;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-completed {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-failed {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-momo {
            background: #FFCB05;
            color: #000;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-bank {
            background: #007bff;
            color: white;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .btn-verify {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            border: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-verify:hover {
            background: #218838;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            border: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-reject:hover {
            background: #c82333;
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
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </a>
                
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo explode(' ', $admin['full_name'])[0]; ?>
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

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="nav flex-column">
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
                <a class="nav-link" href="questions.php">
                    <i class="fas fa-question-circle"></i> Questions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="applications.php">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-credit-card"></i> Payment Management
            </h1>
            <p class="text-muted">Manage and verify student payments</p>
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
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_payments']); ?></div>
                        <div class="stat-label">Total Payments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pending_payments']); ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['completed_payments']); ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_revenue']); ?> RWF</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" action="payments.php">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="method" class="form-control">
                                <option value="all" <?php echo $method_filter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                                <option value="momo" <?php echo $method_filter === 'momo' ? 'selected' : ''; ?>>Mobile Money</option>
                                <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Transaction ID, name, email..." value="<?php echo htmlspecialchars($search); ?>">
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

        <!-- Payments Table -->
        <div class="payments-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Student</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No payments found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($payment['full_name']); ?></strong></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['plan_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($payment['amount']); ?> RWF</strong>
                                    </td>
                                    <td>
                                        <?php if ($payment['payment_method'] === 'momo'): ?>
                                            <span class="badge-momo">
                                                <i class="fas fa-mobile-alt"></i> MoMo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-bank">
                                                <i class="fas fa-university"></i> Bank
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <span class="badge-pending">PENDING</span>
                                        <?php elseif ($payment['status'] === 'completed'): ?>
                                            <span class="badge-completed">COMPLETED</span>
                                        <?php else: ?>
                                            <span class="badge-failed">FAILED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <button class="btn btn-verify btn-sm" onclick="verifyPayment(<?php echo $payment['payment_id']; ?>)">
                                                <i class="fas fa-check"></i> Verify
                                            </button>
                                            <button class="btn btn-reject btn-sm" onclick="rejectPayment(<?php echo $payment['payment_id']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function verifyPayment(paymentId) {
            if (confirm('Are you sure you want to VERIFY this payment? This will activate the student\'s subscription.')) {
                $.ajax({
                    url: 'payment-verify.php',
                    method: 'POST',
                    data: { payment_id: paymentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Payment verified successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        }
        
        function rejectPayment(paymentId) {
            const reason = prompt('Enter rejection reason:');
            if (reason) {
                $.ajax({
                    url: 'payment-reject.php',
                    method: 'POST',
                    data: { 
                        payment_id: paymentId,
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Payment rejected!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        }
    </script>
</body>
</html>