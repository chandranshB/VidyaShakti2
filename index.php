<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // For simplicity in testing, we use plaintext matching. 
        // In production, use password_verify() with hashed passwords.
        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'faculty':
                    header("Location: faculty_dashboard.php");
                    break;
                case 'student':
                    header("Location: student_dashboard.php");
                    break;
                default:
                    $error = "Invalid role assigned.";
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Doon University ERP</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        body { 
            justify-content: center; 
            align-items: center; 
            background: #f5f5f7;
        }
        .hero-bg {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top right, rgba(0, 113, 227, 0.08), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(94, 92, 230, 0.05), transparent 40%);
            z-index: 0;
        }
    </style>
</head>
<body>
<div class="hero-bg"></div>

<div class="auth-wrapper" style="background: transparent;">
    <div class="surface-card auth-box">
        <div class="text-center mb-3">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(0, 113, 227, 0.1); border-radius: 18px; color: var(--primary); margin-bottom: 1rem;">
                <i data-feather="hexagon" style="width: 32px; height: 32px;"></i>
            </div>
            <h2 style="font-size: 1.6rem;">Doon University</h2>
            <p style="font-size: 0.9rem;">Enterprise Resource Planning</p>
        </div>

        <?php if ($error): ?>
            <div class="badge badge-danger mb-2" style="display: block; text-align: center; padding: 0.75rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="user@doonuniversity.edu" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Login to Portal <i data-feather="arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-3" style="font-size: 0.85rem; color: var(--text-muted);">
            <p>Demo Accounts:</p>
            <p>Admin: admin@doonuniversity.edu / admin123</p>
            <p>Faculty: sharma@doonuniversity.edu / faculty123</p>
            <p>Student: john@student.doonuniversity.edu / student123</p>
        </div>
    </div>
</div>

<script>feather.replace();</script>
</body>
</html>
