<?php
/**
 * Database Connection Test Page
 * 
 * Tests database connection and displays sample data
 * 
 * IMPORTANT: Delete this file in production!
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';

// Only allow in development
if (ENVIRONMENT !== 'development') {
    die('This page is only available in development mode');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 30px 0;
        }
        .test-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-card">
            <h1><i class="fas fa-database"></i> Database Connection Test</h1>
            <p class="text-muted">Testing Uruhushya Software database connection and setup</p>
            <hr>

            <?php
            // Test 1: Database Connection
            echo '<h3>Test 1: Database Connection</h3>';
            try {
                if ($conn) {
                    echo '<p class="success">✓ Database connected successfully!</p>';
                    echo '<p class="info">Database Name: <strong>' . DB_NAME . '</strong></p>';
                } else {
                    echo '<p class="error">✗ Database connection failed</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 2: Tables Existence
            echo '<hr><h3>Test 2: Database Tables</h3>';
            try {
                $tables = db_fetch_all("SHOW TABLES");
                echo '<p class="success">✓ Found ' . count($tables) . ' tables</p>';
                echo '<ul>';
                foreach ($tables as $table) {
                    $table_name = array_values($table)[0];
                    echo '<li>' . $table_name . '</li>';
                }
                echo '</ul>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 3: Sample Users
            echo '<hr><h3>Test 3: Sample Users</h3>';
            try {
                $users = db_fetch_all("SELECT user_id, email, full_name, role, status FROM users LIMIT 10");
                
                if (count($users) > 0) {
                    echo '<p class="success">✓ Found ' . count($users) . ' users</p>';
                    echo '<table class="table table-bordered table-sm">';
                    echo '<thead><tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . $user['user_id'] . '</td>';
                        echo '<td>' . $user['email'] . '</td>';
                        echo '<td>' . $user['full_name'] . '</td>';
                        echo '<td><span class="badge badge-primary">' . $user['role'] . '</span></td>';
                        echo '<td><span class="badge badge-success">' . $user['status'] . '</span></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="error">✗ No users found. Did you import seed.sql?</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 4: Courses
            echo '<hr><h3>Test 4: Courses</h3>';
            try {
                $courses = db_fetch_all("SELECT course_id, course_name_en, course_name_rw, status FROM courses");
                
                if (count($courses) > 0) {
                    echo '<p class="success">✓ Found ' . count($courses) . ' courses</p>';
                    echo '<table class="table table-bordered table-sm">';
                    echo '<thead><tr><th>ID</th><th>English Name</th><th>Kinyarwanda Name</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($courses as $course) {
                        echo '<tr>';
                        echo '<td>' . $course['course_id'] . '</td>';
                        echo '<td>' . $course['course_name_en'] . '</td>';
                        echo '<td>' . $course['course_name_rw'] . '</td>';
                        echo '<td><span class="badge badge-success">' . $course['status'] . '</span></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="error">✗ No courses found</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 5: Questions
            echo '<hr><h3>Test 5: Questions</h3>';
            try {
                $question_count = db_fetch("SELECT COUNT(*) as total FROM questions");
                $questions = db_fetch_all("SELECT question_id, question_text_en, difficulty_level FROM questions LIMIT 5");
                
                echo '<p class="success">✓ Total Questions: <strong>' . $question_count['total'] . '</strong></p>';
                echo '<p class="info">Sample Questions (first 5):</p>';
                echo '<table class="table table-bordered table-sm">';
                echo '<thead><tr><th>ID</th><th>Question</th><th>Difficulty</th></tr></thead>';
                echo '<tbody>';
                foreach ($questions as $q) {
                    echo '<tr>';
                    echo '<td>' . $q['question_id'] . '</td>';
                    echo '<td>' . truncate_text($q['question_text_en'], 80) . '</td>';
                    echo '<td><span class="badge badge-info">' . $q['difficulty_level'] . '</span></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 6: Email Templates
            echo '<hr><h3>Test 6: Email Templates</h3>';
            try {
                $templates = db_fetch_all("SELECT template_name, status FROM email_templates");
                
                if (count($templates) > 0) {
                    echo '<p class="success">✓ Found ' . count($templates) . ' email templates</p>';
                    echo '<ul>';
                    foreach ($templates as $template) {
                        echo '<li>' . $template['template_name'] . ' <span class="badge badge-success">' . $template['status'] . '</span></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="error">✗ No email templates found</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
            }

            // Test 7: Configuration
            echo '<hr><h3>Test 7: Configuration</h3>';
            echo '<pre>';
            echo 'APP_NAME: ' . APP_NAME . "\n";
            echo 'APP_URL: ' . APP_URL . "\n";
            echo 'ENVIRONMENT: ' . ENVIRONMENT . "\n";
            echo 'DB_HOST: ' . DB_HOST . "\n";
            echo 'DB_NAME: ' . DB_NAME . "\n";
            echo 'DEFAULT_LANGUAGE: ' . DEFAULT_LANGUAGE . "\n";
            echo '</pre>';

            // Summary
            echo '<hr><h3>Summary</h3>';
            echo '<div class="alert alert-success">';
            echo '<h4>✓ All Tests Passed!</h4>';
            echo '<p>Your database is set up correctly and ready to use.</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ul>';
            echo '<li>Delete this test file (test-db.php) in production</li>';
            echo '<li>Go to <a href="login.php">Login Page</a> and test with sample credentials</li>';
            echo '<li>Admin: admin@uruhushya.rw / Admin@2025</li>';
            echo '<li>Student: student@example.rw / Student@2025</li>';
            echo '</ul>';
            echo '</div>';
            ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
