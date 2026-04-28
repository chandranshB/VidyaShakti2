<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'faculty' && $_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $question_text = $_POST['question_text'];
    $marks = $_POST['marks'];
    $difficulty = $_POST['difficulty'];
    
    // Get faculty ID
    $stmt = $pdo->prepare("SELECT id FROM faculties WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $faculty_id = $stmt->fetchColumn();

    if ($faculty_id) {
        $stmt = $pdo->prepare("INSERT INTO question_pool (subject_id, question_text, marks, difficulty, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$subject_id, $question_text, $marks, $difficulty, $faculty_id])) {
            $message = "<div class='badge badge-success mb-3' style='display:block; padding:1rem;'>Question successfully added to pool!</div>";
        } else {
            $message = "<div class='badge badge-danger mb-3' style='display:block; padding:1rem;'>Failed to add question.</div>";
        }
    }
}
?>

<div class="flex-between mb-3">
    <h2>Manage Question Pool</h2>
    <a href="generate_exam.php" class="btn btn-secondary"><i data-feather="file-text"></i> Generate Paper</a>
</div>

<?= $message ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Add Question Form -->
    <div class="glass-panel">
        <h3><i data-feather="plus-circle" style="vertical-align: middle;"></i> Add New Question</h3>
        <form method="POST" action="" style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Subject</label>
                <select name="subject_id" class="form-control" required style="background: rgba(0,0,0,0.3);">
                    <?php
                    $subjects = $pdo->query("SELECT id, name, code FROM subjects")->fetchAll();
                    foreach ($subjects as $s) {
                        echo "<option value='{$s['id']}'>{$s['code']} - {$s['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Question Text</label>
                <textarea name="question_text" class="form-control" rows="4" required></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Marks</label>
                    <input type="number" name="marks" class="form-control" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty" class="form-control" required style="background: rgba(0,0,0,0.3);">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Add Question</button>
        </form>
    </div>

    <!-- Question List -->
    <div class="glass-panel">
        <h3>Question Bank</h3>
        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
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
                    $stmt = $pdo->query("
                        SELECT q.question_text, q.marks, q.difficulty, s.code 
                        FROM question_pool q 
                        JOIN subjects s ON q.subject_id = s.id 
                        ORDER BY q.id DESC
                    ");
                    $questions = $stmt->fetchAll();
                    foreach ($questions as $q):
                    ?>
                    <tr>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($q['code']) ?></span></td>
                        <td><?= htmlspecialchars($q['question_text']) ?></td>
                        <td><?= $q['marks'] ?></td>
                        <td>
                            <?php 
                            $color = $q['difficulty'] === 'hard' ? 'danger' : ($q['difficulty'] === 'medium' ? 'primary' : 'success');
                            echo "<span class='badge badge-{$color}'>" . ucfirst($q['difficulty']) . "</span>";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
