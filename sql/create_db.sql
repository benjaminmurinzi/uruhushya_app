-- ============================================
-- Uruhushya Software Database Schema
-- Developer: Benjamin NIYOMURINZI
-- Date: 2025-01-07
-- Database Name: uruhushya (UPDATED)
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS uruhushya 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE uruhushya;

-- ============================================
-- TABLE: users
-- Purpose: Store all platform users (Admin, School, Agent, Student)
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'school', 'agent', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    school_id INT NULL,
    agent_id INT NULL,
    language_preference ENUM('kinyarwanda', 'english') DEFAULT 'kinyarwanda',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_school (school_id),
    INDEX idx_agent (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: schools
-- Purpose: Store driving school information
-- ============================================
CREATE TABLE schools (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    tin_number VARCHAR(50) UNIQUE,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    max_students INT DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: agents
-- Purpose: Store agent information and referral tracking
-- ============================================
CREATE TABLE agents (
    agent_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_code VARCHAR(20) UNIQUE NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    total_referrals INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    account_name VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_agent_code (agent_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: subscriptions
-- Purpose: Track user subscription plans
-- ============================================
CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_type ENUM('trial', 'individual_week', 'individual_month', 
                          'school_month', 'school_3month', 'school_6month', 'school_year') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    amount DECIMAL(10,2) NOT NULL,
    payment_id INT NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: payments
-- Purpose: Record all payment transactions
-- ============================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_id INT NULL,
    payment_method ENUM('mtn_momo', 'airtel_money', 'card', 'bank_transfer', 'cash') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RWF',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    phone_number VARCHAR(20),
    description TEXT,
    agent_commission DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: courses
-- Purpose: Course categories (Signs, Regulations, etc.)
-- ============================================
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name_en VARCHAR(255) NOT NULL,
    course_name_rw VARCHAR(255) NOT NULL,
    description_en TEXT,
    description_rw TEXT,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: lessons
-- Purpose: Individual lessons within courses
-- ============================================
CREATE TABLE lessons (
    lesson_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lesson_title_en VARCHAR(255) NOT NULL,
    lesson_title_rw VARCHAR(255) NOT NULL,
    lesson_content_en TEXT,
    lesson_content_rw TEXT,
    video_url VARCHAR(500),
    image_url VARCHAR(500),
    sort_order INT DEFAULT 0,
    duration_minutes INT DEFAULT 5,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: questions
-- Purpose: Exam question bank
-- ============================================
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    question_text_en TEXT NOT NULL,
    question_text_rw TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false') DEFAULT 'multiple_choice',
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    image_url VARCHAR(500),
    explanation_en TEXT,
    explanation_rw TEXT,
    points INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: question_choices
-- Purpose: Multiple choice answers for questions
-- ============================================
CREATE TABLE question_choices (
    choice_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text_en VARCHAR(500) NOT NULL,
    choice_text_rw VARCHAR(500) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: exams
-- Purpose: Exam configurations/templates
-- ============================================
CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name_en VARCHAR(255) NOT NULL,
    exam_name_rw VARCHAR(255) NOT NULL,
    exam_code VARCHAR(50) UNIQUE,
    description_en TEXT,
    description_rw TEXT,
    total_questions INT DEFAULT 50,
    passing_score INT DEFAULT 35,
    time_limit_minutes INT DEFAULT 30,
    is_free BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_exam_code (exam_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: exam_attempts
-- Purpose: Track user exam attempts
-- ============================================
CREATE TABLE exam_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    total_questions INT NOT NULL,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    unanswered INT DEFAULT 0,
    score_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    time_taken_seconds INT DEFAULT 0,
    passed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    INDEX idx_user_exam (user_id, exam_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: exam_responses
-- Purpose: Individual question responses per attempt
-- ============================================
CREATE TABLE exam_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    choice_id INT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    time_taken_seconds INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (choice_id) REFERENCES question_choices(choice_id) ON DELETE SET NULL,
    INDEX idx_attempt (attempt_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: session_devices
-- Purpose: Track user devices and sessions for access control
-- ============================================
CREATE TABLE session_devices (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token (session_token),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: irembo_requests
-- Purpose: Handle registration requests from Irembo portal
-- ============================================
CREATE TABLE irembo_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    national_id VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    request_type ENUM('provisional_license', 'theory_test', 'practical_test') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_national_id (national_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: notifications
-- Purpose: Log of sent emails and SMS
-- ============================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    notification_type ENUM('email', 'sms', 'push') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: school_applications
-- Purpose: Store driving school registration applications
-- ============================================
CREATE TABLE school_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    tin_number VARCHAR(50) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    business_license_url VARCHAR(500),
    rdb_certificate_url VARCHAR(500),
    application_letter_url VARCHAR(500),
    max_students_requested INT DEFAULT 50,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (contact_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: agent_applications
-- Purpose: Store agent registration applications
-- ============================================
CREATE TABLE agent_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    national_id VARCHAR(16) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    id_card_url VARCHAR(500),
    bank_statement_url VARCHAR(500),
    motivation_letter TEXT,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_national_id (national_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: student_progress
-- Purpose: Track student learning progress (lessons completed)
-- ============================================
CREATE TABLE student_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    time_spent_seconds INT DEFAULT 0,
    completion_percentage INT DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: certificates
-- Purpose: Store generated certificates for students
-- ============================================
CREATE TABLE certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    certificate_code VARCHAR(50) UNIQUE NOT NULL,
    certificate_type ENUM('course_completion', 'exam_excellence', 'full_completion') NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NULL,
    total_exams_passed INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    total_lessons_completed INT DEFAULT 0,
    certificate_url VARCHAR(500),
    verification_url VARCHAR(500),
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    issued_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_code (certificate_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: email_templates
-- Purpose: Store email templates for automated notifications
-- ============================================
CREATE TABLE email_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    subject_en VARCHAR(500) NOT NULL,
    subject_rw VARCHAR(500) NOT NULL,
    body_en TEXT NOT NULL,
    body_rw TEXT NOT NULL,
    variables TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (template_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: student_analytics
-- Purpose: Aggregate student performance metrics
-- ============================================
CREATE TABLE student_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_study_time_seconds INT DEFAULT 0,
    total_lessons_completed INT DEFAULT 0,
    total_lessons_available INT DEFAULT 0,
    lessons_completion_rate DECIMAL(5,2) DEFAULT 0.00,
    total_exams_taken INT DEFAULT 0,
    total_exams_passed INT DEFAULT 0,
    total_exams_failed INT DEFAULT 0,
    average_exam_score DECIMAL(5,2) DEFAULT 0.00,
    best_exam_score DECIMAL(5,2) DEFAULT 0.00,
    weakest_category INT NULL,
    strongest_category INT NULL,
    current_streak_days INT DEFAULT 0,
    last_activity_date DATE NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (weakest_category) REFERENCES courses(course_id) ON DELETE SET NULL,
    FOREIGN KEY (strongest_category) REFERENCES courses(course_id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_analytics (user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: activity_log
-- Purpose: Track all user activities for analytics and security
-- ============================================
CREATE TABLE activity_log (
    log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    activity_type ENUM('login', 'logout', 'exam_start', 'exam_complete', 
                       'lesson_view', 'lesson_complete', 'payment', 
                       'registration', 'profile_update', 'password_change',
                       'certificate_generated', 'application_submitted') NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (activity_type),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Additional indexes for performance
-- ============================================
ALTER TABLE users ADD INDEX idx_last_login (last_login);
ALTER TABLE subscriptions ADD INDEX idx_dates (start_date, end_date);
ALTER TABLE payments ADD INDEX idx_date (payment_date);