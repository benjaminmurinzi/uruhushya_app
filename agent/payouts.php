<?php
/**
 * Agent Payouts Page
 * 
 * Request and view payout history
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require agent login
require_role('agent');

$agent = get_logged_in_user();
$agent_id = get_user_id();

// Get available balance (unpaid commissions)
$available_balance = db_fetch(
    "SELECT COALESCE(SUM(commission_amount), 0) as balance 
     FROM referrals 
     WHERE agent_id = ? AND commission_paid = 0 AND status = 'active'",
    [$agent_id]
)['balance'];

// Minimum payout amount
$min_payout = 10000; // 10,000 RWF

$error = '';
$success = '';

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $payment_method = clean_input($_POST['payment_method'] ?? '');
    $phone_number = clean_input($_POST['phone_number'] ?? '');
    $bank_name = clean_input($_POST['bank_name'] ?? '');
    $bank_account = clean_input($_POST['bank_account'] ?? '');
    
    // Validation
    if ($amount <= 0) {
        $error = 'Invalid amount';
    } elseif ($amount > $available_balance) {
        $error = 'Insufficient balance';
    } elseif ($amount < $min_payout) {
        $error = "Minimum payout amount is " . number_format($min_payout) . " RWF";
    } elseif (empty($payment_method)) {
        $error = 'Please select payment method';
    } elseif ($payment_method === 'momo' && !is_valid_phone($phone_number)) {
        $error = 'Invalid phone number for Mobile Money';
    } elseif ($payment_method === 'bank_transfer' && (empty($bank_name) || empty($bank_account))) {
        $error = 'Bank details required for bank transfer';
    } else {
        try {
            // Create payout request
            $insert_payout = "INSERT INTO payout_requests 
                             (agent_id, amount, payment_method, phone_number, bank_account, bank_name, status, request_date, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
            
            db_query($insert_payout, [
                $agent_id,
                $amount,
                $payment_method,
                $payment_method === 'momo' ? $phone_number : null,
                $payment_method === 'bank_transfer' ? $bank_account : null,
                $payment_method === 'bank_transfer' ? $bank_name : null
            ]);
            
            // Log activity
            log_activity($agent_id, 'payout_requested', "Requested payout of {$amount} RWF");
            
            $success = 'Payout request submitted successfully! We will process it within 3-5 business days.';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $error = 'Failed to submit payout request. Please try again.';
            error_log("Payout request error: " . $e->getMessage());
        }
    }
}

// Get payout history
$payout_history = db_fetch_all(
    "SELECT * FROM payout_requests 
     WHERE agent_id = ? 
     ORDER BY request_date DESC",
    [$agent_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payouts - <?php echo APP_NAME; ?></title>
    
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
        
        .balance-card {
            background: linear-gradient(135deg, var(--agent-color) 0%, #E65100 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .balance-amount {
            font-size: 48px;
            font-weight: 800;
            margin: 20px 0;
        }
        
        .request-form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .payment-method-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method-option:hover {
            border-color: var(--agent-color);
            background: rgba(255, 111, 0, 0.05);
        }
        
        .payment-method-option.selected {
            border-color: var(--agent-color);
            background: rgba(255, 111, 0, 0.1);
        }
        
        .payment-method-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .payment-details {
            display: none;
            margin-top: 20px;
        }
        
        .payment-details.show {
            display: block;
        }
        
        .btn-request-payout {
            background: var(--agent-color);
            color: white;
            padding: 15px 40px;
            border-radius: 25px;
            font-weight: 700;
            border: none;
        }
        
        .btn-request-payout:hover {
            background: #E65100;
            color: white;
        }
        
        .history-card {
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
        <div class="container">
            <!-- Page Header -->
            <h1 class="page-title">
                <i class="fas fa-wallet"></i> Request Payout
            </h1>

            <!-- Available Balance -->
            <div class="balance-card">
                <h4>Available Balance</h4>
                <div class="balance-amount">
                    <?php echo number_format($available_balance); ?> RWF
                </div>
                <p style="opacity: 0.9; margin: 0;">
                    <i class="fas fa-info-circle"></i> 
                    Minimum payout: <?php echo number_format($min_payout); ?> RWF
                </p>
            </div>

            <!-- Request Form -->
            <div class="request-form-card">
                <h5 style="color: var(--agent-color); margin-bottom: 20px;">
                    <i class="fas fa-hand-holding-usd"></i> Request Withdrawal
                </h5>

                <!-- Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if ($available_balance < $min_payout): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Insufficient Balance:</strong> You need at least <?php echo number_format($min_payout); ?> RWF to request a payout.
                        Keep referring students to earn more commissions!
                    </div>
                <?php endif; ?>

                <!-- Payout Form -->
                <form method="POST" id="payoutForm">
                    <div class="form-group">
                        <label><strong>Amount (RWF) *</strong></label>
                        <input 
                            type="number" 
                            class="form-control form-control-lg" 
                            name="amount" 
                            placeholder="Enter amount"
                            min="<?php echo $min_payout; ?>"
                            max="<?php echo $available_balance; ?>"
                            step="1000"
                            required
                            <?php echo $available_balance < $min_payout ? 'disabled' : ''; ?>
                        >
                        <small class="form-text text-muted">
                            Available: <?php echo number_format($available_balance); ?> RWF
                        </small>
                    </div>

                    <div class="form-group">
                        <label><strong>Payment Method *</strong></label>
                        
                        <label class="payment-method-option" id="momo-option">
                            <input type="radio" name="payment_method" value="momo" id="momo-radio" required>
                            <strong>MTN Mobile Money</strong>
                        </label>

                        <label class="payment-method-option" id="bank-option">
                            <input type="radio" name="payment_method" value="bank_transfer" id="bank-radio" required>
                            <strong>Bank Transfer</strong>
                        </label>
                    </div>

                    <!-- Mobile Money Details -->
                    <div class="payment-details" id="momo-details">
                        <div class="form-group">
                            <label><strong>MTN Mobile Money Number *</strong></label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                name="phone_number" 
                                placeholder="078XXXXXXX"
                                pattern="[0-9]{10}"
                                value="<?php echo htmlspecialchars($agent['phone'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <!-- Bank Transfer Details -->
                    <div class="payment-details" id="bank-details">
                        <div class="form-group">
                            <label><strong>Bank Name *</strong></label>
                            <select class="form-control" name="bank_name">
                                <option value="">Select Bank</option>
                                <option value="Bank of Kigali">Bank of Kigali</option>
                                <option value="Equity Bank">Equity Bank</option>
                                <option value="I&M Bank">I&M Bank</option>
                                <option value="KCB Bank">KCB Bank</option>
                                <option value="GT Bank">GT Bank</option>
                                <option value="Cogebanque">Cogebanque</option>
                                <option value="Unguka Bank">Unguka Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Account Number *</strong></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="bank_account" 
                                placeholder="Enter account number"
                            >
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Processing Time:</strong> Payouts are processed within 3-5 business days.
                    </div>

                    <div class="text-center">
                        <button 
                            type="submit" 
                            class="btn btn-request-payout"
                            <?php echo $available_balance < $min_payout ? 'disabled' : ''; ?>
                        >
                            <i class="fas fa-paper-plane"></i> Request Payout
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payout History -->
            <div class="history-card">
                <h5 style="color: var(--agent-color); margin-bottom: 20px;">
                    <i class="fas fa-history"></i> Payout History
                </h5>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Processed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payout_history)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No payout history yet</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payout_history as $payout): ?>
                                    <tr>
                                        <td><?php echo format_date($payout['request_date']); ?></td>
                                        <td><strong><?php echo number_format($payout['amount']); ?> RWF</strong></td>
                                        <td>
                                            <?php if ($payout['payment_method'] === 'momo'): ?>
                                                <i class="fas fa-mobile-alt"></i> Mobile Money
                                            <?php else: ?>
                                                <i class="fas fa-university"></i> Bank Transfer
                                            <?php endif; ?>
                                        </td>
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
                                        <td>
                                            <?php if ($payout['processed_date']): ?>
                                                <?php echo format_date($payout['processed_date']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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
        $(document).ready(function() {
            // Handle payment method selection
            $('.payment-method-option').on('click', function() {
                $('.payment-method-option').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
                
                // Show/hide payment details
                $('.payment-details').removeClass('show');
                
                if ($('#momo-radio').is(':checked')) {
                    $('#momo-details').addClass('show');
                    $('#momo-details input').prop('required', true);
                    $('#bank-details input, #bank-details select').prop('required', false);
                } else if ($('#bank-radio').is(':checked')) {
                    $('#bank-details').addClass('show');
                    $('#bank-details input, #bank-details select').prop('required', true);
                    $('#momo-details input').prop('required', false);
                }
            });
        });
    </script>
</body>
</html>