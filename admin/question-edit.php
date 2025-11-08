<?php
/**
 * Edit Question - Bilingual Form
 * 
 * Edit existing question with both EN and RW fields
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Require admin login
require_role('admin');

$admin = get_logged_in_user();
$lang = $admin['language_preference'] ?? 'english';

// Get question ID
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$question_id) {
    set_flash_message('error', 'Invalid question ID');
    redirect('questions.php');
}

// Get question details
$question = db_fetch("SELECT * FROM questions WHERE question_id = ?", [$question_id]);

if (!$question) {
    set_flash_message('error', 'Question not found');
    redirect('questions.php');
}

// Get question choices
$choices = db_fetch_all(
    "SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order",
    [$question_id]
);

// Get all courses
$courses = db_fetch_all("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name_en");

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $question_text_en = clean_input($_POST['question_text_en'] ?? '');
    $question_text_rw = clean_input($_POST['question_text_rw'] ?? '');
    $explanation_en = clean_input($_POST['explanation_en'] ?? '');
    $explanation_rw = clean_input($_POST['explanation_rw'] ?? '');
    $difficulty_level = clean_input($_POST['difficulty_level'] ?? 'medium');
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 1;
    
    // Get choices
    $choices_data = [];
    for ($i = 0; $i < 4; $i++) {
        if (isset($_POST["choice_{$i}_en"]) && !empty($_POST["choice_{$i}_en"])) {
            $choices_data[] = [
                'choice_id' => isset($_POST["choice_{$i}_id"]) ? (int)$_POST["choice_{$i}_id"] : 0,
                'text_en' => clean_input($_POST["choice_{$i}_en"]),
                'text_rw' => clean_input($_POST["choice_{$i}_rw"] ?? ''),
                'is_correct' => isset($_POST['correct_answer']) && $_POST['correct_answer'] == $i ? 1 : 0,
                'order' => $i
            ];
        }
    }
    
    // Validate
    if (empty($question_text_en) && empty($question_text_rw)) {
        $error = 'Question text is required (at least one language)';
    } elseif (count($choices_data) < 2) {
        $error = 'At least 2 answer choices are required';
    } elseif (!isset($_POST['correct_answer'])) {
        $error = 'Please select the correct answer';
    } else {
        try {
            // Start transaction
            db_query("START TRANSACTION");
            
            // Update question
            $update_question = "UPDATE questions 
                               SET question_text_en = ?,
                                   question_text_rw = ?,
                                   explanation_en = ?,
                                   explanation_rw = ?,
                                   difficulty_level = ?,
                                   course_id = ?,
                                   updated_at = NOW()
                               WHERE question_id = ?";
            
            db_query($update_question, [
                $question_text_en,
                $question_text_rw,
                $explanation_en,
                $explanation_rw,
                $difficulty_level,
                $course_id,
                $question_id
            ]);
            
            // Delete old choices
            db_query("DELETE FROM question_choices WHERE question_id = ?", [$question_id]);
            
            // Insert updated choices
            foreach ($choices_data as $choice) {
                $insert_choice = "INSERT INTO question_choices 
                                 (question_id, choice_text_en, choice_text_rw, is_correct, sort_order, created_at)
                                 VALUES (?, ?, ?, ?, ?, NOW())";
                
                db_query($insert_choice, [
                    $question_id,
                    $choice['text_en'],
                    $choice['text_rw'],
                    $choice['is_correct'],
                    $choice['order']
                ]);
            }
            
            // Commit transaction
            db_query("COMMIT");
            
            // Log activity
            log_activity(get_user_id(), 'question_update', "Updated question ID: {$question_id}");
            
            $success = 'Question updated successfully!';
            
            // Refresh question data
            $question = db_fetch("SELECT * FROM questions WHERE question_id = ?", [$question_id]);
            $choices = db_fetch_all(
                "SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order",
                [$question_id]
            );
            
        } catch (Exception $e) {
            // Rollback on error
            db_query("ROLLBACK");
            $error = 'Error updating question: ' . $e->getMessage();
            error_log("Question update error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Edit Question' : 'Hindura Ikibazo'; ?> - <?php echo APP_NAME; ?></title>
    
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
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 1000px;
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
        
        .language-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .language-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .language-title.english {
            color: #007bff;
        }
        
        .language-title.kinyarwanda {
            color: #6f42c1;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .required {
            color: var(--admin-color);
        }
        
        .choice-group {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .choice-group.correct {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .choice-letter {
            position: absolute;
            top: -12px;
            left: 15px;
            background: white;
            padding: 0 10px;
            font-weight: 700;
            color: var(--admin-color);
            font-size: 18px;
        }
        
        .correct-badge {
            position: absolute;
            top: -12px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .btn-save {
            background: var(--admin-color);
            color: white;
            padding: 15px 50px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            border: none;
        }
        
        .btn-save:hover {
            background: #c82333;
            color: white;
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
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="questions.php">
                            <i class="fas fa-question-circle"></i> Questions
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
            <div class="form-card">
                <h1 class="page-title">
                    <i class="fas fa-edit"></i> 
                    <?php echo $lang === 'english' ? 'Edit Question' : 'Hindura Ikibazo'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $lang === 'english' 
                        ? 'Update question details in both English and Kinyarwanda' 
                        : 'Hindura amakuru y\'ikibazo mu Cyongereza no mu Kinyarwanda'; ?>
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

                <!-- Edit Form -->
                <form method="POST" action="">
                    <!-- ENGLISH SECTION -->
                    <div class="language-section">
                        <h3 class="language-title english">
                            <i class="fas fa-flag"></i> English Version
                        </h3>

                        <!-- Question Text EN -->
                        <div class="form-group">
                            <label for="question_text_en">
                                Question Text (English) <span class="required">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="question_text_en" 
                                name="question_text_en" 
                                rows="3"
                                placeholder="Enter question text in English..."
                            ><?php echo htmlspecialchars($question['question_text_en'] ?? ''); ?></textarea>
                        </div>

                        <!-- Explanation EN -->
                        <div class="form-group">
                            <label for="explanation_en">
                                Explanation (English) <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="explanation_en" 
                                name="explanation_en" 
                                rows="2"
                                placeholder="Why is this the correct answer?"
                            ><?php echo htmlspecialchars($question['explanation_en'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- KINYARWANDA SECTION -->
                    <div class="language-section">
                        <h3 class="language-title kinyarwanda">
                            <i class="fas fa-flag"></i> Kinyarwanda Version
                        </h3>

                        <!-- Question Text RW -->
                        <div class="form-group">
                            <label for="question_text_rw">
                                Ikibazo (Kinyarwanda) <span class="required">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="question_text_rw" 
                                name="question_text_rw" 
                                rows="3"
                                placeholder="Andika ikibazo mu Kinyarwanda..."
                            ><?php echo htmlspecialchars($question['question_text_rw'] ?? ''); ?></textarea>
                        </div>

                        <!-- Explanation RW -->
                        <div class="form-group">
                            <label for="explanation_rw">
                                Ibisobanuro (Kinyarwanda) <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="explanation_rw" 
                                name="explanation_rw" 
                                rows="2"
                                placeholder="Kuki iki ni gisubizo gikwiye?"
                            ><?php echo htmlspecialchars($question['explanation_rw'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- ANSWER CHOICES (BILINGUAL) -->
                    <div class="language-section">
                        <h3 class="language-title" style="color: var(--admin-color);">
                            <i class="fas fa-list"></i> Answer Choices (Both Languages)
                        </h3>

                        <?php 
                        $letters = ['A', 'B', 'C', 'D'];
                        // Find which choice is correct
                        $correct_index = -1;
                        foreach ($choices as $index => $choice) {
                            if ($choice['is_correct']) {
                                $correct_index = $index;
                                break;
                            }
                        }
                        
                        for ($i = 0; $i < 4; $i++): 
                            $choice = isset($choices[$i]) ? $choices[$i] : null;
                            $is_correct = ($i === $correct_index);
                        ?>
                            <div class="choice-group <?php echo $is_correct ? 'correct' : ''; ?>">
                                <div class="choice-letter">Choice <?php echo $letters[$i]; ?></div>
                                <?php if ($is_correct): ?>
                                    <div class="correct-badge">
                                        <i class="fas fa-check"></i> Correct Answer
                                    </div>
                                <?php endif; ?>
                                
                                <input type="hidden" name="choice_<?php echo $i; ?>_id" value="<?php echo $choice ? $choice['choice_id'] : ''; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>English <?php echo $i < 3 ? '<span class="required">*</span>' : ''; ?></label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                name="choice_<?php echo $i; ?>_en"
                                                placeholder="Choice <?php echo $letters[$i]; ?> in English"
                                                value="<?php echo $choice ? htmlspecialchars($choice['choice_text_en']) : ''; ?>"
                                                <?php echo $i < 3 ? 'required' : ''; ?>
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Kinyarwanda <?php echo $i < 3 ? '<span class="required">*</span>' : ''; ?></label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                name="choice_<?php echo $i; ?>_rw"
                                                placeholder="Igisubizo <?php echo $letters[$i]; ?> mu Kinyarwanda"
                                                value="<?php echo $choice ? htmlspecialchars($choice['choice_text_rw']) : ''; ?>"
                                                <?php echo $i < 3 ? 'required' : ''; ?>
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check">
                                    <input 
                                        type="radio" 
                                        class="form-check-input" 
                                        id="correct_<?php echo $i; ?>" 
                                        name="correct_answer" 
                                        value="<?php echo $i; ?>"
                                        <?php echo $is_correct ? 'checked' : ''; ?>
                                        required
                                    >
                                    <label class="form-check-label" for="correct_<?php echo $i; ?>">
                                        <strong>This is the correct answer</strong>
                                    </label>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Select the radio button next to the correct answer. Choices A, B, and C are required. Choice D is optional.
                        </small>
                    </div>

                    <!-- ADDITIONAL SETTINGS -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="course_id">
                                    Category <span class="required">*</span>
                                </label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <?php foreach ($courses as $course): ?>
                                        <?php $course_name = $lang === 'english' ? $course['course_name_en'] : $course['course_name_rw']; ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php echo $question['course_id'] == $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="difficulty_level">
                                    Difficulty Level <span class="required">*</span>
                                </label>
                                <select class="form-control" id="difficulty_level" name="difficulty_level" required>
                                    <option value="easy" <?php echo $question['difficulty_level'] === 'easy' ? 'selected' : ''; ?>>Easy</option>
                                    <option value="medium" <?php echo $question['difficulty_level'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="hard" <?php echo $question['difficulty_level'] === 'hard' ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save"></i> 
                            <?php echo $lang === 'english' ? 'Update Question' : 'Bika Impinduka'; ?>
                        </button>
                        
                        <a href="questions.php" class="btn btn-secondary ml-3" style="padding: 15px 30px; border-radius: 30px;">
                            <i class="fas fa-times"></i> 
                            <?php echo $lang === 'english' ? 'Cancel' : 'Hagarika'; ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Highlight selected correct answer
        $('input[name="correct_answer"]').on('change', function() {
            $('.choice-group').removeClass('correct');
            $('.correct-badge').remove();
            
            const selectedIndex = $(this).val();
            const selectedGroup = $(this).closest('.choice-group');
            
            selectedGroup.addClass('correct');
            selectedGroup.find('.choice-letter').after('<div class="correct-badge"><i class="fas fa-check"></i> Correct Answer</div>');
        });
    </script>
</body>
</html>