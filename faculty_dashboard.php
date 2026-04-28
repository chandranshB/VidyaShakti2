<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'faculty' && $_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Get faculty details if it's a faculty
$faculty_dept = "Admin Mode";
if ($_SESSION['user_role'] === 'faculty') {
    $stmt = $pdo->prepare("SELECT d.name FROM faculties f JOIN departments d ON f.department_id = d.id WHERE f.user_id = ?");
    $stmt->execute([$user_id]);
    $faculty_dept = $stmt->fetchColumn() ?: "Unknown Dept";
}

// Stats
$my_questions = $pdo->prepare("SELECT COUNT(*) FROM question_pool WHERE created_by = (SELECT id FROM faculties WHERE user_id = ?)");
$my_questions->execute([$user_id]);
$q_count = $my_questions->fetchColumn();

// If admin, show total questions instead
if ($_SESSION['user_role'] === 'admin') {
    $q_count = $pdo->query("SELECT COUNT(*) FROM question_pool")->fetchColumn();
}
?>

<div class="flex-between mb-3">
    <div>
        <h2>Faculty Portal</h2>
        <p>Department: <?= htmlspecialchars($faculty_dept) ?></p>
    </div>
    <a href="manage_questions.php" class="btn btn-primary"><i data-feather="plus"></i> Add Question</a>
</div>

<div class="dashboard-grid">
    <div class="glass-panel stat-card">
        <div class="stat-icon"><i data-feather="database"></i></div>
        <div class="stat-info">
            <h4>Questions Contributed</h4>
            <h2><?= $q_count ?></h2>
        </div>
    </div>
    <div class="glass-panel stat-card" onclick="window.location='generate_exam.php'" style="cursor: pointer;">
        <div class="stat-icon" style="color: var(--secondary); background: rgba(236, 72, 153, 0.1);"><i data-feather="file-text"></i></div>
        <div class="stat-info">
            <h4>Exam Papers</h4>
            <h2>Generate <i data-feather="arrow-right" style="width: 16px;"></i></h2>
        </div>
    </div>
</div>

<div class="glass-panel">
    <h3>My Recent Questions</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Question</th>
                    <th>Marks</th>
                    <th>Difficulty</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($_SESSION['user_role'] === 'faculty') {
                    $stmt = $pdo->prepare("
                        SELECT q.question_text, q.marks, q.difficulty, s.name as subject_name 
                        FROM question_pool q 
                        JOIN subjects s ON q.subject_id = s.id 
                        WHERE q.created_by = (SELECT id FROM faculties WHERE user_id = ?)
                        ORDER BY q.id DESC LIMIT 5
                    ");
                    $stmt->execute([$user_id]);
                } else {
                    $stmt = $pdo->query("
                        SELECT q.question_text, q.marks, q.difficulty, s.name as subject_name 
                        FROM question_pool q 
                        JOIN subjects s ON q.subject_id = s.id 
                        ORDER BY q.id DESC LIMIT 5
                    ");
                }
                
                $questions = $stmt->fetchAll();
                if (count($questions) > 0):
                    foreach ($questions as $q):
                ?>
                <tr>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($q['subject_name']) ?></span></td>
                    <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($q['question_text']) ?>
                    </td>
                    <td><?= $q['marks'] ?></td>
                    <td>
                        <?php 
                        $color = $q['difficulty'] === 'hard' ? 'danger' : ($q['difficulty'] === 'medium' ? 'primary' : 'success');
                        echo "<span class='badge badge-{$color}'>" . ucfirst($q['difficulty']) . "</span>";
                        ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" class="text-center text-muted">No questions found. Add some to the pool!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
