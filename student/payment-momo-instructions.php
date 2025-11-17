<?php
/**
 * Mobile Money Payment Instructions
 * 
 * Show MTN Mobile Money payment instructions
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
    <title><?php echo $lang === 'english' ? 'Payment Instructions' : 'Amabwiriza yo Kwishyura'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
            --mtn-yellow: #FFCB05;
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
        
        .mtn-header {
            background: var(--mtn-yellow);
            color: #000;
            padding: 30px;
            text-align: center;
        }
        
        .mtn-header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .instructions-body {
            padding: 40px;
        }
        
        .transaction-info {
            background: #f8f9fa;
            border-left: 5px solid var(--mtn-yellow);
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
        
        .steps-section {
            margin: 30px 0;
        }
        
        .step-item {
            background: #f8f9fa;
            border-left: 5px solid var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: start;
            gap: 15px;
        }
        
        .step-number {
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .step-content h5 {
            color: #333;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .step-content p {
            color: #6c757d;
            margin: 0;
        }
        
        .ussd-code {
            background: #000;
            color: #fff;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 3px;
            margin: 20px 0;
            font-family: monospace;
        }
        
        .alert-info {
            background: #e7f3ff;
            border-left: 5px solid #007bff;
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
    </style>
</head>
<body>
    <div class="instructions-container">
        <div class="instructions-card">
            <!-- MTN Header -->
            <div class="mtn-header">
                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                <h2>MTN Mobile Money</h2>
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
                            <?php echo $lang === 'english' ? 'Amount:' : 'Amafaranga:'; ?>
                        </span>
                        <span class="info-value" style="color: #28a745;">
                            <?php echo number_format($payment['amount']); ?> RWF
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">
                            <?php echo $lang === 'english' ? 'Your Phone:' : 'Telefone Yawe:'; ?>
                        </span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['phone_number']); ?></span>
                    </div>
                </div>

                <!-- USSD Code -->
                <div class="text-center">
                    <h5 style="color: var(--primary-color);">
                        <?php echo $lang === 'english' ? 'Dial this code on your phone:' : 'Kanda kode iyi kuri telefone yawe:'; ?>
                    </h5>
                    <div class="ussd-code">*182*7*1#</div>
                </div>

                <!-- Payment Steps -->
                <div class="steps-section">
                    <h5 style="color: var(--primary-color); margin-bottom: 20px;">
                        <i class="fas fa-list-ol"></i> 
                        <?php echo $lang === 'english' ? 'Step-by-Step Instructions' : 'Amabwiriza Akurikizwa'; ?>
                    </h5>

                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Dial USSD Code' : 'Kanda Kode'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Dial *182*7*1# on your MTN Mobile Money registered phone' 
                                    : 'Kanda *182*7*1# kuri telefone yawe yanditswe kuri MTN Mobile Money'; ?>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Select Pay for Services' : 'Hitamo Kwishyura Serivisi'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Select option for "Pay for Services" from the menu' 
                                    : 'Hitamo "Kwishyura Serivisi" muri menu'; ?>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Enter Our Till Number' : 'Andika Nimero Yacu'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Enter our business number: ' 
                                    : 'Andika nimero y\'ubucuruzi: '; ?>
                                <strong>123456</strong>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Enter Amount' : 'Andika Amafaranga'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Enter the exact amount: ' 
                                    : 'Andika amafaranga nk\'uko ari: '; ?>
                                <strong><?php echo number_format($payment['amount']); ?> RWF</strong>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Enter Reference' : 'Andika Referance'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Enter your transaction ID as reference: ' 
                                    : 'Andika nimero y\'ubwishyu nka reference: '; ?>
                                <strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">6</div>
                        <div class="step-content">
                            <h5><?php echo $lang === 'english' ? 'Enter PIN & Confirm' : 'Andika PIN Wemeze'; ?></h5>
                            <p>
                                <?php echo $lang === 'english' 
                                    ? 'Enter your MTN Mobile Money PIN and confirm the payment' 
                                    : 'Andika PIN yawe ya MTN Mobile Money wemeze kwishyura'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Important Note -->
                <div class="alert alert-info">
                    <h6 style="font-weight: 700;">
                        <i class="fas fa-info-circle"></i> 
                        <?php echo $lang === 'english' ? 'Important:' : 'Ibyitonderwa:'; ?>
                    </h6>
                    <ul class="mb-0">
                        <li>
                            <?php echo $lang === 'english' 
                                ? 'Your subscription will be activated within 5 minutes after successful payment' 
                                : 'Konti yawe izaba yakoze mu minota 5 nyuma y\'ubwishyu butsinze'; ?>
                        </li>
                        <li>
                            <?php echo $lang === 'english' 
                                ? 'You will receive an email confirmation once payment is verified' 
                                : 'Uzakira email y\'kwemeza iyo ubwishyu bwemejwe'; ?>
                        </li>
                        <li>
                            <?php echo $lang === 'english' 
                                ? 'Keep your transaction ID for reference' 
                                : 'Bika nimero y\'ubwishyu kugira ngo uyikoreshe'; ?>
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
                        <strong><?php echo SITE_PHONE; ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>