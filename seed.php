<?php
require_once 'includes/db.php';

echo "Setting up dummy users...\n";

function createUser($pdo, $name, $email, $password, $role) {
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn();
        }
        echo "Error creating user: " . $e->getMessage() . "\n";
        return false;
    }
}

// Teacher 1
$t1_id = createUser($pdo, 'Prof. Alan Turing', 'alan@doon.edu', 'turing123', 'faculty');
if ($t1_id) {
    // Add to faculties
    $stmt = $pdo->prepare("INSERT IGNORE INTO faculties (user_id, department_id, employee_code) VALUES (?, 1, 'EMP1001')");
    $stmt->execute([$t1_id]);
}

// Teacher 2
$t2_id = createUser($pdo, 'Dr. Marie Curie', 'marie@doon.edu', 'curie123', 'faculty');
if ($t2_id) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO faculties (user_id, department_id, employee_code) VALUES (?, 2, 'EMP1002')");
    $stmt->execute([$t2_id]);
}

// Student 1
$s1_id = createUser($pdo, 'Alice Wonderland', 'alice@student.doon.edu', 'alice123', 'student');
if ($s1_id) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id, enrollment_no, course, batch_year) VALUES (?, 'ENR2026001', 'B.Tech CS', 2026)");
    $stmt->execute([$s1_id]);
    
    // Add some marks for Alice
    $stmt = $pdo->prepare("INSERT IGNORE INTO marks (student_id, subject_id, exam_type, marks_obtained, max_marks) VALUES ((SELECT id FROM students WHERE user_id = ?), 1, 'midterm', 45, 50)");
    $stmt->execute([$s1_id]);
}

// Student 2
$s2_id = createUser($pdo, 'Bob Builder', 'bob@student.doon.edu', 'bob123', 'student');
if ($s2_id) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id, enrollment_no, course, batch_year) VALUES (?, 'ENR2026002', 'B.Tech CS', 2026)");
    $stmt->execute([$s2_id]);
}

echo "\nDummy users created successfully!\n";
echo "---------------------------------\n";
echo "Faculty 1: alan@doon.edu / turing123\n";
echo "Faculty 2: marie@doon.edu / curie123\n";
echo "Student 1: alice@student.doon.edu / alice123\n";
echo "Student 2: bob@student.doon.edu / bob123\n";
echo "Admin (from schema): admin@doonuniversity.edu / admin123\n";

?>
