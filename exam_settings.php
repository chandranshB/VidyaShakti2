<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [
        'sec_a_count' => $_POST['sec_a_count'],
        'sec_a_marks' => $_POST['sec_a_marks'],
        'sec_b_count' => $_POST['sec_b_count'],
        'sec_b_marks' => $_POST['sec_b_marks'],
        'sec_c_count' => $_POST['sec_c_count'],
        'sec_c_marks' => $_POST['sec_c_marks']
    ];

    try {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($updates as $key => $val) {
            $stmt->execute([$val, $key]);
        }
        $message = "<div class='badge badge-success mb-3' style='display:block; padding:1rem; font-size: 1rem;'>Settings updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='badge badge-danger mb-3' style='display:block; padding:1rem; font-size: 1rem;'>Error updating settings.</div>";
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="flex-between mb-3">
    <div>
        <h2>Global Exam Preferences</h2>
        <p>Set the default structure and marks distribution for generated exams across the university.</p>
    </div>
</div>

<?= $message ?>

<div class="surface-card" style="max-width: 800px;">
    <form method="POST" action="">
        
        <div style="padding-top: 1rem;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Default Paper Structure</h3>
            
            <!-- Section A -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 1.5rem; background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div style="font-weight: 600; color: var(--primary);">Section A</div>
                <div class="form-group" style="margin: 0;">
                    <label>Default Questions</label>
                    <input type="number" name="sec_a_count" class="form-control" value="<?= htmlspecialchars($settings['sec_a_count'] ?? 10) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_a_marks" class="form-control" value="<?= htmlspecialchars($settings['sec_a_marks'] ?? 2) ?>" min="0">
                </div>
            </div>

            <!-- Section B -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 1.5rem; background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div style="font-weight: 600; color: var(--primary);">Section B</div>
                <div class="form-group" style="margin: 0;">
                    <label>Default Questions</label>
                    <input type="number" name="sec_b_count" class="form-control" value="<?= htmlspecialchars($settings['sec_b_count'] ?? 5) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_b_marks" class="form-control" value="<?= htmlspecialchars($settings['sec_b_marks'] ?? 5) ?>" min="0">
                </div>
            </div>

            <!-- Section C -->
            <div style="display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 1.5rem; align-items: end; margin-bottom: 2rem; background: var(--bg-main); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div style="font-weight: 600; color: var(--primary);">Section C</div>
                <div class="form-group" style="margin: 0;">
                    <label>Default Questions</label>
                    <input type="number" name="sec_c_count" class="form-control" value="<?= htmlspecialchars($settings['sec_c_count'] ?? 3) ?>" min="0">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Marks per Question</label>
                    <input type="number" name="sec_c_marks" class="form-control" value="<?= htmlspecialchars($settings['sec_c_marks'] ?? 10) ?>" min="0">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Save Global Preferences</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
