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

// Fetch default settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$defaults = [];
while ($row = $stmt->fetch()) {
    $defaults[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    
    $sections = [
        'A' => ['type' => $_POST['sec_a_type'], 'count' => (int)$_POST['sec_a_count'], 'marks' => (int)$_POST['sec_a_marks']],
        'B' => ['type' => $_POST['sec_b_type'], 'count' => (int)$_POST['sec_b_count'], 'marks' => (int)$_POST['sec_b_marks']],
        'C' => ['type' => $_POST['sec_c_type'], 'count' => (int)$_POST['sec_c_count'], 'marks' => (int)$_POST['sec_c_marks']]
    ];
    
    $paper_data = [];
    $total_marks = 0;
    $has_error = false;
    
    foreach ($sections as $sec_name => $req) {
        if ($req['count'] > 0 && $req['marks'] > 0) {
            $stmt = $pdo->prepare("SELECT id, question_text, marks, difficulty, question_type FROM question_pool WHERE subject_id = ? AND question_type = ?");
            $stmt->execute([$subject_id, $req['type']]);
            $pool = $stmt->fetchAll();
            
            if (count($pool) < $req['count']) {
                $error = "Not enough questions for Section {$sec_name}. You requested {$req['count']} '" . ucfirst($req['type']) . "' questions, but only " . count($pool) . " exist in the pool for this subject.";
                $has_error = true;
                break;
            }
            
            shuffle($pool);
            $selected = array_slice($pool, 0, $req['count']);
            
            $paper_data[$sec_name] = [
                'marks_per_q' => $req['marks'],
                'questions' => $selected
            ];
            
            $total_marks += ($req['count'] * $req['marks']);
        }
    }
    
    if (!$has_error && empty($paper_data)) {
        $error = "Please specify at least one section to generate.";
        $has_error = true;
    }
    
    if (!$has_error) {
        // Save paper to DB
        $paper_json = json_encode($paper_data);
        $date = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO exam_papers (subject_id, generated_date, paper_json, total_marks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $date, $paper_json, $total_marks]);
        $paper_id = $pdo->lastInsertId();
        
        $subject_name = $pdo->query("SELECT name FROM subjects WHERE id = " . (int)$subject_id)->fetchColumn();
        $generated_paper = [
            'id' => $paper_id,
            'subject' => $subject_name,
            'date' => $date,
            'total_marks' => $total_marks,
            'sections' => $paper_data
        ];
    }
}
?>

<style>
    /* Specific styles for exam paper print view */
    .paper-preview {
        background: #fff;
        color: #000;
        padding: 3rem 4rem;
        border-radius: var(--radius-md);
        margin-top: 2rem;
        box-shadow: var(--shadow-md);
        font-family: 'Times New Roman', Times, serif;
    }
    .paper-header {
        text-align: center;
        border-bottom: 2px solid #000;
        padding-bottom: 1.5rem;
        margin-bottom: 2rem;
    }
    .paper-header h2 {
        color: #000;
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .paper-header h3 {
        color: #333;
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }
    .section-header {
        font-weight: bold;
        font-size: 1.2rem;
        text-align: center;
        margin: 2.5rem 0 1.5rem 0;
        text-transform: uppercase;
    }
    .question-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        line-height: 1.5;
    }
    @media print {
        body * { visibility: hidden; }
        .paper-preview, .paper-preview * { visibility: visible; }
        .paper-preview { 
            position: absolute; 
            left: 0; top: 0; 
            width: 100%; 
            box-shadow: none; 
            padding: 0;
        }
    }
</style>

<div class="flex-between mb-3">
    <div>
        <h2>Generate Exam Paper</h2>
        <p>Design your paper structure. The system will randomly pick questions from the pool matching your criteria.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="badge badge-danger mb-3" style="display:block; padding:1rem; font-size: 1rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="glass-panel" style="max-width: 900px;">
    <form method="POST" action="">
        <div class="form-group">
            <label>Select Subject</label>
            <select name="subject_id" class="form-control" required style="max-width: 400px;">
                <?php
                $subjects = $pdo->query("SELECT id, name, code FROM subjects")->fetchAll();
                foreach ($subjects as $s) {
                    echo "<option value='{$s['id']}'>{$s['code']} - {$s['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Paper Structure (Sections)</h3>
            
            <!-- Section A -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 1.5rem; background: var(--bg-main); padding: 1rem; border-radius: var(--radius-sm);">
                <div style="font-weight: 600;">Section A</div>
                <div class="form-group" style="margin: 0;">
                    <label>Question Type</label>
                    <select name="sec_a_type" class="form-control" required>
                        <option value="objective" <?= ($defaults['sec_a_type'] ?? 'objective') === 'objective' ? 'selected' : '' ?>>Objective</option>
                        <option value="short" <?= ($defaults['sec_a_type'] ?? '') === 'short' ? 'selected' : '' ?>>Short Answer</option>
                        <option value="long" <?= ($defaults['sec_a_type'] ?? '') === 'long' ? 'selected' : '' ?>>Long Essay</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>No. of Questions</label>
                    <input type="number" name="sec_a_count" class="form-control" value="<?= htmlspecialchars($defaults['sec_a_count'] ?? 10) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_a_marks" class="form-control" value="<?= htmlspecialchars($defaults['sec_a_marks'] ?? 2) ?>" min="0">
                </div>
            </div>

            <!-- Section B -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 1.5rem; background: var(--bg-main); padding: 1rem; border-radius: var(--radius-sm);">
                <div style="font-weight: 600;">Section B</div>
                <div class="form-group" style="margin: 0;">
                    <label>Question Type</label>
                    <select name="sec_b_type" class="form-control" required>
                        <option value="objective" <?= ($defaults['sec_b_type'] ?? '') === 'objective' ? 'selected' : '' ?>>Objective</option>
                        <option value="short" <?= ($defaults['sec_b_type'] ?? 'short') === 'short' ? 'selected' : '' ?>>Short Answer</option>
                        <option value="long" <?= ($defaults['sec_b_type'] ?? '') === 'long' ? 'selected' : '' ?>>Long Essay</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>No. of Questions</label>
                    <input type="number" name="sec_b_count" class="form-control" value="<?= htmlspecialchars($defaults['sec_b_count'] ?? 5) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_b_marks" class="form-control" value="<?= htmlspecialchars($defaults['sec_b_marks'] ?? 5) ?>" min="0">
                </div>
            </div>

            <!-- Section C -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 2rem; background: var(--bg-main); padding: 1rem; border-radius: var(--radius-sm);">
                <div style="font-weight: 600;">Section C</div>
                <div class="form-group" style="margin: 0;">
                    <label>Question Type</label>
                    <select name="sec_c_type" class="form-control" required>
                        <option value="objective" <?= ($defaults['sec_c_type'] ?? '') === 'objective' ? 'selected' : '' ?>>Objective</option>
                        <option value="short" <?= ($defaults['sec_c_type'] ?? '') === 'short' ? 'selected' : '' ?>>Short Answer</option>
                        <option value="long" <?= ($defaults['sec_c_type'] ?? 'long') === 'long' ? 'selected' : '' ?>>Long Essay</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>No. of Questions</label>
                    <input type="number" name="sec_c_count" class="form-control" value="<?= htmlspecialchars($defaults['sec_c_count'] ?? 3) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_c_marks" class="form-control" value="<?= htmlspecialchars($defaults['sec_c_marks'] ?? 10) ?>" min="0">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i data-feather="settings"></i> Generate Random Paper</button>
    </form>
</div>

<?php if ($generated_paper): ?>
<div class="paper-preview">
    <div class="paper-header">
        <h2>DOON UNIVERSITY</h2>
        <h3>Semester End Examination</h3>
        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; font-weight: bold; font-size: 1.1rem;">
            <span>Subject: <?= htmlspecialchars($generated_paper['subject']) ?></span>
            <span>Date: <?= date('d M Y', strtotime($generated_paper['date'])) ?></span>
            <span>Max Marks: <?= $generated_paper['total_marks'] ?></span>
        </div>
    </div>
    
    <div>
        <?php 
        $q_number = 1;
        foreach ($generated_paper['sections'] as $sec_name => $sec_data): 
        ?>
            <div class="section-header">
                SECTION <?= $sec_name ?> <br>
                <span style="font-size: 0.9rem; font-weight: normal;">
                    (Attempt all questions. Each question carries <?= $sec_data['marks_per_q'] ?> marks.)
                </span>
            </div>
            
            <?php foreach ($sec_data['questions'] as $q): ?>
                <div class="question-item">
                    <div style="flex: 1; padding-right: 2rem;">
                        <strong>Q<?= $q_number++ ?>.</strong> <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                    </div>
                    <div style="font-weight: bold;">
                        [<?= $sec_data['marks_per_q'] ?>]
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 4rem; font-weight: bold; font-size: 1.2rem;">
        --- END OF PAPER ---
    </div>
    
    <div style="text-align: right; margin-top: 2rem;">
        <button class="btn btn-primary" onclick="window.print()" style="display: inline-flex;"><i data-feather="printer"></i> Print Paper</button>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
