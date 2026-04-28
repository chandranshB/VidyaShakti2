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
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $password, $role]);
        $message = "<div class='badge badge-success mb-3' style='display:block; padding:1rem;'>User created successfully!</div>";
    } catch(PDOException $e) {
        $message = "<div class='badge badge-danger mb-3' style='display:block; padding:1rem;'>Error creating user. Email might already exist.</div>";
    }
}
?>

<div class="flex-between mb-3">
    <h2>Manage Users</h2>
    <a href="admin_dashboard.php" class="btn btn-secondary"><i data-feather="arrow-left"></i> Back to Dashboard</a>
</div>

<?= $message ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Add User Form -->
    <div class="glass-panel">
        <h3><i data-feather="user-plus" style="vertical-align: middle;"></i> Add New User</h3>
        <form method="POST" action="" style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required style="background: rgba(0,0,0,0.3);">
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="department">Department</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create User</button>
        </form>
    </div>

    <!-- User List -->
    <div class="glass-panel">
        <h3>All Users</h3>
        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
                    $users = $stmt->fetchAll();
                    foreach ($users as $u):
                    ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php 
                            $roleColors = [
                                'admin' => 'danger',
                                'faculty' => 'secondary',
                                'student' => 'primary',
                                'department' => 'success'
                            ];
                            $color = $roleColors[$u['role']] ?? 'primary';
                            echo "<span class='badge badge-{$color}'>" . ucfirst($u['role']) . "</span>";
                            ?>
                        </td>
                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
