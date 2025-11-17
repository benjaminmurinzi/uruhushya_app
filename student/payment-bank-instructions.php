<?php
/**
 * Bank Transfer Payment Instructions
 * 
 * Show bank account details for payment
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$lang = $user['language_preference'] ?? 'kinyarwanda';

// Get payment details from session
if (!isset($_SESSION['payment_details'])) {
    set_flash_message('error', 'Invalid payment session');
    redirect('pricing.php');
}

$payment = $_SESSION['payment_details'];

// Clear session after getting data
unset($_SESSION['payment_details']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Bank Transfer Instructions' : 'Amabwiriza yo Kohereza kuri Banki'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1E2A5E 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .instructions-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .instructions-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .bank-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .bank-header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .instructions-body {
            padding: 40px;
        }
        
        .transaction-info {
            background: #f8f9fa;
            border-left: 5px solid var(--primary-color);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-weight: 600;
        }
        
        .info-value {
            color: #333;
            font-weight: 700;
            font-size: 18px;
        }
        
        .bank-details {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .bank-detail-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .bank-detail-item h6 {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .bank-detail-item .value {
            color: #333;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .copy-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .copy-btn:hover {
            background: #1E2A5E;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 10px;
        }
        
        .btn-dashboard {
            background: var(--primary-color);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: 700;
            border: none;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-dashboard:hover {
            background: #1E2A5E;
            color: white;
        }
        
        .steps-list {
            list-style: none;
            padding: 0;
        }
        
        .steps-list li {
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .steps-list li:last-child {
            border-bottom: none;
        }
        
        .steps-list i {
            color: #28a745;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="instructions-container">
        <div class="instructions-card">
            <!-- Bank Header -->
            <div class="bank-header">
                <i class="fas fa-university fa-3x mb-3"></i>
                <h2><?php echo $lang === 'english' ? 'Bank Transfer' : 'Kohereza kuri Banki'; ?></h2>
                <p><?php echo $lang === 'english' ? 'Payment Instructions' : 'Amabwiriza yo Kwishyura'; ?></p>
            </div>

            <!-- Instructions Body -->
            <div class="instructions-body">
                <!-- Transaction Info -->
                <div class="transaction-info">
                    <h5 style="color: var(--primary-color); margin-bottom: 20px;">
                        <i class="fas fa-receipt"></i> 
                        <?php echo $lang === 'english' ? 'Transaction Details' : 'Amakuru y\'Ubwishyu'; ?>
                    </h5>
                    
                    <div class="info-row">
                        <span class="info-label">
                            <?php echo $lang === 'english' ? 'Transaction ID:' : 'Nimero y\'Ubwishyu:'; ?>
                        </span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">
                            <?php echo $lang === 'english' ? 'Plan:' : 'Igishushanyo:'; ?>
                        </span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['plan_name']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">
                            <?php echo $lang === 'english' ? 'Amount to Pay:' : 'Amafaranga yo Kwishyura:'; ?>
                        </span>
                        <span class="info-value" style="color: #28a745;">
                            <?php echo number_format($payment['amount']); ?> RWF
                        </span>
                    </div>
                </div>

                <!-- Bank Account Details -->
                <div class="bank-details">
                    <h5 style="color: var(--primary-color); margin-bottom: 25px; text-align: center;">
                        <i class="fas fa-building"></i> 
                        <?php echo $lang === 'english' ? 'Our Bank Account Details' : 'Amakuru y\'Akaonti Kacu'; ?>
                    </h5>

                    <div class="bank-detail-item">
                        <h6><?php echo $lang === 'english' ? 'Bank Name' : 'Izina rya Banki'; ?></h6>
                        <div class="value">
                            <span>Bank of Kigali (BK)</span>
                            <button class="copy-btn" onclick="copyToClipboard('Bank of Kigali')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="bank-detail-item">
                        <h6><?php echo $lang === 'english' ? 'Account Name' : 'Izina kuri Akaonti'; ?></h6>
                        <div class="value">
                            <span><?php echo APP_NAME; ?> Ltd</span>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo APP_NAME; ?> Ltd')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="bank-detail-item">
                        <h6><?php echo $lang === 'english' ? 'Account Number' : 'Nimero y\'Akaonti'; ?></h6>
                        <div class="value">
                            <span>000123456789</span>
                            <button class="copy-btn" onclick="copyToClipboard('000123456789')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="bank-detail-item">
                        <h6><?php echo $lang === 'english' ? 'Reference (Important!)' : 'Reference (Ngombwa!)'; ?></h6>
                        <div class="value">
                            <span><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($payment['transaction_id']); ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="alert alert-warning">
                    <h6 style="font-weight: 700;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo $lang === 'english' ? 'Important Instructions:' : 'Amabwiriza y\'Ingenzi:'; ?>
                    </h6>
                    <ul class="steps-list">
                        <li>
                            <i class="fas fa-check"></i>
                            <?php echo $lang === 'english' 
                                ? 'Transfer the EXACT amount shown above to our bank account' 
                                : 'Ohereza AMAFARANGA YOSE agaragara hejuru kuri akaonti kacu'; ?>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <?php echo $lang === 'english' 
                                ? 'MUST include your Transaction ID as the reference/description' 
                                : 'UGOMBA gushyiramo Nimero y\'Ubwishyu nka reference'; ?>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <?php echo $lang === 'english' 
                                ? 'Keep your bank transfer receipt/proof for verification' 
                                : 'Bika icyemezo cy\'ubwishyu kugira ngo cyemezwe'; ?>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <?php echo $lang === 'english' 
                                ? 'Your subscription will be activated within 24 hours after verification' 
                                : 'Konti yawe izakora mu masaha 24 nyuma yo kwemezwa'; ?>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <?php echo $lang === 'english' 
                                ? 'You will receive an email confirmation once verified' 
                                : 'Uzakira email y\'kwemeza iyo byemejwe'; ?>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <button onclick="window.location.href='dashboard.php'" class="btn btn-dashboard">
                    <i class="fas fa-home"></i> 
                    <?php echo $lang === 'english' ? 'Return to Dashboard' : 'Subira kuri Dashboard'; ?>
                </button>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <?php echo $lang === 'english' 
                            ? 'Need help? Contact us at ' 
                            : 'Ukeneye ubufasha? Tuvugishe kuri '; ?>
                        <strong><?php echo SITE_EMAIL; ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToClipboard(text) {
            // Create temporary input
            const temp = document.createElement('input');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            
            // Show feedback
            alert('<?php echo $lang === "english" ? "Copied to clipboard!" : "Byakopewe!"; ?>');
        }
    </script>
</body>
</html>