<?php
/**
 * Admin - Agent Payout Management
 * 
 * Approve or reject agent payout requests
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

// Require admin login
require_role('admin');

$admin_id = get_user_id();

// Get all payout requests
$payouts = db_fetch_all(
    "SELECT pr.*, u.full_name, u.email, u.phone, u.agent_code,
     (SELECT COUNT(*) FROM referrals WHERE agent_id = pr.agent_id) as total_referrals,
     (SELECT COALESCE(SUM(commission_amount), 0) FROM referrals WHERE agent_id = pr.agent_id AND commission_paid = 0) as pending_balance
     FROM payout_requests pr
     JOIN users u ON pr.agent_id = u.user_id
     ORDER BY pr.request_date DESC"
);

// Get statistics
$stats = [
    'pending' => db_fetch("SELECT COUNT(*) as count FROM payout_requests WHERE status = 'pending'")['count'],
    'approved' => db_fetch("SELECT COUNT(*) as count FROM payout_requests WHERE status = 'approved'")['count'],
    'paid' => db_fetch("SELECT COUNT(*) as count FROM payout_requests WHERE status = 'paid'")['count'],
    'total_paid' => db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payout_requests WHERE status = 'paid'")['total']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Payouts - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
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
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: #1E2A5E;
            padding-top: 70px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 25px;
            margin: 5px 15px;
            border-radius: 10px;
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 90px 30px 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .payouts-table-card {
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
        
        .badge-approved {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-paid {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .badge-rejected {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            border: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-mark-paid {
            background: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            border: none;
            font-size: 12px;
            font-weight: 600;
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
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand" style="color: white; text-decoration: none;">
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </a>
                
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> Admin
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="../public/logout.php">
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
                <a class="nav-link" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="agent-payouts.php">
                    <i class="fas fa-wallet"></i> Agent Payouts
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h1 class="page-title">
                <i class="fas fa-wallet"></i> Agent Payout Management
            </h1>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['paid']; ?></div>
                        <div class="stat-label">Paid Out</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($stats['total_paid']); ?> RWF</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
            </div>

            <!-- Payouts Table -->
            <div class="payouts-table-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Details</th>
                                <th>Referrals</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payouts)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No payout requests</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payouts as $payout): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($payout['full_name']); ?></strong><br>
                                            <small class="text-muted">
                                                Code: <?php echo htmlspecialchars($payout['agent_code']); ?><br>
                                                <?php echo htmlspecialchars($payout['email']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($payout['amount']); ?> RWF</strong>
                                        </td>
                                        <td>
                                            <?php if ($payout['payment_method'] === 'momo'): ?>
                                                <i class="fas fa-mobile-alt"></i> MoMo
                                            <?php else: ?>
                                                <i class="fas fa-university"></i> Bank
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($payout['payment_method'] === 'momo'): ?>
                                                    <?php echo htmlspecialchars($payout['phone_number']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($payout['bank_name']); ?><br>
                                                    <?php echo htmlspecialchars($payout['bank_account']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><?php echo $payout['total_referrals']; ?></td>
                                        <td><?php echo number_format($payout['pending_balance']); ?> RWF</td>
                                        <td>
                                            <?php if ($payout['status'] === 'pending'): ?>
                                                <span class="badge-pending">PENDING</span>
                                            <?php elseif ($payout['status'] === 'approved'): ?>
                                                <span class="badge-approved">APPROVED</span>
                                            <?php elseif ($payout['status'] === 'paid'): ?>
                                                <span class="badge-paid">PAID</span>
                                            <?php else: ?>
                                                <span class="badge-rejected">REJECTED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo time_ago($payout['request_date']); ?></td>
                                        <td>
                                            <?php if ($payout['status'] === 'pending'): ?>
                                                <button class="btn btn-approve btn-sm mb-1" onclick="approvePayout(<?php echo $payout['payout_id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn btn-reject btn-sm" onclick="rejectPayout(<?php echo $payout['payout_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            <?php elseif ($payout['status'] === 'approved'): ?>
                                                <button class="btn btn-mark-paid btn-sm" onclick="markAsPaid(<?php echo $payout['payout_id']; ?>)">
                                                    <i class="fas fa-check-double"></i> Mark Paid
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
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function approvePayout(payoutId) {
            if (confirm('Approve this payout request?')) {
                $.post('payout-approve.php', { payout_id: payoutId }, function(response) {
                    if (response.success) {
                        alert('Payout approved!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            }
        }
        
        function rejectPayout(payoutId) {
            const reason = prompt('Enter rejection reason:');
            if (reason) {
                $.post('payout-reject.php', { payout_id: payoutId, reason: reason }, function(response) {
                    if (response.success) {
                        alert('Payout rejected!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            }
        }
        
        function markAsPaid(payoutId) {
            if (confirm('Mark this payout as PAID? This will update agent balances.')) {
                $.post('payout-mark-paid.php', { payout_id: payoutId }, function(response) {
                    if (response.success) {
                        alert('Payout marked as paid!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            }
        }
    </script>
</body>
</html>