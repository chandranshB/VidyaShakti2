-- Database Schema for Doon University ERP

CREATE DATABASE IF NOT EXISTS doon_university_erp;
USE doon_university_erp;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty', 'department', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- 3. Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    enrollment_no VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(100) NOT NULL,
    batch_year INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Faculty Table
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- 5. Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
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
    FOREIGN KEY (created_by) REFERENCES faculties(id) ON DELETE SET NULL
);

-- 7. Exam Papers Table
CREATE TABLE IF NOT EXISTS exam_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    generated_date DATE NOT NULL,
    paper_json TEXT NOT NULL, -- Storing array of question IDs or full question objects
    total_marks INT NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 8. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 9. Marks Table
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_type ENUM('midterm', 'assignment', 'final') NOT NULL,
    marks_obtained DECIMAL(5, 2) NOT NULL,
    max_marks DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- ==========================================
-- DUMMY DATA FOR INITIAL TESTING
-- ==========================================

-- Insert Admin
INSERT INTO users (name, email, password, role) VALUES 
('God Admin', 'admin@doonuniversity.edu', 'admin123', 'admin');

-- Insert Department
INSERT INTO departments (name) VALUES 
('Computer Science'), ('Business Administration');

-- Insert Faculty
INSERT INTO users (name, email, password, role) VALUES 
('Dr. Sharma', 'sharma@doonuniversity.edu', 'faculty123', 'faculty');
INSERT INTO faculties (user_id, department_id, employee_code) VALUES 
(LAST_INSERT_ID(), 1, 'EMP001');

-- Insert Subjects
INSERT INTO subjects (name, code, department_id) VALUES 
('Data Structures', 'CS201', 1),
('Database Systems', 'CS202', 1);

-- Insert Student
INSERT INTO users (name, email, password, role) VALUES 
('John Doe', 'john@student.doonuniversity.edu', 'student123', 'student');
INSERT INTO students (user_id, enrollment_no, course, batch_year) VALUES 
(LAST_INSERT_ID(), 'ENR2024001', 'B.Tech CS', 2024);

-- Insert Questions
INSERT INTO question_pool (subject_id, question_text, marks, difficulty, created_by) VALUES 
(1, 'Explain Quick Sort algorithm with an example.', 10, 'medium', 1),
(1, 'What is a Binary Search Tree?', 5, 'easy', 1),
(1, 'Write an algorithm for Dijkstra''s shortest path.', 15, 'hard', 1),
(2, 'Explain ACID properties in DBMS.', 10, 'medium', 1),
(2, 'What is normalization? Explain up to 3NF.', 15, 'hard', 1);
