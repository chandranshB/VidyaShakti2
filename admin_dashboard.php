<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SESSION['user_role'] !== 'admin') {
    echo "<div class='glass-panel'>Access Denied.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch some stats
$stats = [
    'students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'faculty' => $pdo->query("SELECT COUNT(*) FROM faculties")->fetchColumn(),
    'departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'questions' => $pdo->query("SELECT COUNT(*) FROM question_pool")->fetchColumn()
];
?>

<div class="flex-between mb-3">
    <div>
        <h2>Admin Overview (God Mode)</h2>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here's what's happening today.</p>
    </div>
    <a href="manage_users.php" class="btn btn-primary"><i data-feather="user-plus"></i> Add User</a>
</div>

<div class="dashboard-grid">
    <div class="glass-panel stat-card">
        <div class="stat-icon"><i data-feather="users"></i></div>
        <div class="stat-info">
            <h4>Total Students</h4>
            <h2><?= $stats['students'] ?></h2>
        </div>
    </div>
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--secondary); background: rgba(236, 72, 153, 0.1);"><i data-feather="user-check"></i></div>
        <div class="stat-info">
            <h4>Total Faculty</h4>
            <h2><?= $stats['faculty'] ?></h2>
        </div>
    </div>
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--accent); background: rgba(6, 182, 212, 0.1);"><i data-feather="layers"></i></div>
        <div class="stat-info">
            <h4>Departments</h4>
            <h2><?= $stats['departments'] ?></h2>
        </div>
    </div>
    <div class="glass-panel stat-card">
        <div class="stat-icon" style="color: var(--success); background: rgba(16, 185, 129, 0.1);"><i data-feather="database"></i></div>
        <div class="stat-info">
            <h4>Question Bank</h4>
            <h2><?= $stats['questions'] ?></h2>
        </div>
    </div>
</div>

<div class="glass-panel">
    <h3>Recent System Activity</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_users = $pdo->query("SELECT name, role, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
                foreach ($recent_users as $user):
                ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($user['role']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
