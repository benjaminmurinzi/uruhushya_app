<?php
/**
 * Exam Taking Interface - Single Question Navigation
 * 
 * Shows ONE question at a time with navigation and timer
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

// Start exam (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_id'])) {
    $exam_id = (int)$_POST['exam_id'];
    
    // Get exam details
    $exam = db_fetch("SELECT * FROM exams WHERE exam_id = ? AND status = 'active'", [$exam_id]);
    
    if (!$exam) {
        set_flash_message('error', 'Exam not found');
        redirect('exams.php');
    }
    
    // Create exam attempt
    $create_attempt = "INSERT INTO exam_attempts (user_id, exam_id, total_questions, start_time, status) 
                       VALUES (?, ?, ?, NOW(), 'in_progress')";
    db_query($create_attempt, [$user_id, $exam_id, $exam['total_questions']]);
    
    $attempt_id = db_last_id();
    
    // Log activity
    log_activity($user_id, 'exam_start', 'Started exam: ' . $exam['exam_code']);
    
    // Redirect to question page
    redirect('exam-take.php?attempt_id=' . $attempt_id);
}

// Get attempt ID from URL
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

if (!$attempt) {
    set_flash_message('error', 'Exam attempt not found');
    redirect('exams.php');
}

// Check if already completed
if ($attempt['status'] === 'completed') {
    redirect('exam-results.php?attempt_id=' . $attempt_id);
}

// Get random questions for this exam
$questions_sql = "SELECT q.*, 
                  (SELECT choice_id FROM exam_responses WHERE attempt_id = ? AND question_id = q.question_id) as selected_choice
                  FROM questions q
                  WHERE q.course_id IN (SELECT course_id FROM courses WHERE status = 'active')
                  AND q.status = 'active'
                  ORDER BY RAND()
                  LIMIT ?";

$questions = db_fetch_all($questions_sql, [$attempt_id, $attempt['total_questions']]);

// Get current question index
$current_index = isset($_GET['q']) ? (int)$_GET['q'] : 0;
if ($current_index < 0) $current_index = 0;
if ($current_index >= count($questions)) $current_index = count($questions) - 1;

$current_question = $questions[$current_index];

// Get choices for current question
$choices = db_fetch_all(
    "SELECT * FROM question_choices WHERE question_id = ? ORDER BY sort_order",
    [$current_question['question_id']]
);

// Calculate time remaining
$start_time = strtotime($attempt['start_time']);
$time_limit_seconds = $attempt['time_limit_minutes'] * 60;
$elapsed_seconds = time() - $start_time;
$remaining_seconds = max(0, $time_limit_seconds - $elapsed_seconds);

// Auto-submit if time expired
if ($remaining_seconds <= 0) {
    // Submit exam automatically
    include 'exam-submit.php';
    exit;
}

$exam_name = $lang === 'english' ? $attempt['exam_name_en'] : $attempt['exam_name_rw'];
$question_text = $lang === 'english' ? $current_question['question_text_en'] : $current_question['question_text_rw'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'rw'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $attempt['exam_code']; ?> - <?php echo $lang === 'english' ? 'Question' : 'Ikibazo'; ?> <?php echo $current_index + 1; ?></title>
    
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
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        /* Top Bar with Timer */
        .exam-topbar {
            background: var(--primary-color);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .timer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exam-info {
            font-size: 18px;
            font-weight: 600;
        }
        
        .timer {
            background: rgba(255,255,255,0.2);
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timer.warning {
            background: #ffc107;
            color: #000;
            animation: pulse 1s infinite;
        }
        
        .timer.danger {
            background: #dc3545;
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Question Container */
        .question-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .question-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .question-number {
            font-size: 16px;
            color: var(--primary-color);
            font-weight: 700;
            background: rgba(44, 62, 150, 0.1);
            padding: 8px 20px;
            border-radius: 20px;
        }
        
        .question-difficulty {
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .difficulty-easy { background: #d4edda; color: #155724; }
        .difficulty-medium { background: #fff3cd; color: #856404; }
        .difficulty-hard { background: #f8d7da; color: #721c24; }
        
        .question-text {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .question-image {
            max-width: 100%;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        /* Choices */
        .choices-container {
            margin-bottom: 30px;
        }
        
        .choice-item {
            background: #f8f9fa;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-size: 18px;
        }
        
        .choice-item:hover {
            border-color: var(--primary-color);
            background: rgba(44, 62, 150, 0.05);
            transform: translateX(5px);
        }
        
        .choice-item.selected {
            border-color: var(--primary-color);
            background: rgba(44, 62, 150, 0.1);
            font-weight: 600;
        }
        
        .choice-radio {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            cursor: pointer;
        }
        
        .choice-label {
            flex: 1;
            cursor: pointer;
            margin: 0;
        }
        
        /* Navigation */
        .navigation-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .btn-nav {
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-previous {
            background: #6c757d;
            color: white;
        }
        
        .btn-previous:hover {
            background: #5a6268;
            color: white;
            transform: scale(1.05);
        }
        
        .btn-next {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-next:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
        }
        
        .btn-submit:hover {
            background: #218838;
            color: white;
            transform: scale(1.05);
        }
        
        .btn-nav:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Progress Bar */
        .progress-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .progress {
            height: 30px;
            border-radius: 15px;
            background: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 15px;
            font-weight: 700;
            font-size: 16px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        /* Question Navigator */
        .question-navigator {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        
        .navigator-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .question-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 10px;
        }
        
        .question-dot {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: #e9ecef;
            color: #6c757d;
            border: 2px solid transparent;
        }
        
        .question-dot:hover {
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .question-dot.answered {
            background: var(--primary-color);
            color: white;
        }
        
        .question-dot.current {
            border-color: var(--primary-color);
            background: rgba(44, 62, 150, 0.1);
            transform: scale(1.15);
        }
    </style>
</head>
<body>
    <!-- Top Bar with Timer -->
    <div class="exam-topbar">
        <div class="container">
            <div class="timer-container">
                <div class="exam-info">
                    <i class="fas fa-clipboard-check"></i> 
                    <?php echo $attempt['exam_code']; ?>: <?php echo htmlspecialchars($exam_name); ?>
                </div>
                <div class="timer <?php echo $remaining_seconds < 300 ? 'warning' : ''; ?> <?php echo $remaining_seconds < 60 ? 'danger' : ''; ?>" id="timer">
                    <i class="far fa-clock"></i>
                    <span id="timer-display">--:--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="question-container">
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-text">
                <span>
                    <?php echo $lang === 'english' ? 'Question' : 'Ikibazo'; ?> 
                    <?php echo $current_index + 1; ?> / <?php echo count($questions); ?>
                </span>
                <span>
                    <?php 
                        $answered_count = 0;
                        foreach ($questions as $q) {
                            if ($q['selected_choice']) $answered_count++;
                        }
                        echo $answered_count . ' / ' . count($questions) . ' ';
                        echo $lang === 'english' ? 'Answered' : 'Byasubijwe';
                    ?>
                </span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo (($current_index + 1) / count($questions)) * 100; ?>%">
                    <?php echo round((($current_index + 1) / count($questions)) * 100); ?>%
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Question -->
            <div class="col-lg-8">
                <!-- Question Card -->
                <div class="question-card">
                    <div class="question-header">
                        <div class="question-number">
                            <?php echo $lang === 'english' ? 'Question' : 'Ikibazo'; ?> 
                            <?php echo $current_index + 1; ?>
                        </div>
                        <div class="question-difficulty difficulty-<?php echo $current_question['difficulty_level']; ?>">
                            <?php 
                                $difficulty_labels = [
                                    'easy' => $lang === 'english' ? 'Easy' : 'Byoroshye',
                                    'medium' => $lang === 'english' ? 'Medium' : 'Byagati',
                                    'hard' => $lang === 'english' ? 'Hard' : 'Bigoye'
                                ];
                                echo $difficulty_labels[$current_question['difficulty_level']];
                            ?>
                        </div>
                    </div>

                    <div class="question-text">
                        <?php echo nl2br(htmlspecialchars($question_text)); ?>
                    </div>

                    <?php if (!empty($current_question['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($current_question['image_url']); ?>" 
                             alt="Question Image" 
                             class="question-image">
                    <?php endif; ?>

                    <!-- Choices -->
                    <form id="answerForm">
                        <div class="choices-container">
                            <?php foreach ($choices as $choice): ?>
                                <?php 
                                    $choice_text = $lang === 'english' ? $choice['choice_text_en'] : $choice['choice_text_rw'];
                                    $is_selected = ($current_question['selected_choice'] == $choice['choice_id']);
                                ?>
                                <div class="choice-item <?php echo $is_selected ? 'selected' : ''; ?>" 
                                     onclick="selectChoice(<?php echo $choice['choice_id']; ?>)">
                                    <input 
                                        type="radio" 
                                        name="choice" 
                                        value="<?php echo $choice['choice_id']; ?>" 
                                        class="choice-radio"
                                        id="choice_<?php echo $choice['choice_id']; ?>"
                                        <?php echo $is_selected ? 'checked' : ''; ?>
                                        onchange="saveAnswer(<?php echo $choice['choice_id']; ?>)"
                                    >
                                    <label class="choice-label" for="choice_<?php echo $choice['choice_id']; ?>">
                                        <?php echo htmlspecialchars($choice_text); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>

                    <!-- Navigation Buttons -->
                    <div class="navigation-container">
                        <?php if ($current_index > 0): ?>
                            <a href="exam-take.php?attempt_id=<?php echo $attempt_id; ?>&q=<?php echo $current_index - 1; ?>" 
                               class="btn btn-nav btn-previous">
                                <i class="fas fa-arrow-left"></i>
                                <?php echo $lang === 'english' ? 'Previous' : 'Ibambere'; ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-nav btn-previous" disabled>
                                <i class="fas fa-arrow-left"></i>
                                <?php echo $lang === 'english' ? 'Previous' : 'Ibambere'; ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($current_index < count($questions) - 1): ?>
                            <a href="exam-take.php?attempt_id=<?php echo $attempt_id; ?>&q=<?php echo $current_index + 1; ?>" 
                               class="btn btn-nav btn-next">
                                <?php echo $lang === 'english' ? 'Next' : 'Ikurikira'; ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-nav btn-submit" onclick="confirmSubmit()">
                                <?php echo $lang === 'english' ? 'Submit Exam' : 'Tanga Ikizamini'; ?>
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Question Navigator -->
            <div class="col-lg-4">
                <div class="question-navigator">
                    <div class="navigator-title">
                        <i class="fas fa-th"></i> 
                        <?php echo $lang === 'english' ? 'All Questions' : 'Ibibazo Byose'; ?>
                    </div>
                    <div class="question-grid">
                        <?php foreach ($questions as $index => $q): ?>
                            <a href="exam-take.php?attempt_id=<?php echo $attempt_id; ?>&q=<?php echo $index; ?>" 
                               class="question-dot <?php echo $q['selected_choice'] ? 'answered' : ''; ?> <?php echo $index == $current_index ? 'current' : ''; ?>">
                                <?php echo $index + 1; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for submitting exam -->
    <form id="submitExamForm" method="POST" action="exam-submit.php" style="display: none;">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
    </form>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Exam JavaScript -->
    <script>
        // Timer variables
        let remainingSeconds = <?php echo $remaining_seconds; ?>;
        const attemptId = <?php echo $attempt_id; ?>;
        const questionId = <?php echo $current_question['question_id']; ?>;
        
        // Start timer
        const timerInterval = setInterval(updateTimer, 1000);
        
        function updateTimer() {
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                alert('<?php echo $lang === "english" ? "Time is up! Your exam will be submitted automatically." : "Igihe cyarangiye! Ikizamini cyawe kizatangwa mu buryo bwikora."; ?>');
                document.getElementById('submitExamForm').submit();
                return;
            }
            
            remainingSeconds--;
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            
            const display = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            document.getElementById('timer-display').textContent = display;
            
            // Update timer color
            const timer = document.getElementById('timer');
            if (remainingSeconds < 60) {
                timer.className = 'timer danger';
            } else if (remainingSeconds < 300) {
                timer.className = 'timer warning';
            }
        }
        
        // Initialize timer display
        updateTimer();
        
        // Select choice
        function selectChoice(choiceId) {
            // Update UI
            document.querySelectorAll('.choice-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Check radio
            document.getElementById('choice_' + choiceId).checked = true;
            
            // Save answer
            saveAnswer(choiceId);
        }
        
        // Save answer via AJAX
        function saveAnswer(choiceId) {
            fetch('exam-save-answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'attempt_id=' + attemptId + '&question_id=' + questionId + '&choice_id=' + choiceId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Answer saved');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Confirm submit
        function confirmSubmit() {
            const answered = <?php echo $answered_count; ?>;
            const total = <?php echo count($questions); ?>;
            const unanswered = total - answered;
            
            let message = '<?php echo $lang === "english" 
                ? "Are you sure you want to submit your exam?" 
                : "Uzi neza ko ushaka gutanga ikizamini cyawe?"; ?>';
            
            if (unanswered > 0) {
                message += '\n\n<?php echo $lang === "english" 
                    ? "You have " : "Ufite "; ?>' + unanswered + '<?php echo $lang === "english" 
                    ? " unanswered questions!" : " ibibazo bidasubijwe!"; ?>';
            }
            
            if (confirm(message)) {
                document.getElementById('submitExamForm').submit();
            }
        }
        
        // Prevent accidental page close
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>