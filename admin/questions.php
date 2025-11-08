<?php
/**
 * Admin Question Management - Language Based on Admin Preference
 * 
 * Manage exam questions - displays in admin's chosen language
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();
$lang = $admin['language_preference'] ?? 'english';

// Get filters
$category_filter = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? clean_input($_GET['difficulty']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["q.status = 'active'"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "c.course_id = ?";
    $params[] = $category_filter;
}

if (!empty($difficulty_filter)) {
    $where_conditions[] = "q.difficulty_level = ?";
    $params[] = $difficulty_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(q.question_text_en LIKE ? OR q.question_text_rw LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total questions
$count_sql = "SELECT COUNT(*) as total 
              FROM questions q
              LEFT JOIN courses c ON q.course_id = c.course_id
              $where_clause";
$total_questions = db_fetch($count_sql, $params)['total'];
$total_pages = ceil($total_questions / $per_page);

// Get questions
$questions_sql = "SELECT q.*, c.course_name_en, c.course_name_rw
                  FROM questions q
                  LEFT JOIN courses c ON q.course_id = c.course_id
                  $where_clause
                  ORDER BY q.created_at DESC
                  LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;

$questions = db_fetch_all($questions_sql, $params);

// Get all courses for filter
$courses = db_fetch_all("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name_en");

// Get statistics
$stats = [
    'total' => db_fetch("SELECT COUNT(*) as count FROM questions WHERE status = 'active'")['count'],
    'easy' => db_fetch("SELECT COUNT(*) as count FROM questions WHERE difficulty_level = 'easy' AND status = 'active'")['count'],
    'medium' => db_fetch("SELECT COUNT(*) as count FROM questions WHERE difficulty_level = 'medium' AND status = 'active'")['count'],
    'hard' => db_fetch("SELECT COUNT(*) as count FROM questions WHERE difficulty_level = 'hard' AND status = 'active'")['count']
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Question Management' : 'Gucunga Ibibazo'; ?> - <?php echo APP_NAME; ?></title>
    
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
            margin-bottom: 20px;
        }
        
        .language-switcher {
            display: inline-flex;
            gap: 5px;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 25px;
            margin-bottom: 20px;
        }
        
        .lang-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lang-btn.active {
            background: var(--admin-color);
            color: white;
        }
        
        .lang-btn:hover {
            background: rgba(220, 53, 69, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action-main {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-add {
            background: var(--admin-color);
            color: white;
        }
        
        .btn-add:hover {
            background: var(--admin-dark);
            color: white;
        }
        
        .btn-import {
            background: #28a745;
            color: white;
        }
        
        .btn-import:hover {
            background: #218838;
            color: white;
        }
        
        .btn-export {
            background: #007bff;
            color: white;
        }
        
        .btn-export:hover {
            background: #0056b3;
            color: white;
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-icon.total { color: var(--admin-color); }
        .stat-icon.easy { color: #28a745; }
        .stat-icon.medium { color: #ffc107; }
        .stat-icon.hard { color: #dc3545; }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .questions-table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table {
            font-size: 14px;
        }
        
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            color: var(--admin-color);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            vertical-align: top;
        }
        
        .question-text {
            max-width: 350px;
        }
        
        .choices-list {
            font-size: 13px;
        }
        
        .choice-item {
            margin-bottom: 5px;
        }
        
        .badge-difficulty {
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .badge-difficulty.easy { background: #d4edda; color: #155724; }
        .badge-difficulty.medium { background: #fff3cd; color: #856404; }
        .badge-difficulty.hard { background: #f8d7da; color: #721c24; }
        
        .correct-answer {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .btn-action {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .pagination .page-link {
            color: var(--admin-color);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--admin-color);
            border-color: var(--admin-color);
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> <?php echo $lang === 'english' ? 'Dashboard' : 'Dashboard'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> <?php echo $lang === 'english' ? 'Users' : 'Abakoresha'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="questions.php">
                            <i class="fas fa-question-circle"></i> <?php echo $lang === 'english' ? 'Questions' : 'Ibibazo'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-inbox"></i> <?php echo $lang === 'english' ? 'Applications' : 'Amasaba'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="content.php">
                            <i class="fas fa-book"></i> <?php echo $lang === 'english' ? 'Content' : 'Ibirimo'; ?>
                        </a>
                    </li>
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo explode(' ', $admin['full_name'])[0]; ?>
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
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="page-title mb-0">
                        <i class="fas fa-question-circle"></i> 
                        <?php echo $lang === 'english' ? 'Question Management' : 'Gucunga Ibibazo'; ?>
                    </h1>
                    
                    <!-- Language Switcher -->
                    <div class="language-switcher">
                        <form method="POST" action="change-admin-language.php" style="display: inline;">
                            <input type="hidden" name="redirect" value="questions.php">
                            <button type="submit" name="language" value="english" class="lang-btn <?php echo $lang === 'english' ? 'active' : ''; ?>">
                                English
                            </button>
                            <button type="submit" name="language" value="kinyarwanda" class="lang-btn <?php echo $lang === 'kinyarwanda' ? 'active' : ''; ?>">
                                Kinyarwanda
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="question-add.php" class="btn btn-action-main btn-add">
                     <i class="fas fa-plus"></i> <?php echo $lang === 'english' ? 'Add New Question' : 'Ongeraho Ikibazo'; ?>
                    </a>
    
                    <!-- Import English Questions -->
                    <a href="question-import-en.php" class="btn btn-action-main btn-import">
                       <i class="fas fa-file-import"></i> <?php echo $lang === 'english' ? 'Import English Questions' : 'Injiza Ibibazo by\'Icyongereza'; ?>
                    </a>
    
                    <!-- Import Kinyarwanda Questions -->
                    <a href="question-import-rw.php" class="btn btn-action-main" style="background: #6f42c1; color: white;">
                      <i class="fas fa-file-import"></i> <?php echo $lang === 'english' ? 'Import Kinyarwanda Questions' : 'Injiza Ibibazo by\'Ikinyarwanda'; ?>
                    </a>
    
                    <a href="question-export.php" class="btn btn-action-main btn-export">
                      <i class="fas fa-file-export"></i> <?php echo $lang === 'english' ? 'Export to Excel' : 'Sohora muri Excel'; ?>
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon total">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Total Questions' : 'Ibibazo Byose'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon easy">
                                <i class="fas fa-smile"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['easy']); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Easy' : 'Byoroshye'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon medium">
                                <i class="fas fa-meh"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['medium']); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Medium' : 'Byagati'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon hard">
                                <i class="fas fa-frown"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($stats['hard']); ?></div>
                            <div class="stat-label">
                                <?php echo $lang === 'english' ? 'Hard' : 'Bigoye'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="questions.php" class="form-inline">
                    <div class="form-group mr-3">
                        <label class="mr-2">
                            <?php echo $lang === 'english' ? 'Search:' : 'Shakisha:'; ?>
                        </label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="<?php echo $lang === 'english' ? 'Search questions...' : 'Shakisha ibibazo...'; ?>"
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="min-width: 300px;"
                        >
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">
                            <?php echo $lang === 'english' ? 'Category:' : 'Icyiciro:'; ?>
                        </label>
                        <select name="category" class="form-control">
                            <option value=""><?php echo $lang === 'english' ? 'All Categories' : 'Ibyiciro Byose'; ?></option>
                            <?php foreach ($courses as $course): ?>
                                <?php $course_name = $lang === 'english' ? $course['course_name_en'] : $course['course_name_rw']; ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo $category_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">
                            <?php echo $lang === 'english' ? 'Difficulty:' : 'Urwego:'; ?>
                        </label>
                        <select name="difficulty" class="form-control">
                            <option value=""><?php echo $lang === 'english' ? 'All Levels' : 'Inzego Zose'; ?></option>
                            <option value="easy" <?php echo $difficulty_filter === 'easy' ? 'selected' : ''; ?>>
                                <?php echo $lang === 'english' ? 'Easy' : 'Byoroshye'; ?>
                            </option>
                            <option value="medium" <?php echo $difficulty_filter === 'medium' ? 'selected' : ''; ?>>
                                <?php echo $lang === 'english' ? 'Medium' : 'Byagati'; ?>
                            </option>
                            <option value="hard" <?php echo $difficulty_filter === 'hard' ? 'selected' : ''; ?>>
                                <?php echo $lang === 'english' ? 'Hard' : 'Bigoye'; ?>
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> <?php echo $lang === 'english' ? 'Search' : 'Shakisha'; ?>
                    </button>
                    
                    <a href="questions.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> <?php echo $lang === 'english' ? 'Reset' : 'Ongera'; ?>
                    </a>
                </form>
            </div>

            <!-- Questions Table -->
            <div class="questions-table-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 350px;">
                                    <?php echo $lang === 'english' ? 'Question Text' : 'Ikibazo'; ?>
                                </th>
                                <th style="width: 280px;">
                                    <?php echo $lang === 'english' ? 'Answer Choices' : 'Ibisubizo'; ?>
                                </th>
                                <th style="width: 80px; text-align: center;">
                                    <?php echo $lang === 'english' ? 'Correct' : 'Igisubizo'; ?><br>
                                    <?php echo $lang === 'english' ? 'Answer' : 'Gikwiye'; ?>
                                </th>
                                <th style="width: 120px;">
                                    <?php echo $lang === 'english' ? 'Category' : 'Icyiciro'; ?>
                                </th>
                                <th style="width: 100px;">
                                    <?php echo $lang === 'english' ? 'Difficulty' : 'Urwego'; ?>
                                </th>
                                <th style="width: 150px;">
                                    <?php echo $lang === 'english' ? 'Actions' : 'Ibikorwa'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">
                                            <?php echo $lang === 'english' ? 'No questions found' : 'Nta bibazo byabonetse'; ?>
                                        </p>
                                        <a href="question-add.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> 
                                            <?php echo $lang === 'english' ? 'Add First Question' : 'Ongeraho Ikibazo Cya Mbere'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <?php 
                                        // Get question text in admin's language
                                        $question_text = $lang === 'english' ? $q['question_text_en'] : $q['question_text_rw'];
                                        
                                        // Get choices
                                        $choices = db_fetch_all(
                                            "SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order",
                                            [$q['question_id']]
                                        );
                                    ?>
                                    <tr>
                                        <td><?php echo $q['question_id']; ?></td>
                                        
                                        <!-- Question Text -->
                                        <td class="question-text">
                                            <?php echo htmlspecialchars(truncate_text($question_text, 150)); ?>
                                        </td>
                                        
                                        <!-- Choices -->
                                        <td class="choices-list">
                                            <?php foreach ($choices as $index => $choice): ?>
                                                <?php 
                                                    $letter = chr(65 + $index); // A, B, C, D
                                                    $choice_text = $lang === 'english' ? $choice['choice_text_en'] : $choice['choice_text_rw'];
                                                ?>
                                                <div class="choice-item">
                                                    <strong><?php echo $letter; ?>)</strong>
                                                    <?php echo htmlspecialchars(truncate_text($choice_text, 50)); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        
                                        <!-- Correct Answer -->
                                        <td style="text-align: center;">
                                            <?php 
                                                // Find correct answer letter
                                                $correct_letter = '';
                                                foreach ($choices as $index => $choice) {
                                                    if ($choice['is_correct']) {
                                                        $correct_letter = chr(65 + $index);
                                                        break;
                                                    }
                                                }
                                            ?>
                                            <div class="correct-answer">
                                                <?php echo $correct_letter; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Category -->
                                        <td>
                                            <?php 
                                                $category_name = $lang === 'english' ? $q['course_name_en'] : $q['course_name_rw'];
                                                echo htmlspecialchars($category_name ?? 'N/A'); 
                                            ?>
                                        </td>
                                        
                                        <!-- Difficulty -->
                                        <td>
                                            <span class="badge-difficulty <?php echo $q['difficulty_level']; ?>">
                                                <?php 
                                                    $difficulty_labels = [
                                                        'easy' => $lang === 'english' ? 'Easy' : 'Byoroshye',
                                                        'medium' => $lang === 'english' ? 'Medium' : 'Byagati',
                                                        'hard' => $lang === 'english' ? 'Hard' : 'Bigoye'
                                                    ];
                                                    echo $difficulty_labels[$q['difficulty_level']];
                                                ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td>
                                            <a href="question-view.php?id=<?php echo $q['question_id']; ?>" 
                                               class="btn btn-action btn-view" 
                                               title="<?php echo $lang === 'english' ? 'View' : 'Reba'; ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="question-edit.php?id=<?php echo $q['question_id']; ?>" 
                                               class="btn btn-action btn-edit" 
                                               title="<?php echo $lang === 'english' ? 'Edit' : 'Hindura'; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-action btn-delete" 
                                                    onclick="deleteQuestion(<?php echo $q['question_id']; ?>)" 
                                                    title="<?php echo $lang === 'english' ? 'Delete' : 'Siba'; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $lang === 'english' ? 'Previous' : 'Ibambere'; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $lang === 'english' ? 'Next' : 'Ikurikira'; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Results Info -->
                <div class="text-center text-muted mt-3">
                    <small>
                        <?php echo $lang === 'english' ? 'Showing' : 'Byerekanwe'; ?> 
                        <?php echo $offset + 1; ?> 
                        <?php echo $lang === 'english' ? 'to' : 'kugeza'; ?> 
                        <?php echo min($offset + $per_page, $total_questions); ?> 
                        <?php echo $lang === 'english' ? 'of' : 'muri'; ?> 
                        <?php echo number_format($total_questions); ?> 
                        <?php echo $lang === 'english' ? 'questions' : 'ibibazo'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteQuestion(questionId) {
            const confirmMsg = '<?php echo $lang === "english" ? "Are you sure you want to delete this question? This action cannot be undone." : "Uzi neza ko ushaka gusiba iki kibazo? Ibi ntibishobora gusubizwa."; ?>';
            
            if (confirm(confirmMsg)) {
                fetch('question-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'question_id=' + questionId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('<?php echo $lang === "english" ? "Question deleted successfully!" : "Ikibazo cyasibwe neza!"; ?>');
                        location.reload();
                    } else {
                        alert('<?php echo $lang === "english" ? "Error:" : "Ikosa:"; ?> ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php echo $lang === "english" ? "An error occurred while deleting the question" : "Habayeho ikosa mu gusiba ikibazo"; ?>');
                });
            }
        }
    </script>
</body>
</html>