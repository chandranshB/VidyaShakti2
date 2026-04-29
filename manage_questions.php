<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'faculty' && $_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$toast_message = '';
$toast_type = '';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM question_pool WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $toast_message = "Question deleted successfully.";
        $toast_type = "success";
    } else {
        $toast_message = "Failed to delete question.";
        $toast_type = "danger";
    }
}

// Handle Add Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_id = $_POST['subject_id'];
    $question_text = $_POST['question_text'];
    $marks = $_POST['marks'];
    $difficulty = $_POST['difficulty'];
    $question_type = $_POST['question_type'];
    
    $stmt = $pdo->prepare("INSERT INTO question_pool (subject_id, question_text, marks, difficulty, question_type) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$subject_id, $question_text, $marks, $difficulty, $question_type])) {
        $toast_message = "Question added to pool!";
        $toast_type = "success";
    } else {
        $toast_message = "Error adding question.";
        $toast_type = "danger";
    }
}
?>

<style>
/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-sm);
    background: var(--bg-surface);
    color: var(--text-primary);
    box-shadow: var(--shadow-lg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 4px solid var(--primary);
}
.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}
.toast-notification.success { border-left-color: var(--success); }
.toast-notification.danger { border-left-color: var(--danger); }

/* Quick Search Input */
.search-wrapper {
    position: relative;
    width: 100%;
}
.search-wrapper svg {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    width: 18px;
    height: 18px;
}
.search-input {
    padding-left: 2.5rem !important;
    background: var(--bg-surface);
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 0.5rem;
}
.icon-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.icon-btn:hover {
    color: var(--danger);
    background: rgba(255, 59, 48, 0.1);
}
</style>

<div class="flex-between mb-3">
    <div>
        <h2>Manage Question Pool</h2>
        <p>Add new questions and manage the existing repository.</p>
    </div>
    <a href="generate_exam.php" class="btn btn-secondary"><i data-feather="file-text"></i> Generate Paper</a>
</div>

<div class="split-layout">
    <!-- Add Question Form -->
    <div class="surface-card">
        <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.2rem;">
            <i data-feather="plus-circle" style="color: var(--primary);"></i> Add Question
        </h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Subject</label>
                <select name="subject_id" class="form-control" required>
                    <?php
                    $subjects = $pdo->query("SELECT id, name, code FROM subjects")->fetchAll();
                    foreach ($subjects as $s) {
                        echo "<option value='{$s['id']}'>{$s['code']} - {$s['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Question Type</label>
                <select name="question_type" class="form-control" required>
                    <option value="objective">Objective (Multiple Choice / 1-word)</option>
                    <option value="short" selected>Short Answer</option>
                    <option value="long">Long Essay / Detailed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Question Text</label>
                <textarea name="question_text" class="form-control" rows="4" placeholder="Enter the full question description..." required></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Default Marks</label>
                    <input type="number" name="marks" class="form-control" value="2" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty" class="form-control" required>
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i data-feather="save"></i> Save Question
            </button>
        </form>
    </div>

    <!-- Question List (Card View) -->
    <div style="display: flex; flex-direction: column; height: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
            <h3 style="margin: 0; font-size: 1.2rem; flex-shrink: 0;">Question Bank</h3>
            
            <!-- Quick Search -->
            <div class="search-wrapper" style="max-width: 300px; flex: 1;">
                <i data-feather="search"></i>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by text or subject...">
            </div>
        </div>
        
        <div class="question-list" id="questionList" style="flex: 1;">
            <?php
            $stmt = $pdo->query("
                SELECT q.id, q.question_text, q.marks, q.difficulty, q.question_type, s.code, s.name as subject_name 
                FROM question_pool q 
                JOIN subjects s ON q.subject_id = s.id 
                ORDER BY q.id DESC
            ");
            $questions = $stmt->fetchAll();
            
            if (count($questions) > 0):
                foreach ($questions as $q):
            ?>
            <div class="question-card" data-search="<?= strtolower(htmlspecialchars($q['question_text'] . ' ' . $q['code'] . ' ' . $q['subject_name'] . ' ' . $q['question_type'])) ?>">
                <div class="question-header">
                    <div class="question-badges">
                        <span class="badge badge-secondary" style="font-weight: 600; letter-spacing: 0.05em;"><?= htmlspecialchars($q['code']) ?></span>
                        <span class="badge badge-primary"><?= ucfirst(htmlspecialchars($q['question_type'])) ?></span>
                        <?php 
                        $color = $q['difficulty'] === 'hard' ? 'danger' : ($q['difficulty'] === 'medium' ? 'primary' : 'success');
                        echo "<span class='badge badge-{$color}'>" . ucfirst(htmlspecialchars($q['difficulty'])) . "</span>";
                        ?>
                    </div>
                    
                    <div class="card-actions">
                        <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this question? This action cannot be undone.');">
                            <input type="hidden" name="delete_id" value="<?= $q['id'] ?>">
                            <button type="submit" class="icon-btn" title="Delete Question">
                                <i data-feather="trash-2" style="width: 18px; height: 18px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="question-body">
                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                </div>
                
                <div class="question-footer">
                    <div>
                        <i data-feather="award" style="width: 16px; height: 16px;"></i>
                        <strong style="color: var(--text-primary);"><?= $q['marks'] ?> Marks</strong>
                    </div>
                    <div>
                        <i data-feather="book-open" style="width: 16px; height: 16px;"></i>
                        <?= htmlspecialchars($q['subject_name']) ?>
                    </div>
                </div>
            </div>
            <?php 
                endforeach; 
            else: 
            ?>
                <div class="surface-card text-center" style="padding: 3rem 2rem;">
                    <i data-feather="inbox" style="width: 48px; height: 48px; color: var(--border-color); margin-bottom: 1rem;"></i>
                    <p>The question pool is currently empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($toast_message): ?>
<div id="toast" class="toast-notification <?= $toast_type ?>">
    <?php if ($toast_type === 'success'): ?>
        <i data-feather="check-circle" style="color: var(--success);"></i>
    <?php else: ?>
        <i data-feather="alert-circle" style="color: var(--danger);"></i>
    <?php endif; ?>
    <span><?= htmlspecialchars($toast_message) ?></span>
</div>
<?php endif; ?>

<script>
    // Search functionality
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const questionCards = document.querySelectorAll('.question-card');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                questionCards.forEach(card => {
                    const searchableText = card.getAttribute('data-search');
                    if (searchableText.includes(term)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // Toast functionality
        const toast = document.getElementById('toast');
        if (toast) {
            // Slight delay before showing so animation triggers
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            // Auto hide after 4 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
