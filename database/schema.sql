-- Database Schema for Doon University ERP
-- Robust, Stable, Optimized & Efficient Version

CREATE DATABASE IF NOT EXISTS doon_university_erp
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doon_university_erp;

-- 1. Users Table (Core Identity)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty', 'department', 'admin') NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    signature_image VARCHAR(255) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_role (role)
);

-- 2. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) UNIQUE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Students Table (Academic Profile)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    enrollment_no VARCHAR(50) UNIQUE NOT NULL,
    roll_no VARCHAR(50) UNIQUE DEFAULT NULL,
    course VARCHAR(150) NOT NULL,
    batch_year INT NOT NULL,
    semester INT DEFAULT 1,
    department_name VARCHAR(100) DEFAULT NULL,
    father_name VARCHAR(100) DEFAULT NULL,
    mother_name VARCHAR(100) DEFAULT NULL,
    dob DATE DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    cast_category VARCHAR(20) DEFAULT NULL,
    is_ph ENUM('YES', 'NO') DEFAULT 'NO',
    abc_id VARCHAR(50) DEFAULT NULL,
    mobile VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_enrollment (enrollment_no),
    INDEX idx_roll (roll_no)
);

-- 4. Faculty Table
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_emp_code (employee_code)
);

-- 5. Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    department_id INT,
    credits INT DEFAULT 3,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_subj_code (code)
);

-- 6. Question Pool Table
CREATE TABLE IF NOT EXISTS question_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    question_text TEXT NOT NULL,
    marks INT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES faculties(id) ON DELETE SET NULL,
    INDEX idx_subj_diff (subject_id, difficulty)
);

-- 7. Exam Papers Table
CREATE TABLE IF NOT EXISTS exam_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    generated_date DATE NOT NULL,
    paper_json JSON NOT NULL,
    total_marks INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_exam_date (generated_date)
);

-- 8. Attendance Table (Highly Optimized)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_attendance (student_id, subject_id, date),
    INDEX idx_student_date (student_id, date)
);

-- 9. Marks Table
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_type ENUM('midterm', 'assignment', 'final', 'practical') NOT NULL,
    marks_obtained DECIMAL(5, 2) NOT NULL,
    max_marks DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_marks (student_id, subject_id, exam_type)
);

-- ==========================================
-- DUMMY DATA FOR INITIAL TESTING
-- ==========================================

-- Insert Admin
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('God Admin', 'admin@doonuniversity.edu', 'admin123', 'admin');

-- Insert Department
INSERT IGNORE INTO departments (name) VALUES 
('Computer Science'), ('Business Administration');

-- Insert Faculty
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('Dr. Sharma', 'sharma@doonuniversity.edu', 'faculty123', 'faculty');
INSERT IGNORE INTO faculties (user_id, department_id, employee_code) VALUES 
((SELECT id FROM users WHERE email='sharma@doonuniversity.edu'), 1, 'EMP001');

-- Insert Subjects
INSERT IGNORE INTO subjects (name, code, department_id) VALUES 
('Data Structures', 'CS201', 1),
('Database Systems', 'CS202', 1);

-- Insert Student
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('John Doe', 'john@student.doonuniversity.edu', 'student123', 'student');
INSERT IGNORE INTO students (user_id, enrollment_no, course, batch_year) VALUES 
((SELECT id FROM users WHERE email='john@student.doonuniversity.edu'), 'ENR2024001', 'B.Tech CS', 2024);

-- Insert Questions
INSERT IGNORE INTO question_pool (subject_id, question_text, marks, difficulty, created_by) VALUES 
(1, 'Explain Quick Sort algorithm with an example.', 10, 'medium', 1),
(1, 'What is a Binary Search Tree?', 5, 'easy', 1),
(1, 'Write an algorithm for Dijkstra''s shortest path.', 15, 'hard', 1),
(2, 'Explain ACID properties in DBMS.', 10, 'medium', 1),
(2, 'What is normalization? Explain up to 3NF.', 15, 'hard', 1);
