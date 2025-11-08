<?php
/**
 * Exam Results Page
 * 
 * Display exam results with detailed feedback
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

// Get attempt ID
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if (!$attempt_id) {
    set_flash_message('error', 'Invalid exam attempt');
    redirect('exams.php');
}

// Get attempt details
$attempt = db_fetch(
    "SELECT ea.*, e.* FROM exam_attempts ea 
     JOIN exams e ON ea.exam_id = e.exam_id 
     WHERE ea.attempt_id = ? AND ea.user_id = ?",
    [$attempt_id, $user_id]
);

if (!$attempt || $attempt['status'] !== 'completed') {
    set_flash_message('error', 'Exam results not found');
    redirect('exams.php');
}

// Get all responses with questions and correct answers
$responses_sql = "SELECT er.*, q.*, 
                  qc_selected.choice_text_en as selected_choice_en,
                  qc_selected.choice_text_rw as selected_choice_rw,
                  qc_correct.choice_text_en as correct_choice_en,
                  qc_correct.choice_text_rw as correct_choice_rw
                  FROM exam_responses er
                  JOIN questions q ON er.question_id = q.question_id
                  LEFT JOIN question_choices qc_selected ON er.choice_id = qc_selected.choice_id
                  LEFT JOIN question_choices qc_correct ON q.question_id = qc_correct.question_id AND qc_correct.is_correct = 1
                  WHERE er.attempt_id = ?
                  ORDER BY er.response_id";

$responses = db_fetch_all($responses_sql, [$attempt_id]);

$exam_name = $lang === 'english' ? $attempt['exam_name_en'] : $attempt['exam_name_rw'];
$passed = $attempt['passed'];
$score_percentage = round($attempt['score_percentage']);

$minutes = floor($attempt['time_taken_seconds'] / 60);
$seconds = $attempt['time_taken_seconds'] % 60;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'english' ? 'Exam Results' : 'Ibisubizo by\'Ikizamini'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2C3E96;
            --secondary-color: #1E2A5E;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, 
                <?php echo $passed ? 'var(--success-color)' : 'var(--danger-color)'; ?> 0%, 
                <?php echo $passed ? '#20c997' : '#c82333'; ?> 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .results-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .results-header {
            background: <?php echo $passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
            color: white;
            padding: 50px 40px;
            text-align: center;
        }
        
        .results-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: scaleIn 0.5s;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .results-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .results-subtitle {
            font-size: 24px;
            opacity: 0.95;
        }
        
        .score-display {
            font-size: 120px;
            font-weight: 700;
            margin: 30px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .results-body {
            padding: 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .stat-icon.correct { color: var(--success-color); }
        .stat-icon.wrong { color: var(--danger-color); }
        .stat-icon.unanswered { color: #6c757d; }
        .stat-icon.time { color: var(--primary-color); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .feedback-box {
            background: <?php echo $passed ? '#d4edda' : '#f8d7da'; ?>;
            border-left: 5px solid <?php echo $passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .feedback-box h4 {
            color: <?php echo $passed ? '#155724' : '#721c24'; ?>;
            margin-bottom: 15px;
        }
        
        .feedback-box p {
            color: <?php echo $passed ? '#155724' : '#721c24'; ?>;
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .question-review {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #e9ecef;
        }
        
        .question-review.correct {
            border-left-color: var(--success-color);
            background: #d4edda;
        }
        
        .question-review.wrong {
            border-left-color: var(--danger-color);
            background: #f8d7da;
        }
        
        .question-number {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .question-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .answer-row {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
        }
        
        .answer-row.your-answer {
            background: rgba(0,0,0,0.05);
        }
        
        .answer-label {
            font-weight: 600;
            margin-right: 10px;
        }
        
        .correct-icon {
            color: var(--success-color);
            font-size: 20px;
        }
        
        .wrong-icon {
            color: var(--danger-color);
            font-size: 20px;
        }
        
        .explanation-box {
            background: rgba(44, 62, 150, 0.1);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
        }
        
        .explanation-box strong {
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        
        .btn-action {
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-retake {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-retake:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-dashboard {
            background: #6c757d;
            color: white;
        }
        
        .btn-dashboard:hover {
            background: #5a6268;
            color: white;
            transform: scale(1.05);
        }
        
        .btn-exams {
            background: #ffc107;
            color: #000;
        }
        
        .btn-exams:hover {
            background: #e0a800;
            color: #000;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="results-container">
        <!-- Results Header -->
        <div class="results-card">
            <div class="results-header">
                <div class="results-icon">
                    <?php if ($passed): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                    <?php endif; ?>
                </div>
                
                <div class="results-title">
                    <?php if ($passed): ?>
                        <?php echo $lang === 'english' ? 'Congratulations!' : 'Amashongore!'; ?>
                    <?php else: ?>
                        <?php echo $lang === 'english' ? 'Keep Trying!' : 'Komeza Ugerageze!'; ?>
                    <?php endif; ?>
                </div>
                
                <div class="results-subtitle">
                    <?php echo $attempt['exam_code']; ?>: <?php echo htmlspecialchars($exam_name); ?>
                </div>
                
                <div class="score-display">
                    <?php echo $score_percentage; ?>%
                </div>
                
                <div style="font-size: 20px; opacity: 0.95;">
                    <?php if ($passed): ?>
                        <?php echo $lang === 'english' ? 'You Passed!' : 'Watsinze!'; ?>
                    <?php else: ?>
                        <?php echo $lang === 'english' ? 'Not Passed' : 'Ntiwatsindiye'; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Results Body -->
            <div class="results-body">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-icon correct">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $attempt['correct_answers']; ?></div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Correct' : 'Byiza'; ?>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon wrong">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $attempt['wrong_answers']; ?></div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Wrong' : 'Bitatsinze'; ?>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon unanswered">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $attempt['unanswered']; ?></div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Unanswered' : 'Ntibyasubijwe'; ?>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-icon time">
                            <i class="far fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo $minutes; ?>:<?php echo str_pad($seconds, 2, '0', STR_PAD_LEFT); ?></div>
                        <div class="stat-label">
                            <?php echo $lang === 'english' ? 'Time Taken' : 'Igihe Cyakoreweho'; ?>
                        </div>
                    </div>
                </div>

                <!-- Feedback -->
                <div class="feedback-box">
                    <h4>
                        <i class="fas fa-<?php echo $passed ? 'trophy' : 'info-circle'; ?>"></i> 
                        <?php echo $passed 
                            ? ($lang === 'english' ? 'Excellent Work!' : 'Ibikorwa Byiza Cyane!') 
                            : ($lang === 'english' ? 'Keep Learning!' : 'Komeza Kwiga!'); ?>
                    </h4>
                    <p>
                        <?php if ($passed): ?>
                            <?php echo $lang === 'english' 
                                ? "You've successfully passed this exam with a score of {$score_percentage}%. Great job! Continue practicing to maintain and improve your knowledge." 
                                : "Watsinze neza iki kizamini hamwe n'amanota {$score_percentage}%. Ibikorwa byiza! Komeza kwiga kugira ngo ukomeze kandi wongere ubumenyi bwawe."; ?>
                        <?php else: ?>
                            <?php echo $lang === 'english' 
                                ? "You scored {$score_percentage}%, but the passing score is " . round(($attempt['passing_score']/$attempt['total_questions'])*100) . "%. Don't give up! Review the lessons and try again. Every mistake is a learning opportunity." 
                                : "Waronse amanota {$score_percentage}%, ariko amanota yo kutsinda ni " . round(($attempt['passing_score']/$attempt['total_questions'])*100) . "%. Ntucike intege! Subiramo amasomo hanyuma ugerageze. Buri kosa ni amahirwe yo kwiga."; ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Detailed Review -->
                <h3 class="section-title">
                    <i class="fas fa-list-alt"></i> 
                    <?php echo $lang === 'english' ? 'Detailed Review' : 'Isubiramo Ryihariye'; ?>
                </h3>

                <?php $question_num = 1; ?>
                <?php foreach ($responses as $response): ?>
                    <?php 
                        $question_text = $lang === 'english' ? $response['question_text_en'] : $response['question_text_rw'];
                        $selected_text = $lang === 'english' ? $response['selected_choice_en'] : $response['selected_choice_rw'];
                        $correct_text = $lang === 'english' ? $response['correct_choice_en'] : $response['correct_choice_rw'];
                        $explanation = $lang === 'english' ? $response['explanation_en'] : $response['explanation_rw'];
                    ?>
                    <div class="question-review <?php echo $response['is_correct'] ? 'correct' : 'wrong'; ?>">
                        <div class="question-number">
                            <?php echo $lang === 'english' ? 'Question' : 'Ikibazo'; ?> <?php echo $question_num; ?>
                            <?php if ($response['is_correct']): ?>
                                <i class="fas fa-check-circle correct-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle wrong-icon"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($question_text); ?>
                        </div>
                        
                        <div class="answer-row your-answer">
                            <span class="answer-label">
                                <?php echo $lang === 'english' ? 'Your Answer:' : 'Igisubizo Cyawe:'; ?>
                            </span>
                            <?php echo htmlspecialchars($selected_text ?? 'No answer'); ?>
                        </div>
                        
                        <?php if (!$response['is_correct']): ?>
                        <div class="answer-row">
                            <span class="answer-label" style="color: var(--success-color);">
                                <?php echo $lang === 'english' ? 'Correct Answer:' : 'Igisubizo Gikwiye:'; ?>
                            </span>
                            <?php echo htmlspecialchars($correct_text); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($explanation)): ?>
                        <div class="explanation-box">
                            <strong>
                                <?php echo $lang === 'english' ? 'Explanation:' : 'Ibisobanuro:'; ?>
                            </strong><br>
                            <?php echo htmlspecialchars($explanation); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php $question_num++; ?>
                <?php endforeach; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="exam-start.php?id=<?php echo $attempt['exam_id']; ?>" class="btn btn-action btn-retake">
                        <i class="fas fa-redo"></i> 
                        <?php echo $lang === 'english' ? 'Retake Exam' : 'Ongeramo Ikizamini'; ?>
                    </a>
                    
                    <a href="exams.php" class="btn btn-action btn-exams">
                        <i class="fas fa-clipboard-list"></i> 
                        <?php echo $lang === 'english' ? 'All Exams' : 'Ibizamini Byose'; ?>
                    </a>
                    
                    <a href="dashboard.php" class="btn btn-action btn-dashboard">
                        <i class="fas fa-home"></i> 
                        <?php echo $lang === 'english' ? 'Dashboard' : 'Dashboard'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Confetti for passing (optional fun feature!) -->
    <?php if ($passed): ?>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <script>
        // Celebrate with confetti!
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
        
        setTimeout(function() {
            confetti({
                particleCount: 50,
                angle: 60,
                spread: 55,
                origin: { x: 0 }
            });
        }, 250);
        
        setTimeout(function() {
            confetti({
                particleCount: 50,
                angle: 120,
                spread: 55,
                origin: { x: 1 }
            });
        }, 400);
    </script>
    <?php endif; ?>
</body>
</html>