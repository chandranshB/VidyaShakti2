<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'student') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='glass-panel'>Student profile not fully setup. Please contact admin.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id = $student['id'];
$selected_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// Fetch All Subjects for Filter
$stmt = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name ASC");
$all_subjects = $stmt->fetchAll();

// Fetch Summary Stats (Grouped by Subject)
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.name as subject_name, 
        s.code as subject_code,
        COUNT(a.id) as total_classes,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM subjects s
    LEFT JOIN attendance a ON s.id = a.subject_id AND a.student_id = ?
    GROUP BY s.id
");
$stmt->execute([$student_id]);
$subject_attendance = $stmt->fetchAll();

// Calculate Overall Attendance
$total_classes_all = 0;
$total_present_all = 0;
foreach ($subject_attendance as $row) {
    $total_classes_all += $row['total_classes'];
    $total_present_all += $row['present_count'] + ($row['late_count'] * 0.5);
}
$overall_percentage = ($total_classes_all > 0) ? round(($total_present_all / $total_classes_all) * 100, 1) : 0;

// Fetch Attendance History
$query = "
    SELECT a.date, a.status, s.name as subject_name, s.code as subject_code
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.student_id = ?
";
$params = [$student_id];

if ($selected_subject > 0) {
    $query .= " AND a.subject_id = ?";
    $params[] = $selected_subject;
}

$query .= " ORDER BY a.date DESC, s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Group history by date
$grouped_history = [];
foreach ($history as $row) {
    $grouped_history[$row['date']][] = $row;
}
?>

<style>
    .attendance-card {
        border-left: 4px solid var(--border-color);
        padding-left: 1.5rem;
        position: relative;
        margin-bottom: 1.5rem;
    }
    .attendance-card::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--bg-main);
        border: 2px solid var(--border-color);
    }
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }
    .dot-present { background-color: var(--success); box-shadow: 0 0 8px rgba(30, 142, 62, 0.4); }
    .dot-absent { background-color: var(--danger); box-shadow: 0 0 8px rgba(217, 48, 37, 0.4); }
    .dot-late { background-color: var(--accent); box-shadow: 0 0 8px rgba(94, 92, 230, 0.4); }
    
    .date-header {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .date-header::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border-color);
    }
    
    .subject-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: var(--bg-main);
        border-radius: var(--radius-sm);
        margin-bottom: 0.5rem;
        transition: transform 0.2s;
    }
    .subject-pill:hover {
        transform: translateX(5px);
        background: var(--input-bg-hover);
    }
</style>

<div class="flex-between mb-3">
    <div>
        <h2>My Attendance</h2>
        <p>Comprehensive record of your presence in academic sessions.</p>
    </div>
    <div class="flex-between" style="gap: 1rem;">
        <form action="" method="GET" id="filterForm">
            <select name="subject_id" class="form-control" onchange="document.getElementById('filterForm').submit()" style="width: 200px;">
                <option value="0">All Subjects</option>
                <?php foreach ($all_subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="dashboard-grid">
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--primary); background: var(--primary-light);"><i data-feather="percent"></i></div>
        <div class="stat-info">
            <h4>Overall Progress</h4>
            <h2><?= $overall_percentage ?>%</h2>
        </div>
    </div>
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--success); background: rgba(16, 185, 129, 0.1);"><i data-feather="calendar"></i></div>
        <div class="stat-info">
            <h4>Total Sessions</h4>
            <h2><?= $total_classes_all ?></h2>
        </div>
    </div>
</div>

<div class="glass-panel mb-4">
    <h3>Subject-wise Summary</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Sessions</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th width="30%">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subject_attendance as $row): 
                    $subj_total = $row['total_classes'];
                    $subj_present = $row['present_count'] + ($row['late_count'] * 0.5);
                    $subj_percent = ($subj_total > 0) ? round(($subj_present / $subj_total) * 100, 1) : 0;
                    
                    $color = 'danger';
                    if ($subj_percent >= 75) $color = 'success';
                    elseif ($subj_percent >= 60) $color = 'primary';
                    
                    $is_selected = ($selected_subject == $row['id']);
                ?>
                <tr style="<?= $is_selected ? 'background: var(--primary-light);' : '' ?>">
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($row['subject_name']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($row['subject_code']) ?></div>
                    </td>
                    <td><?= $row['total_classes'] ?></td>
                    <td><span class="badge badge-success"><?= $row['present_count'] ?></span></td>
                    <td><span class="badge badge-danger"><?= $row['absent_count'] ?></span></td>
                    <td><span class="badge badge-warning"><?= $row['late_count'] ?></span></td>
                    <td>
                        <div class="flex-between" style="gap: 1rem;">
                            <div style="flex: 1; height: 6px; background: var(--input-bg); border-radius: 3px; overflow: hidden;">
                                <div style="width: <?= $subj_percent ?>%; height: 100%; background: var(--<?= $color ?>);"></div>
                            </div>
                            <span style="font-weight: 700; font-size: 0.9rem;"><?= $subj_percent ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel">
    <h3><?= $selected_subject > 0 ? 'Subject Details' : 'Timeline View' ?></h3>
    <div style="margin-top: 2rem;">
        <?php if (count($grouped_history) > 0): ?>
            <?php foreach ($grouped_history as $date => $records): ?>
                <div class="date-header">
                    <i data-feather="calendar" style="width: 14px; height: 14px;"></i>
                    <?= date('l, d F Y', strtotime($date)) ?>
                </div>
                <div class="attendance-card">
                    <?php foreach ($records as $r): 
                        $status_class = 'dot-' . $r['status'];
                        $status_label = ucfirst($r['status']);
                        $status_color = 'var(--text-secondary)';
                        if ($r['status'] === 'present') $status_color = 'var(--success)';
                        elseif ($r['status'] === 'absent') $status_color = 'var(--danger)';
                        elseif ($r['status'] === 'late') $status_color = 'var(--accent)';
                    ?>
                        <div class="subject-pill">
                            <div style="display: flex; align-items: center;">
                                <span class="status-dot <?= $status_class ?>"></span>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.95rem;"><?= htmlspecialchars($r['subject_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($r['subject_code']) ?></div>
                                </div>
                            </div>
                            <div style="font-weight: 700; font-size: 0.85rem; color: <?= $status_color ?>;">
                                <?= $status_label ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted" style="padding: 3rem;">
                <i data-feather="info" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"></i>
                <p>No attendance records found for the selected criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

