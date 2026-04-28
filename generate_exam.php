<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'faculty' && $_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$generated_paper = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $target_marks = (int)$_POST['target_marks'];
    
    // Fetch all questions for this subject
    $stmt = $pdo->prepare("SELECT id, question_text, marks, difficulty FROM question_pool WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $pool = $stmt->fetchAll();
    
    // Simple greedy random selection to reach exact or close to target marks
    shuffle($pool);
    $selected_questions = [];
    $current_marks = 0;
    
    foreach ($pool as $q) {
        if ($current_marks + $q['marks'] <= $target_marks) {
            $selected_questions[] = $q;
            $current_marks += $q['marks'];
        }
        if ($current_marks === $target_marks) break;
    }
    
    if (count($selected_questions) === 0) {
        $error = "Not enough questions in the pool for this subject to generate a paper.";
    } else {
        // Save paper to DB
        $paper_json = json_encode($selected_questions);
        $date = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO exam_papers (subject_id, generated_date, paper_json, total_marks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $date, $paper_json, $current_marks]);
        $paper_id = $pdo->lastInsertId();
        
        $subject_name = $pdo->query("SELECT name FROM subjects WHERE id = $subject_id")->fetchColumn();
        $generated_paper = [
            'id' => $paper_id,
            'subject' => $subject_name,
            'date' => $date,
            'total_marks' => $current_marks,
            'questions' => $selected_questions
        ];
    }
}
?>

<style>
    /* Specific styles for exam paper print view */
    .paper-preview {
        background: #fff;
        color: #000;
        padding: 3rem;
        border-radius: 8px;
        margin-top: 2rem;
    }
    .paper-header {
        text-align: center;
        border-bottom: 2px solid #000;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }
    .paper-header h2 {
        color: #000;
        margin: 0 0 0.5rem 0;
        -webkit-text-fill-color: initial;
    }
    .question-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
    }
    @media print {
        body * { visibility: hidden; }
        .paper-preview, .paper-preview * { visibility: visible; }
        .paper-preview { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; }
    }
</style>

<div class="flex-between mb-3">
    <h2>Generate Exam Paper</h2>
    <p>Randomly generates a paper from the Question Pool</p>
</div>

<?php if ($error): ?>
    <div class="badge badge-danger mb-3" style="display:block; padding:1rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="glass-panel" style="max-width: 600px;">
    <form method="POST" action="">
        <div class="form-group">
            <label>Select Subject</label>
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
            <label>Target Total Marks</label>
            <input type="number" name="target_marks" class="form-control" value="50" min="10" max="100" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;"><i data-feather="settings"></i> Generate Random Paper</button>
    </form>
</div>

<?php if ($generated_paper): ?>
<div class="paper-preview">
    <div class="paper-header">
        <h2>DOON UNIVERSITY</h2>
        <h3>Semester End Examination</h3>
        <div style="display: flex; justify-content: space-between; margin-top: 1rem; font-weight: bold;">
            <span>Subject: <?= htmlspecialchars($generated_paper['subject']) ?></span>
            <span>Date: <?= date('d M Y', strtotime($generated_paper['date'])) ?></span>
            <span>Total Marks: <?= $generated_paper['total_marks'] ?></span>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <?php foreach ($generated_paper['questions'] as $index => $q): ?>
            <div class="question-item">
                <div style="flex: 1;">
                    <strong>Q<?= $index + 1 ?>.</strong> <?= htmlspecialchars($q['question_text']) ?>
                </div>
                <div style="margin-left: 2rem; font-weight: bold;">
                    [<?= $q['marks'] ?>]
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 4rem; font-weight: bold;">
        --- END OF PAPER ---
    </div>
    
    <div style="text-align: right; margin-top: 2rem;">
        <button class="btn btn-primary" onclick="window.print()" style="display: inline-flex;"><i data-feather="printer"></i> Print Paper</button>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
