<?php
/**
 * Pricing & Payment Plans
 * 
 * Student and School subscription plans
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require student login
require_role('student');

$user = get_logged_in_user();
$user_id = get_user_id();
$lang = $user['language_preference'] ?? 'kinyarwanda';

// Get current subscription
$subscription = get_user_subscription($user_id);

// Define pricing plans
$student_plans = [
    'monthly' => [
        'name' => $lang === 'english' ? 'Monthly Plan' : 'Igishushanyo cy\'Ukwezi',
        'price' => 5000,
        'duration' => 30,
        'features' => [
            $lang === 'english' ? 'Access to all practice exams' : 'Kubona ibizamini byose byo kwiga',
            $lang === 'english' ? 'Unlimited exam attempts' : 'Ibigeragezo bitagira iherezo',
            $lang === 'english' ? 'Progress tracking' : 'Gukurikirana iterambere',
            $lang === 'english' ? 'Certificates on passing' : 'Impamyabushobozi iyo watsinze',
            $lang === 'english' ? '30-day access' : 'Kubona iminsi 30'
        ]
    ],
    'quarterly' => [
        'name' => $lang === 'english' ? '3-Month Plan' : 'Igishushanyo cy\'Amezi 3',
        'price' => 12000,
        'duration' => 90,
        'savings' => 3000,
        'features' => [
            $lang === 'english' ? 'All Monthly features' : 'Ibyose byo ku kwezi',
            $lang === 'english' ? 'Save 3,000 RWF' : 'Kizanira 3,000 RWF',
            $lang === 'english' ? '90-day access' : 'Kubona iminsi 90',
            $lang === 'english' ? 'Priority support' : 'Ubufasha bwihuse'
        ]
    ],
    'yearly' => [
        'name' => $lang === 'english' ? 'Yearly Plan' : 'Igishushanyo cy\'Umwaka',
        'price' => 40000,
        'duration' => 365,
        'savings' => 20000,
        'popular' => true,
        'features' => [
            $lang === 'english' ? 'All features included' : 'Ibyose byose',
            $lang === 'english' ? 'Save 20,000 RWF' : 'Kizanira 20,000 RWF',
            $lang === 'english' ? '1 year full access' : 'Umwaka wuzuye',
            $lang === 'english' ? 'Priority support' : 'Ubufasha bwihuse',
            $lang === 'english' ? 'Free certificate printing' : 'Gucapa impamyabushobozi ubuntu'
        ]
    ]
];

$school_plans = [
    'basic' => [
        'name' => $lang === 'english' ? 'Basic School Plan' : 'Igishushanyo cy\'Ishuri - Basic',
        'price' => 50000,
        'duration' => 30,
        'students' => 50,
        'features' => [
            $lang === 'english' ? 'Up to 50 student accounts' : 'Abanyeshuri kugeza 50',
            $lang === 'english' ? 'School dashboard' : 'Dashboard y\'ishuri',
            $lang === 'english' ? 'Progress reports' : 'Raporo z\'iterambere',
            $lang === 'english' ? 'Bulk student management' : 'Gucunga abanyeshuri benshi',
            $lang === 'english' ? '30-day access' : 'Iminsi 30'
        ]
    ],
    'standard' => [
        'name' => $lang === 'english' ? 'Standard School Plan' : 'Igishushanyo cy\'Ishuri - Standard',
        'price' => 120000,
        'duration' => 90,
        'students' => 150,
        'popular' => true,
        'features' => [
            $lang === 'english' ? 'Up to 150 student accounts' : 'Abanyeshuri kugeza 150',
            $lang === 'english' ? 'All Basic features' : 'Ibyose by\'ibanze',
            $lang === 'english' ? 'Advanced analytics' : 'Isesengura ryimbitse',
            $lang === 'english' ? 'Custom branding' : 'Ikirango cyihariye',
            $lang === 'english' ? '90-day access' : 'Iminsi 90'
        ]
    ],
    'premium' => [
        'name' => $lang === 'english' ? 'Premium School Plan' : 'Igishushanyo cy\'Ishuri - Premium',
        'price' => 400000,
        'duration' => 365,
        'students' => 'Unlimited',
        'features' => [
            $lang === 'english' ? 'Unlimited student accounts' : 'Abanyeshuri nta mipaka',
            $lang === 'english' ? 'All Standard features' : 'Ibyose bya Standard',
            $lang === 'english' ? 'Dedicated support' : 'Ubufasha bwihariye',
            $lang === 'english' ? 'API access' : 'API',
            $lang === 'english' ? '1 year access' : 'Umwaka wuzuye'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Pricing' : 'Ibiciro'; ?> - <?php echo APP_NAME; ?></title>
    
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
            padding: 40px 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .page-subtitle {
            font-size: 20px;
            color: #6c757d;
        }
        
        .section-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s;
            height: 100%;
            position: relative;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(44, 62, 150, 0.2);
        }
        
        .pricing-card.popular {
            border: 3px solid var(--primary-color);
        }
        
        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .plan-price {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 10px;
        }
        
        .plan-price small {
            font-size: 20px;
            color: #6c757d;
        }
        
        .plan-duration {
            text-align: center;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .savings-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        
        .plan-features li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .plan-features li:last-child {
            border-bottom: none;
        }
        
        .plan-features i {
            color: #28a745;
            margin-right: 10px;
        }
        
        .btn-subscribe {
            width: 100%;
            padding: 15px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 18px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-subscribe.primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-subscribe.primary:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-subscribe.secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-subscribe.secondary:hover {
            background: #5a6268;
            color: white;
        }
        
        .current-plan-badge {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .tab-content {
            margin-top: 40px;
        }
        
        .nav-tabs {
            border: none;
            justify-content: center;
            margin-bottom: 40px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 700;
            font-size: 20px;
            padding: 15px 40px;
            border-radius: 30px;
            margin: 0 10px;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </a>
                
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> <?php echo $lang === 'english' ? 'Dashboard' : 'Dashboard'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book"></i> <?php echo $lang === 'english' ? 'Courses' : 'Amasomo'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exams.php">
                            <i class="fas fa-clipboard-check"></i> <?php echo $lang === 'english' ? 'Exams' : 'Ibizamini'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pricing.php">
                            <i class="fas fa-tags"></i> <?php echo $lang === 'english' ? 'Pricing' : 'Ibiciro'; ?>
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo explode(' ', $user['full_name'])[0]; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user"></i> <?php echo $lang === 'english' ? 'Profile' : 'Umwirondoro'; ?>
                        </a>
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
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <?php echo $lang === 'english' ? 'Choose Your Plan' : 'Hitamo Igishushanyo'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $lang === 'english' 
                        ? 'Select the perfect plan for your learning needs' 
                        : 'Hitamo igishushanyo gikwiye kubyo ukeneye kwiga'; ?>
                </p>
            </div>

            <!-- Current Subscription -->
            <?php if ($subscription): ?>
                <div class="alert alert-success text-center">
                    <h5>
                        <i class="fas fa-check-circle"></i> 
                        <?php echo $lang === 'english' ? 'Current Plan:' : 'Igishushanyo Cy\'Ubu:'; ?> 
                        <strong><?php echo ucfirst($subscription['subscription_type']); ?></strong>
                    </h5>
                    <p>
                        <?php echo $lang === 'english' ? 'Valid until:' : 'Kirangira:'; ?> 
                        <?php echo format_date($subscription['end_date']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#student-plans">
                        <i class="fas fa-user-graduate"></i> 
                        <?php echo $lang === 'english' ? 'Student Plans' : 'Ibiciro by\'Abanyeshuri'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#school-plans">
                        <i class="fas fa-school"></i> 
                        <?php echo $lang === 'english' ? 'School Plans' : 'Ibiciro by\'Amashuri'; ?>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Student Plans -->
                <div id="student-plans" class="tab-pane fade show active">
                    <div class="row">
                        <?php foreach ($student_plans as $plan_key => $plan): ?>
                            <div class="col-md-4">
                                <div class="pricing-card <?php echo isset($plan['popular']) && $plan['popular'] ? 'popular' : ''; ?>">
                                    <?php if (isset($plan['popular']) && $plan['popular']): ?>
                                        <div class="popular-badge">
                                            <?php echo $lang === 'english' ? 'Most Popular' : 'Byinshi Byakunze'; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="plan-name"><?php echo $plan['name']; ?></div>
                                    
                                    <div class="plan-price">
                                        <?php echo number_format($plan['price']); ?> <small>RWF</small>
                                    </div>
                                    
                                    <div class="plan-duration">
                                        <?php echo $plan['duration']; ?> <?php echo $lang === 'english' ? 'days' : 'iminsi'; ?>
                                    </div>
                                    
                                    <?php if (isset($plan['savings'])): ?>
                                        <div class="text-center">
                                            <span class="savings-badge">
                                                <?php echo $lang === 'english' ? 'Save' : 'Kizanira'; ?> 
                                                <?php echo number_format($plan['savings']); ?> RWF
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <ul class="plan-features">
                                        <?php foreach ($plan['features'] as $feature): ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i> 
                                                <?php echo $feature; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <a href="payment.php?plan=student_<?php echo $plan_key; ?>" 
                                       class="btn btn-subscribe <?php echo isset($plan['popular']) && $plan['popular'] ? 'primary' : 'secondary'; ?>">
                                        <i class="fas fa-shopping-cart"></i> 
                                        <?php echo $lang === 'english' ? 'Subscribe Now' : 'Iyandikishe Nonaha'; ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- School Plans -->
                <div id="school-plans" class="tab-pane fade">
                    <div class="row">
                        <?php foreach ($school_plans as $plan_key => $plan): ?>
                            <div class="col-md-4">
                                <div class="pricing-card <?php echo isset($plan['popular']) && $plan['popular'] ? 'popular' : ''; ?>">
                                    <?php if (isset($plan['popular']) && $plan['popular']): ?>
                                        <div class="popular-badge">
                                            <?php echo $lang === 'english' ? 'Most Popular' : 'Byinshi Byakunze'; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="plan-name"><?php echo $plan['name']; ?></div>
                                    
                                    <div class="plan-price">
                                        <?php echo number_format($plan['price']); ?> <small>RWF</small>
                                    </div>
                                    
                                    <div class="plan-duration">
                                        <?php echo $plan['students']; ?> 
                                        <?php echo $lang === 'english' ? 'Students' : 'Abanyeshuri'; ?>
                                    </div>
                                    
                                    <ul class="plan-features">
                                        <?php foreach ($plan['features'] as $feature): ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i> 
                                                <?php echo $feature; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <a href="payment.php?plan=school_<?php echo $plan_key; ?>" 
                                       class="btn btn-subscribe <?php echo isset($plan['popular']) && $plan['popular'] ? 'primary' : 'secondary'; ?>">
                                        <i class="fas fa-shopping-cart"></i> 
                                        <?php echo $lang === 'english' ? 'Subscribe Now' : 'Iyandikishe Nonaha'; ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>