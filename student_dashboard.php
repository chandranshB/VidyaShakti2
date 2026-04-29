<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'student') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student details
$stmt = $pdo->prepare("SELECT id, enrollment_no, course, batch_year FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='glass-panel'>Student profile not fully setup. Please contact admin.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id = $student['id'];

// Calculate real attendance percentage
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$attendance_stats = $stmt->fetch();

$total_classes = $attendance_stats['total'];
$present_weighted = $attendance_stats['present'] + ($attendance_stats['late'] * 0.5);
$attendance_percentage = ($total_classes > 0) ? round(($present_weighted / $total_classes) * 100, 1) : 0;
?>

<div class="flex-between mb-3">
    <div>
        <h2>Student Dashboard</h2>
        <p>Course: <?= htmlspecialchars($student['course']) ?> | Batch: <?= htmlspecialchars($student['batch_year']) ?> | Enrollment: <?= htmlspecialchars($student['enrollment_no']) ?></p>
    </div>
    <a href="admit_card.php" class="btn btn-primary" target="_blank"><i data-feather="printer"></i> Print Admit Card</a>
</div>

<div class="dashboard-grid">
    <div class="glass-panel stat-card" onclick="location.href='attendance.php'" style="cursor: pointer;">
        <div class="stat-icon" style="color: var(--success); background: rgba(16, 185, 129, 0.1);"><i data-feather="check-circle"></i></div>
        <div class="stat-info">
            <h4>Attendance</h4>
            <h2><?= $attendance_percentage ?>%</h2>
        </div>
    </div>
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--accent); background: rgba(6, 182, 212, 0.1);"><i data-feather="award"></i></div>
        <div class="stat-info">
            <h4>Current CGPA</h4>
            <h2>8.4</h2> <!-- Hardcoded for visual demo -->
        </div>
    </div>
</div>

<div class="glass-panel">
    <h3>Recent Marks</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Marks Obtained</th>
                    <th>Max Marks</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch marks
                $stmt = $pdo->prepare("
                    SELECT m.exam_type, m.marks_obtained, m.max_marks, s.name as subject_name 
                    FROM marks m 
                    JOIN subjects s ON m.subject_id = s.id 
                    WHERE m.student_id = (SELECT id FROM students WHERE user_id = ?)
                    ORDER BY m.id DESC LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $marks = $stmt->fetchAll();
                
                if (count($marks) > 0):
                    foreach ($marks as $m):
                        $percentage = ($m['marks_obtained'] / $m['max_marks']) * 100;
                        $grade = 'F';
                        $color = 'danger';
                        if ($percentage >= 90) { $grade = 'A+'; $color = 'success'; }
                        elseif ($percentage >= 80) { $grade = 'A'; $color = 'success'; }
                        elseif ($percentage >= 70) { $grade = 'B'; $color = 'primary'; }
                        elseif ($percentage >= 60) { $grade = 'C'; $color = 'primary'; }
                ?>
                <tr>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($m['subject_name']) ?></span></td>
                    <td><?= ucfirst(htmlspecialchars($m['exam_type'])) ?></td>
                    <td><strong><?= $m['marks_obtained'] ?></strong></td>
                    <td><?= $m['max_marks'] ?></td>
                    <td><span class="badge badge-<?= $color ?>"><?= $grade ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No marks recorded yet. Keep studying!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
