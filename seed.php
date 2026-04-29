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

    // Add dummy attendance for Alice
    $alice_id = $pdo->query("SELECT id FROM students WHERE enrollment_no = 'ENR2026001'")->fetchColumn();
    if ($alice_id) {
        $subjects = $pdo->query("SELECT id FROM subjects")->fetchAll(PDO::FETCH_COLUMN);
        $statuses = ['present', 'present', 'present', 'absent', 'late', 'present'];
        
        foreach ($subjects as $subj_id) {
            for ($i = 0; $i < 10; $i++) {
                $status = $statuses[array_rand($statuses)];
                $date = date('Y-m-d', strtotime("-$i days"));
                $stmt = $pdo->prepare("INSERT IGNORE INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$alice_id, $subj_id, $date, $status]);
            }
        }
    }
}

// Student 3: Chandransh Binjola (Specific Details)
$s3_id = createUser($pdo, 'CHANDRANSH BINJOLA', 'chandranshbinjola@outlook.com', 'chandransh123', 'student');
if ($s3_id) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (
        user_id, enrollment_no, course, batch_year, 
        father_name, mother_name, abc_id, is_ph, gender, 
        cast_category, dob, mobile, semester, department_name
    ) VALUES (?, '24CS-13', 'Bachelor of Science (Hons/Hons with Research) Computer Science', 2024, 
        'Anil Kumar Binjola', 'Shanti Amoli Binjola', '611082282845', 'NO', 'Male', 
        'UR', '2006-08-13', '8630071845', 4, 'Computer Science')");
    $stmt->execute([$s3_id]);
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
