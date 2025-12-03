<?php
/**
 * Admin Dashboard
 * 
 * Main dashboard for administrators to manage the platform
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

// Define page title for header include
define('PAGE_TITLE', 'Admin Dashboard');

$user = get_logged_in_user();

// Get platform statistics
$stats = [];

// Total users by role
$stats['total_students'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
$stats['total_schools'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'school'")['count'];
$stats['total_agents'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'")['count'];
$stats['total_admins'] = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];

// Active subscriptions
$stats['active_subscriptions'] = db_fetch("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active' AND end_date >= CURDATE()")['count'];

// Total revenue
$stats['total_revenue'] = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'")['total'];

// Revenue this month
$stats['revenue_this_month'] = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")['total'];

// Total exams taken
$stats['total_exams_taken'] = db_fetch("SELECT COUNT(*) as count FROM exam_attempts WHERE status = 'completed'")['count'];

// Exams taken today
$stats['exams_today'] = db_fetch("SELECT COUNT(*) as count FROM exam_attempts WHERE DATE(start_time) = CURDATE()")['count'];

// Pending applications
$stats['pending_school_apps'] = db_fetch("SELECT COUNT(*) as count FROM school_applications WHERE status = 'pending'")['count'];
$stats['pending_agent_apps'] = db_fetch("SELECT COUNT(*) as count FROM agent_applications WHERE status = 'pending'")['count'];

// Recent users
$recent_users = db_fetch_all("SELECT user_id, email, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");

// Recent payments
$recent_payments = db_fetch_all("SELECT p.*, u.full_name, u.email FROM payments p JOIN users u ON p.user_id = u.user_id ORDER BY p.payment_date DESC LIMIT 10");

// Recent exam attempts
$recent_exams = db_fetch_all("SELECT ea.*, u.full_name, e.exam_name_en, e.exam_code FROM exam_attempts ea JOIN users u ON ea.user_id = u.user_id JOIN exams e ON ea.exam_id = e.exam_id WHERE ea.status = 'completed' ORDER BY ea.start_time DESC LIMIT 10");

$lang = $user['language_preference'] ?? 'english';

// Helper functions if not exists
if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return number_format($amount, 0) . ' RWF';
    }
}

if (!function_exists('format_date')) {
    function format_date($date) {
        return date('M d, Y', strtotime($date));
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($datetime) {
        return date('M d, Y H:i', strtotime($datetime));
    }
}

if (!function_exists('display_flash_message')) {
    function display_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            
            return '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show">
                ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
        }
        return '';
    }
}

// Include header
include 'includes/admin-header.php';
?>

<!-- Additional Styles for Dashboard -->
<style>
    .welcome-section {
        background: linear-gradient(135deg, var(--admin-color) 0%, #c82333 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .welcome-section h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
        height: 100%;
        text-align: center;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(220, 53, 69, 0.15);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 15px;
        color: white;
    }
    
    .stat-icon.red { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); }
    .stat-icon.purple { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); }
    .stat-icon.teal { background: linear-gradient(135deg, #20c997 0%, #1aa179 100%); }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--admin-color);
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .section-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--admin-color);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .table {
        font-size: 14px;
    }
    
    .badge-role {
        padding: 5px 10px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 11px;
    }
    
    .badge-admin { background: #dc3545; color: white; }
    .badge-school { background: #007bff; color: white; }
    .badge-agent { background: #6f42c1; color: white; }
    .badge-student { background: #28a745; color: white; }
</style>

<!-- Flash Messages -->
<?php echo display_flash_message(); ?>

<!-- Welcome Section -->
<div class="welcome-section">
    <h2>
        <i class="fas fa-shield-alt"></i> <?php echo $lang === 'english' ? 'Admin Dashboard' : 'Dashboard ya Admin'; ?>
    </h2>
    <p><?php echo $lang === 'english' ? 'Platform overview and management' : 'Igenzura rya platform'; ?></p>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Students' : 'Abanyeshuri'; ?></div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-school"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_schools']); ?></div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Schools' : 'Amashuri'; ?></div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_agents']); ?></div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Agents' : 'Abahagarariye'; ?></div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-crown"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active_subscriptions']); ?></div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Active Subscriptions' : 'Amabwiriza Akora'; ?></div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['exams_today']); ?></div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Exams Today' : 'Ibizamini Uyu Munsi'; ?></div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['revenue_this_month']); ?> RWF</div>
            <div class="stat-label"><?php echo $lang === 'english' ? 'Revenue (Month)' : 'Amafaranga (Ukwezi)'; ?></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="section-card">
            <h3 class="section-title">
                <i class="fas fa-user-plus"></i> <?php echo $lang === 'english' ? 'Recent Users' : 'Abakoresha Bashya'; ?>
            </h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'english' ? 'Name' : 'Izina'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Email' : 'Email'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Role' : 'Uruhare'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Registered' : 'Yiyandikishije'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge-role badge-<?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo format_date($u['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="section-card">
            <h3 class="section-title">
                <i class="fas fa-money-bill"></i> <?php echo $lang === 'english' ? 'Recent Payments' : 'Kwishyura Gushya'; ?>
            </h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'english' ? 'User' : 'Umukoresha'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Amount' : 'Amafaranga'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Method' : 'Uburyo'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Date' : 'Itariki'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo format_currency($p['amount']); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo str_replace('_', ' ', $p['payment_method']); ?>
                                </span>
                            </td>
                            <td><?php echo format_date($p['payment_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Exam Attempts -->
<div class="row">
    <div class="col-12">
        <div class="section-card">
            <h3 class="section-title">
                <i class="fas fa-clipboard-list"></i> <?php echo $lang === 'english' ? 'Recent Exam Attempts' : 'Ibizamini Bigezweho'; ?>
            </h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'english' ? 'Student' : 'Umunyeshuri'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Exam' : 'Ikizamini'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Score' : 'Amanota'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Status' : 'Uko bimeze'; ?></th>
                            <th><?php echo $lang === 'english' ? 'Date' : 'Itariki'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_exams as $exam): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['full_name']); ?></td>
                            <td><?php echo $exam['exam_code']; ?> - <?php echo htmlspecialchars($exam['exam_name_en']); ?></td>
                            <td><?php echo round($exam['score_percentage']); ?>%</td>
                            <td>
                                <?php if ($exam['passed']): ?>
                                    <span class="badge badge-success"><?php echo $lang === 'english' ? 'Passed' : 'Yatsindiye'; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo $lang === 'english' ? 'Failed' : 'Ntiyatsindiye'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo format_datetime($exam['start_time']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/admin-footer.php';
?>