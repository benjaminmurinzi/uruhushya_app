<?php
/**
 * Payment Processing Page - API READY
 * 
 * Handles payments through configured gateway (Demo/Flutterwave/MTN/Paypack)
 * Switch payment mode in config/payment-config.php
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Load payment system
require_once '../config/payment-config.php';
require_once '../includes/payment-gateway.php';
require_once '../includes/email.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$user_id = get_user_id();
$lang = $user['language_preference'] ?? 'kinyarwanda';

// Get selected plan
$plan_param = isset($_GET['plan']) ? clean_input($_GET['plan']) : '';

if (empty($plan_param)) {
    set_flash_message('error', 'Invalid plan selected');
    redirect('pricing.php');
}

// Parse plan (e.g., "student_monthly" or "school_basic")
$plan_parts = explode('_', $plan_param);
$plan_type = $plan_parts[0]; // student or school
$plan_key = $plan_parts[1]; // monthly, quarterly, yearly, basic, standard, premium

// Define all plans with pricing
$all_plans = [
    'student' => [
        'monthly' => [
            'name' => 'Monthly Plan', 
            'name_rw' => 'Igishushanyo cy\'Ukwezi',
            'price' => 5000, 
            'duration' => 30
        ],
        'quarterly' => [
            'name' => '3-Month Plan', 
            'name_rw' => 'Igishushanyo cy\'Amezi 3',
            'price' => 12000, 
            'duration' => 90
        ],
        'yearly' => [
            'name' => 'Yearly Plan', 
            'name_rw' => 'Igishushanyo cy\'Umwaka',
            'price' => 40000, 
            'duration' => 365
        ]
    ],
    'school' => [
        'basic' => [
            'name' => 'Basic School Plan', 
            'name_rw' => 'Igishushanyo cy\'Ishuri - Basic',
            'price' => 50000, 
            'duration' => 30, 
            'students' => 50
        ],
        'standard' => [
            'name' => 'Standard School Plan', 
            'name_rw' => 'Igishushanyo cy\'Ishuri - Standard',
            'price' => 120000, 
            'duration' => 90, 
            'students' => 150
        ],
        'premium' => [
            'name' => 'Premium School Plan', 
            'name_rw' => 'Igishushanyo cy\'Ishuri - Premium',
            'price' => 400000, 
            'duration' => 365, 
            'students' => 'Unlimited'
        ]
    ]
];

// Validate plan
if (!isset($all_plans[$plan_type][$plan_key])) {
    set_flash_message('error', 'Invalid plan selected');
    redirect('pricing.php');
}

$selected_plan = $all_plans[$plan_type][$plan_key];

// Initialize payment gateway
$gateway = new PaymentGateway();
$provider_name = $gateway->getProviderName();
$is_demo = is_demo_mode();

$error = '';
$processing = false;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$processing) {
    $phone_number = clean_input($_POST['phone_number'] ?? '');
    
    // Validate phone number
    if (!is_valid_phone($phone_number)) {
        $error = $lang === 'english' 
            ? 'Please enter a valid phone number (e.g., 0781234567)' 
            : 'Andika numero y\'itelefoni ifite uburyo (urugero: 0781234567)';
    } else {
        // Check if gateway is configured
        if (!$gateway->isConfigured()) {
            $error = $lang === 'english'
                ? 'Payment system is not configured. Please contact support.'
                : 'Sisitemu y\'ubwishyu ntiyashyizweho. Nyamuneka tuvugishe.';
        } else {
            $processing = true;
            
            // Simulate delay only for demo mode
            if ($is_demo) {
                simulate_payment_delay();
            }
            
            // Process payment through configured gateway
            $result = $gateway->processPayment(
                $user,
                $selected_plan['price'],
                $phone_number,
                $plan_key,
                $selected_plan
            );
            
            if ($result['success']) {
                // Check if we need to redirect (for Flutterwave/external gateways)
                if (isset($result['redirect_url'])) {
                    // Store transaction details in session for callback
                    $_SESSION['pending_payment'] = [
                        'transaction_id' => $result['transaction_id'],
                        'amount' => $selected_plan['price'],
                        'plan_key' => $plan_key,
                        'plan_details' => $selected_plan,
                        'user_id' => $user_id
                    ];
                    
                    // Redirect to external payment provider
                    redirect($result['redirect_url']);
                }
                
                // For demo/instant payments - send confirmation email
                $plan_name = $lang === 'english' ? $selected_plan['name'] : $selected_plan['name_rw'];
                
                $email_data = [
                    'full_name' => $user['full_name'],
                    'transaction_id' => $result['transaction_id'],
                    'amount' => number_format($selected_plan['price']),
                    'payment_method' => $provider_name,
                    'subscription_type' => ucfirst($plan_key),
                    'start_date' => format_date($result['start_date']),
                    'end_date' => format_date($result['end_date']),
                    'login_url' => APP_URL . '/public/login.php'
                ];
                
                send_template_email(
                    'payment_confirmed',
                    $user['email'],
                    $lang,
                    $email_data,
                    $user_id
                );
                
                // Set success message and redirect to dashboard
                set_flash_message('success', $lang === 'english' 
                    ? 'Payment successful! Your subscription is now active.' 
                    : 'Ubwishyu bwakunze! Inyandiko yawe irakora.');
                
                redirect('dashboard.php');
                
            } else {
                $error = $result['message'] ?? ($lang === 'english' 
                    ? 'Payment processing failed. Please try again.' 
                    : 'Ubwishyu bwanze. Ongera ugerageze.');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Payment' : 'Kwishyura'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .payment-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .payment-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .provider-badge {
            background: <?php echo $is_demo ? '#ffc107' : '#28a745'; ?>;
            color: <?php echo $is_demo ? '#000' : '#fff'; ?>;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .payment-body {
            padding: 40px;
        }
        
        .plan-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid #dee2e6;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 150, 0.25);
        }
        
        .btn-pay {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            border: none;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-pay:hover:not(:disabled) {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.02);
        }
        
        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-back {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .info-box i {
            color: #007bff;
            margin-right: 10px;
        }
        
        .processing-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .processing-overlay.show {
            display: flex;
        }
        
        .processing-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
        }
        
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Processing Overlay -->
    <div class="processing-overlay" id="processingOverlay">
        <div class="processing-content">
            <div class="spinner"></div>
            <h4><?php echo $lang === 'english' ? 'Processing Payment...' : 'Tegereza...'; ?></h4>
            <p class="text-muted">
                <?php echo $lang === 'english' 
                    ? 'Please wait while we process your payment' 
                    : 'Tegereza turangiza ubwishyu'; ?>
            </p>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-card">
            <!-- Payment Header -->
            <div class="payment-header">
                <span class="provider-badge">
                    <i class="fas <?php echo $is_demo ? 'fa-flask' : 'fa-shield-alt'; ?>"></i> 
                    <?php echo $provider_name; ?>
                </span>
                <h2>
                    <i class="fas fa-mobile-alt"></i> 
                    <?php echo $lang === 'english' ? 'Mobile Money Payment' : 'Kwishyura na Mobile Money'; ?>
                </h2>
                <p><?php echo $lang === 'english' ? $selected_plan['name'] : $selected_plan['name_rw']; ?></p>
            </div>

            <!-- Payment Body -->
            <div class="payment-body">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Demo Mode Info -->
                <?php if ($is_demo): ?>
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong><?php echo $lang === 'english' ? 'Demo Mode:' : 'Demo Mode:'; ?></strong>
                        <?php echo $lang === 'english' 
                            ? 'This is a demo payment. Your subscription will be activated automatically without real money transfer.' 
                            : 'Iyi ni demo. Inyandiko yawe izakora ako kanya nta mafaranga ukwiriye kwishyura.'; ?>
                    </div>
                <?php endif; ?>

                <!-- Plan Summary -->
                <div class="plan-summary">
                    <h5 style="color: var(--primary-color); margin-bottom: 20px;">
                        <i class="fas fa-shopping-cart"></i> 
                        <?php echo $lang === 'english' ? 'Order Summary' : 'Incamake y\'Itegeko'; ?>
                    </h5>
                    
                    <div class="summary-row">
                        <span><?php echo $lang === 'english' ? 'Plan:' : 'Igishushanyo:'; ?></span>
                        <strong><?php echo $lang === 'english' ? $selected_plan['name'] : $selected_plan['name_rw']; ?></strong>
                    </div>
                    
                    <div class="summary-row">
                        <span><?php echo $lang === 'english' ? 'Duration:' : 'Igihe:'; ?></span>
                        <strong>
                            <?php echo $selected_plan['duration']; ?> 
                            <?php echo $lang === 'english' ? 'days' : 'iminsi'; ?>
                        </strong>
                    </div>
                    
                    <?php if (isset($selected_plan['students'])): ?>
                        <div class="summary-row">
                            <span><?php echo $lang === 'english' ? 'Students:' : 'Abanyeshuri:'; ?></span>
                            <strong><?php echo $selected_plan['students']; ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span><?php echo $lang === 'english' ? 'Total Amount:' : 'Amafaranga Yose:'; ?></span>
                        <strong><?php echo number_format($selected_plan['price']); ?> RWF</strong>
                    </div>
                </div>

                <!-- Payment Form -->
                <form method="POST" id="paymentForm">
                    <div class="form-group">
                        <label for="phone_number">
                            <i class="fas fa-mobile-alt"></i>
                            <?php echo $lang === 'english' ? 'MTN Mobile Money Number:' : 'Numero ya MTN Mobile Money:'; ?>
                        </label>
                        <input 
                            type="tel" 
                            class="form-control form-control-lg" 
                            id="phone_number" 
                            name="phone_number" 
                            placeholder="078XXXXXXX"
                            pattern="[0-9]{10}"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            required
                        >
                        <small class="form-text text-muted">
                            <?php echo $lang === 'english' 
                                ? 'Enter your 10-digit phone number' 
                                : 'Andika numero y\'itelefoni ifite imibare 10'; ?>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-pay" id="payBtn">
                        <i class="fas fa-check-circle"></i> 
                        <?php echo $lang === 'english' ? 'Complete Payment' : 'Soza Kwishyura'; ?>
                    </button>

                    <a href="pricing.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> 
                        <?php echo $lang === 'english' ? 'Back to Pricing' : 'Subira ku Biciro'; ?>
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#paymentForm').on('submit', function(e) {
                const phone = $('#phone_number').val();
                
                // Validate phone number
                if (!phone || phone.length !== 10 || !phone.startsWith('07')) {
                    e.preventDefault();
                    alert('<?php echo $lang === "english" ? "Please enter a valid 10-digit phone number starting with 07" : "Andika numero y\'itelefoni ifite imibare 10 itangira kuri 07"; ?>');
                    return false;
                }
                
                // Show processing overlay
                $('#processingOverlay').addClass('show');
                $('#payBtn').prop('disabled', true);
            });
        });
    </script>
</body>
</html>