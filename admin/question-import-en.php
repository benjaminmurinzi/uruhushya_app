<?php
/**
 * Import English Questions from Excel
 * 
 * Upload Excel file with English questions
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();
$lang = $admin['language_preference'] ?? 'english';

$error = '';
$success = '';
$imported_count = 0;
$errors = [];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed';
    } elseif ($file['size'] > 5000000) { // 5MB limit
        $error = 'File is too large (max 5MB)';
    } else {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['csv', 'xlsx', 'xls'])) {
            $error = 'Invalid file format. Please upload CSV or Excel file';
        } else {
            // Process CSV file
            if ($file_ext === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                
                if ($handle) {
                    // Skip header row
                    $header = fgetcsv($handle);
                    
                    $row_number = 1;
                    while (($data = fgetcsv($handle)) !== false) {
                        $row_number++;
                        
                        // Validate required fields
                        if (count($data) < 7) {
                            $errors[] = "Row {$row_number}: Insufficient columns";
                            continue;
                        }
                        
                        $question_text = trim($data[0]);
                        $choice_a = trim($data[1]);
                        $choice_b = trim($data[2]);
                        $choice_c = trim($data[3]);
                        $choice_d = trim($data[4] ?? '');
                        $correct_answer = trim(strtoupper($data[5]));
                        $difficulty = trim(strtolower($data[6]));
                        $course_id = !empty($data[7]) ? (int)$data[7] : 1;
                        $explanation = trim($data[8] ?? '');
                        
                        // Validate
                        if (empty($question_text)) {
                            $errors[] = "Row {$row_number}: Question text is empty";
                            continue;
                        }
                        
                        if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                            $errors[] = "Row {$row_number}: Invalid correct answer (must be A, B, C, or D)";
                            continue;
                        }
                        
                        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                            $difficulty = 'medium';
                        }
                        
                        try {
                            // Insert question (English only)
                            $question_sql = "INSERT INTO questions 
                                           (question_text_en, difficulty_level, course_id, explanation_en, status, created_at) 
                                           VALUES (?, ?, ?, ?, 'active', NOW())";
                            
                            db_query($question_sql, [
                                $question_text,
                                $difficulty,
                                $course_id,
                                $explanation
                            ]);
                            
                            $question_id = db_last_id();
                            
                            // Insert choices
                            $choices = [
                                ['text' => $choice_a, 'is_correct' => ($correct_answer === 'A'), 'order' => 0],
                                ['text' => $choice_b, 'is_correct' => ($correct_answer === 'B'), 'order' => 1],
                                ['text' => $choice_c, 'is_correct' => ($correct_answer === 'C'), 'order' => 2]
                            ];
                            
                            if (!empty($choice_d)) {
                                $choices[] = ['text' => $choice_d, 'is_correct' => ($correct_answer === 'D'), 'order' => 3];
                            }
                            
                            foreach ($choices as $choice) {
                                $choice_sql = "INSERT INTO question_choices 
                                             (question_id, choice_text_en, is_correct, sort_order, created_at) 
                                             VALUES (?, ?, ?, ?, NOW())";
                                
                                db_query($choice_sql, [
                                    $question_id,
                                    $choice['text'],
                                    $choice['is_correct'],
                                    $choice['order']
                                ]);
                            }
                            
                            $imported_count++;
                            
                        } catch (Exception $e) {
                            $errors[] = "Row {$row_number}: " . $e->getMessage();
                        }
                    }
                    
                    fclose($handle);
                    
                    if ($imported_count > 0) {
                        $success = "Successfully imported {$imported_count} English questions!";
                        log_activity(get_user_id(), 'questions_import', "Imported {$imported_count} English questions");
                    }
                }
            }
        }
    }
}

// Get courses for template
$courses = db_fetch_all("SELECT course_id, course_name_en FROM courses WHERE status = 'active' ORDER BY course_name_en");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Import English Questions' : 'Injiza Ibibazo by\'Icyongereza'; ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --admin-color: #dc3545;
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
        
        .import-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--admin-color);
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 50px 30px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: var(--admin-color);
            background: rgba(220, 53, 69, 0.05);
        }
        
        .upload-icon {
            font-size: 64px;
            color: var(--admin-color);
            margin-bottom: 20px;
        }
        
        .custom-file-label::after {
            content: "Browse";
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .template-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .btn-download-template {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
        }
        
        .btn-upload {
            background: var(--admin-color);
            color: white;
            padding: 15px 50px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            border: none;
        }
        
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .error-list li {
            color: #721c24;
            margin-bottom: 5px;
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
                </ul>
                
                <div class="user-menu dropdown">
                    <button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo explode(' ', $admin['full_name'])[0]; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="profile.php">Profile</a>
                        <a class="dropdown-item" href="settings.php">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="../public/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="import-card">
                <h1 class="page-title">
                    <i class="fas fa-file-import"></i> 
                    <?php echo $lang === 'english' ? 'Import English Questions' : 'Injiza Ibibazo by\'Icyongereza'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $lang === 'english' 
                        ? 'Upload a CSV file with English questions to bulk import' 
                        : 'Ohereza dosiye ya CSV ifite ibibazo by\'Icyongereza'; ?>
                </p>

                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Import Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <h5 class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php echo $lang === 'english' ? 'Import Errors:' : 'Amakosa yo Kwinjiza:'; ?>
                        </h5>
                        <ul>
                            <?php foreach (array_slice($errors, 0, 20) as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($errors) > 20): ?>
                                <li><em><?php echo $lang === 'english' ? 'And ' . (count($errors) - 20) . ' more errors...' : 'N\'amakosa ' . (count($errors) - 20) . ' yandi...'; ?></em></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="info-box">
                    <h5>
                        <i class="fas fa-info-circle"></i> 
                        <?php echo $lang === 'english' ? 'Important Notes:' : 'Ibyitonderwa:'; ?>
                    </h5>
                    <ul class="mb-0">
                        <li><?php echo $lang === 'english' ? 'Only CSV format is supported' : 'Gusa dosiye ya CSV irakoresha'; ?></li>
                        <li><?php echo $lang === 'english' ? 'Maximum file size: 5MB' : 'Ingano ntarengwa: 5MB'; ?></li>
                        <li><?php echo $lang === 'english' ? 'Questions will be imported in ENGLISH only' : 'Ibibazo bizinjizwa mu CYONGEREZA gusa'; ?></li>
                        <li><?php echo $lang === 'english' ? 'Use the template below for correct format' : 'Koresha template iri hasi kugira ngo ubone imiterere ikwiye'; ?></li>
                    </ul>
                </div>

                <!-- Download Template -->
                <div class="template-section">
                    <h5 class="mb-3">
                        <i class="fas fa-download"></i> 
                        <?php echo $lang === 'english' ? 'Download Template' : 'Kuramo Template'; ?>
                    </h5>
                    <p class="text-muted">
                        <?php echo $lang === 'english' 
                            ? 'Download the CSV template and fill it with your English questions' 
                            : 'Kuramo dosiye ya template uyuzuze ibibazo by\'Icyongereza'; ?>
                    </p>
                    <a href="download-template-en.php" class="btn btn-download-template">
                        <i class="fas fa-file-download"></i> 
                        <?php echo $lang === 'english' ? 'Download English Template' : 'Kuramo Template y\'Icyongereza'; ?>
                    </a>
                    
                    <div class="mt-3">
                        <strong><?php echo $lang === 'english' ? 'CSV Format:' : 'Imiterere ya CSV:'; ?></strong><br>
                        <code style="font-size: 12px; background: #fff; padding: 10px; display: block; margin-top: 10px; border-radius: 5px;">
                            question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, course_id, explanation
                        </code>
                    </div>
                </div>

                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-area">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h4><?php echo $lang === 'english' ? 'Upload Your CSV File' : 'Ohereza Dosiye Yawe ya CSV'; ?></h4>
                        <p class="text-muted mb-4">
                            <?php echo $lang === 'english' ? 'Select a CSV file with English questions' : 'Hitamo dosiye ya CSV ifite ibibazo by\'Icyongereza'; ?>
                        </p>
                        
                        <div class="custom-file" style="max-width: 400px; margin: 0 auto;">
                            <input type="file" name="excel_file" class="custom-file-input" id="excelFile" accept=".csv" required>
                            <label class="custom-file-label" for="excelFile">
                                <?php echo $lang === 'english' ? 'Choose file' : 'Hitamo dosiye'; ?>
                            </label>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-upload">
                            <i class="fas fa-upload"></i> 
                            <?php echo $lang === 'english' ? 'Upload and Import' : 'Ohereza Kandi Winjize'; ?>
                        </button>
                        
                        <a href="questions.php" class="btn btn-secondary ml-3" style="padding: 15px 30px; border-radius: 30px;">
                            <i class="fas fa-arrow-left"></i> 
                            <?php echo $lang === 'english' ? 'Back to Questions' : 'Subira ku Bibazo'; ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update file name in label
        $('#excelFile').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
    </script>
</body>
</html>