<?php
/**
 * Agent Dashboard
 * 
 * Dashboard for marketing agents
 * Track referrals and earnings
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require agent login
require_role('agent');

$agent = get_logged_in_user();
$agent_id = get_user_id();
$agent_code = $agent['agent_code'];

// Get agent statistics
$stats = [
    'total_referrals' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ?", [$agent_id])['count'],
    'active_referrals' => db_fetch("SELECT COUNT(*) as count FROM referrals WHERE agent_id = ? AND status = 'active'", [$agent_id])['count'],
    'total_earnings' => db_fetch("SELECT COALESCE(SUM(commission_amount), 0) as total FROM referrals WHERE agent_id = ?", [$agent_id])['total'],
    'pending_earnings' => db_fetch("SELECT COALESCE(SUM(commission_amount), 0) as total FROM referrals WHERE agent_id = ? AND commission_paid = 0 AND status = 'active'", [$agent_id])['total'],
    'paid_earnings' => db_fetch("SELECT COALESCE(SUM(commission_amount), 0) as total FROM referrals WHERE agent_id = ? AND commission_paid = 1", [$agent_id])['paid']
];

// Get recent referrals
$recent_referrals = db_fetch_all(
    "SELECT r.*, u.full_name, u.email, s.amount as subscription_amount
     FROM referrals r
     JOIN users u ON r.student_id = u.user_id
     LEFT JOIN subscriptions s ON r.subscription_id = s.subscription_id
     WHERE r.agent_id = ?
     ORDER BY r.referral_date DESC
     LIMIT 10",
    [$agent_id]
);

// Commission rate (10%)
$commission_rate = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - <?php echo APP_NAME; ?></title>
    
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
        
        .agent-code-card {
            background: linear-gradient(135deg, var(--agent-color) 0%, #E65100 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(255, 111, 0, 0.3);
            margin-bottom: 30px;
        }
        
        .agent-code-display {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .agent-code-text {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 5px;
            margin: 0;
        }
        
        .copy-btn {
            background: white;
            color: var(--agent-color);
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 700;
            margin-top: 15px;
        }
        
        .copy-btn:hover {
            background: #f0f0f0;
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
            box-shadow: 0 5px 20px rgba(255, 111, 0, 0.2);
        }
        
        .stat-icon {
            font-size: 48px;
            color: var(--agent-color);
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
        
        .referrals-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .referral-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .referral-item:last-child {
            border-bottom: none;
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
            <h1 class="page-title">
                <i class="fas fa-user-tie"></i> Agent Dashboard
            </h1>

            <!-- Flash Messages -->
            <?php if (has_flash_message()): ?>
                <?php $flash = get_flash_message(); ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Agent Code Card -->
            <div class="agent-code-card">
                <div class="text-center">
                    <h3 style="margin-bottom: 10px;">
                        <i class="fas fa-id-badge"></i> Your Referral Code
                    </h3>
                    <p style="opacity: 0.9;">Share this code with students to earn commission!</p>
                </div>
                
                <div class="agent-code-display">
                    <p class="agent-code-text" id="agentCode"><?php echo htmlspecialchars($agent_code); ?></p>
                    <button class="copy-btn" onclick="copyAgentCode()">
                        <i class="fas fa-copy"></i> Copy Code
                    </button>
                </div>
                
                <div class="text-center">
                    <p style="margin: 0; opacity: 0.9;">
                        <i class="fas fa-gift"></i> Earn <strong><?php echo $commission_rate; ?>%</strong> commission on every student subscription!
                    </p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['total_referrals']); ?></div>
                            <div class="stat-label">Total Referrals</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['active_referrals']); ?></div>
                            <div class="stat-label">Active Referrals</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['pending_earnings']); ?> RWF</div>
                            <div class="stat-label">Pending Earnings</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['total_earnings']); ?> RWF</div>
                            <div class="stat-label">Total Earnings</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Referrals -->
            <div class="referrals-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 style="color: var(--agent-color); margin: 0;">
                        <i class="fas fa-history"></i> Recent Referrals
                    </h5>
                    <a href="referrals.php" class="btn btn-outline-primary btn-sm">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if (empty($recent_referrals)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No referrals yet. Start sharing your code to earn commissions!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_referrals as $referral): ?>
                        <div class="referral-item">
                            <div>
                                <strong><?php echo htmlspecialchars($referral['full_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($referral['email']); ?> • 
                                    <?php echo time_ago($referral['referral_date']); ?>
                                </small>
                            </div>
                            <div class="text-right">
                                <div>
                                    <?php if ($referral['status'] === 'pending'): ?>
                                        <span class="badge-status badge-pending">PENDING</span>
                                    <?php elseif ($referral['status'] === 'active'): ?>
                                        <span class="badge-status badge-active">ACTIVE</span>
                                    <?php elseif ($referral['commission_paid']): ?>
                                        <span class="badge-status badge-paid">PAID</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($referral['commission_amount'] > 0): ?>
                                    <small class="text-success">
                                        <strong>+<?php echo number_format($referral['commission_amount']); ?> RWF</strong>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyAgentCode() {
            const codeText = document.getElementById('agentCode').innerText;
            
            // Create temporary input
            const temp = document.createElement('input');
            temp.value = codeText;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            
            // Show feedback
            alert('Agent code copied: ' + codeText);
        }
    </script>
</body>
</html>