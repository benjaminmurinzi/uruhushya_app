<?php
/**
 * Exam Start Page - Instructions & Confirmation
 * 
 * Shows exam details and instructions before starting
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

// Get exam ID
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$exam_id) {
    set_flash_message('error', 'Invalid exam');
    redirect('exams.php');
}

// Get exam details
$exam = db_fetch("SELECT * FROM exams WHERE exam_id = ? AND status = 'active'", [$exam_id]);

if (!$exam) {
    set_flash_message('error', 'Exam not found');
    redirect('exams.php');
}

// Check subscription if not free
if (!$exam['is_free']) {
    $has_subscription = has_active_subscription($user_id);
    if (!$has_subscription) {
        set_flash_message('error', $lang === 'english' 
            ? 'You need an active subscription to take this exam' 
            : 'Ukeneye inyandiko ikora kugira ngo ukore iki kizamini');
        redirect('exams.php');
    }
}

// Get previous attempts
$previous_attempts = db_fetch_all(
    "SELECT * FROM exam_attempts WHERE user_id = ? AND exam_id = ? AND status = 'completed' ORDER BY start_time DESC LIMIT 5",
    [$user_id, $exam_id]
);

$exam_name = $lang === 'english' ? $exam['exam_name_en'] : $exam['exam_name_rw'];
$exam_desc = $lang === 'english' ? $exam['description_en'] : $exam['description_rw'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exam['exam_code']; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
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
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        
        .exam-start-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .exam-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .exam-header {
            background: var(--primary-color);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .exam-code {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .exam-title {
            font-size: 24px;
            opacity: 0.95;
        }
        
        .exam-body {
            padding: 40px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }
        
        .instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .instructions h5 {
            color: #856404;
            margin-bottom: 15px;
        }
        
        .instructions ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
            color: #856404;
        }
        
        .previous-attempts {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .attempt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .score-badge {
            font-size: 24px;
            font-weight: 700;
        }
        
        .score-passed {
            color: #28a745;
        }
        
        .score-failed {
            color: #dc3545;
        }
        
        .btn-start-exam {
            background: var(--primary-color);
            color: white;
            padding: 18px 50px;
            border-radius: 30px;
            font-size: 20px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .btn-start-exam:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(44, 62, 150, 0.3);
        }
        
        .btn-back {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="exam-start-container">
        <div class="exam-card">
            <!-- Exam Header -->
            <div class="exam-header">
                <div class="exam-code"><?php echo $exam['exam_code']; ?></div>
                <div class="exam-title"><?php echo htmlspecialchars($exam_name); ?></div>
            </div>

            <!-- Exam Body -->
            <div class="exam-body">
                <!-- Exam Info -->
                <div class="info-box">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div>
                            <strong><?php echo $exam['total_questions']; ?></strong> 
                            <?php echo $lang === 'english' ? 'Questions' : 'Ibibazo'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="far fa-clock"></i>
                        </div>
                        <div>
                            <strong><?php echo $exam['time_limit_minutes']; ?></strong> 
                            <?php echo $lang === 'english' ? 'Minutes' : 'Iminota'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <?php echo $lang === 'english' ? 'Pass with' : 'Tsinda hamwe na'; ?> 
                            <strong><?php echo $exam['passing_score']; ?>/<?php echo $exam['total_questions']; ?></strong>
                            (<?php echo round(($exam['passing_score']/$exam['total_questions'])*100); ?>%)
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="instructions">
                    <h5>
                        <i class="fas fa-info-circle"></i> 
                        <?php echo $lang === 'english' ? 'Instructions' : 'Amabwiriza'; ?>
                    </h5>
                    <ul>
                        <?php if ($lang === 'english'): ?>
                            <li>Read each question carefully before selecting your answer</li>
                            <li>You can navigate between questions using Next and Previous buttons</li>
                            <li>The timer will start when you click "Start Exam"</li>
                            <li>Your exam will be automatically submitted when time runs out</li>
                            <li>You can review and change your answers before submitting</li>
                            <li>Make sure you have a stable internet connection</li>
                        <?php else: ?>
                            <li>Soma buri kibazo neza mbere yo guhitamo igisubizo</li>
                            <li>Ushobora kugenda hagati y'ibibazo ukoresheje buto ya Next na Previous</li>
                            <li>Igihe kizatangira iyo ukanze "Tangira Ikizamini"</li>
                            <li>Ikizamini cyawe kizatangwa mu buryo bwikora iyo igihe cyarangiye</li>
                            <li>Ushobora kusubiramo no guhindura ibisubizo byawe mbere yo gutanga</li>
                            <li>Witondere ko ufite internet ikora neza</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Previous Attempts -->
                <?php if (!empty($previous_attempts)): ?>
                <div class="previous-attempts">
                    <h5 style="color: var(--primary-color); margin-bottom: 20px;">
                        <i class="fas fa-history"></i> 
                        <?php echo $lang === 'english' ? 'Your Previous Attempts' : 'Ibigeragezo Byawe Byaheruka'; ?>
                    </h5>
                    
                    <?php foreach ($previous_attempts as $attempt): ?>
                    <div class="attempt-item">
                        <div>
                            <div style="font-weight: 600;">
                                <?php echo format_datetime($attempt['start_time']); ?>
                            </div>
                            <small class="text-muted">
                                <?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?> 
                                <?php echo $lang === 'english' ? 'correct' : 'byiza'; ?>
                            </small>
                        </div>
                        <div class="score-badge <?php echo $attempt['passed'] ? 'score-passed' : 'score-failed'; ?>">
                            <?php echo round($attempt['score_percentage']); ?>%
                            <?php if ($attempt['passed']): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Start Button -->
                <form method="POST" action="exam-take.php">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <button type="submit" class="btn btn-start-exam">
                        <i class="fas fa-play-circle"></i> 
                        <?php echo $lang === 'english' ? 'Start Exam Now' : 'Tangira Ikizamini Nonaha'; ?>
                    </button>
                </form>

                <div class="text-center">
                    <a href="exams.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> 
                        <?php echo $lang === 'english' ? 'Back to Exams' : 'Subira ku Bizamini'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>