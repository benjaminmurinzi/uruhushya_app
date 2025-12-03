<?php
/**
 * Admin Header Include
 * 
 * Common header for all admin pages
 * Includes navigation, language handling, and user info
 * 
 * Developer: Benjamin NIYOMURINZI
 */

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Admin Dashboard');
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin user
$admin = get_logged_in_user();
$lang = $admin['language_preference'] ?? 'english';

// Count pending applications for badge
$pending_applications = db_fetch(
    "SELECT 
        (SELECT COUNT(*) FROM school_applications WHERE status = 'pending') +
        (SELECT COUNT(*) FROM agent_applications WHERE status = 'pending') as total"
)['total'] ?? 0;

// Count pending payouts for badge
$pending_payouts = db_fetch(
    "SELECT COUNT(*) as count FROM payout_requests WHERE status = 'pending'"
)['count'] ?? 0;

// Translations
$translations = [
    'english' => [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'questions' => 'Questions',
        'applications' => 'Applications',
        'payments' => 'Payments',
        'payouts' => 'Agent Payouts',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'admin_panel' => 'Admin Panel'
    ],
    'kinyarwanda' => [
        'dashboard' => 'Dashboard',
        'users' => 'Abakoresha',
        'questions' => 'Ibibazo',
        'applications' => 'Amasaba',
        'payments' => 'Kwishyura',
        'payouts' => 'Kwishyuza Abahagarariye',
        'settings' => 'Igenamiterere',
        'logout' => 'Sohoka',
        'admin_panel' => 'Igenzura rya Admin'
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --admin-color: #dc3545;
            --admin-dark: #c82333;
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
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .top-nav .navbar-brand {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }
        
        .top-nav .navbar-brand:hover {
            color: white;
        }
        
        .top-nav .nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .top-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 15px;
        }
        
        .top-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .top-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }
        
        .top-nav .badge {
            font-size: 10px;
            padding: 3px 6px;
            margin-left: 5px;
        }
        
        .user-menu .dropdown-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
        }
        
        .user-menu .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
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
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-shield-alt"></i> <?php echo APP_NAME; ?> - Admin
                </a>
                
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home"></i> <?php echo $t['dashboard']; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                            <i class="fas fa-users"></i> <?php echo $t['users']; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'questions.php' ? 'active' : ''; ?>" href="questions.php">
                            <i class="fas fa-question-circle"></i> <?php echo $t['questions']; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'applications.php' ? 'active' : ''; ?>" href="applications.php">
                            <i class="fas fa-inbox"></i> <?php echo $t['applications']; ?>
                            <?php if ($pending_applications > 0): ?>
                                <span class="badge badge-warning"><?php echo $pending_applications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                            <i class="fas fa-credit-card"></i> <?php echo $t['payments']; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'agent-payouts.php' ? 'active' : ''; ?>" href="agent-payouts.php">
                            <i class="fas fa-wallet"></i> <?php echo $t['payouts']; ?>
                            <?php if ($pending_payouts > 0): ?>
                                <span class="badge badge-warning"><?php echo $pending_payouts; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> 
                        <?php echo explode(' ', $admin['full_name'])[0]; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog"></i> <?php echo $t['settings']; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="../public/logout.php">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $t['logout']; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">