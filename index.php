<?php
session_start();
require_once 'includes/db.php';

// Auto-login logic
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];
    $stmt = $pdo->prepare("SELECT id, name, role, profile_image FROM users WHERE remember_token = :token");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['profile_image'] = $user['profile_image'];

        // Redirect based on role
        switch ($user['role']) {
            case 'admin': header("Location: admin_dashboard.php"); exit;
            case 'faculty': header("Location: faculty_dashboard.php"); exit;
            case 'student': header("Location: student_dashboard.php"); exit;
        }
    }
}

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin': header("Location: admin_dashboard.php"); exit;
        case 'faculty': header("Location: faculty_dashboard.php"); exit;
        case 'student': header("Location: student_dashboard.php"); exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($login_id && $password) {
        $stmt = $pdo->prepare("SELECT id, name, role, password, profile_image FROM users WHERE phone = :login_id");
        $stmt->execute(['login_id' => $login_id]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];

            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $update_stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $update_stmt->execute(['token' => $token, 'id' => $user['id']]);
                setcookie('remember_user', $token, time() + (86400 * 30), "/", "", false, true);
            }

            switch ($user['role']) {
                case 'admin': header("Location: admin_dashboard.php"); break;
                case 'faculty': header("Location: faculty_dashboard.php"); break;
                case 'student': header("Location: student_dashboard.php"); break;
                default: $error = "Invalid role assigned.";
            }
            exit;
        } else {
            $error = "Invalid credentials. Please try again.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | Doon University ERP</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        (function() {
            const getTheme = () => {
                const saved = localStorage.getItem('theme');
                if (saved) return saved;
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };
            document.documentElement.setAttribute('data-theme', getTheme());
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-main);
            color: var(--text-primary);
            transition: background-color 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 1.5rem;
            margin: 0;
            overflow-x: hidden;
        }

        /* Ambient background subtle pulses */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 50% 50%, var(--primary), transparent 60%);
            opacity: 0.03;
            z-index: -1;
            animation: pulse 10s infinite alternate;
        }

        @keyframes pulse {
            from { transform: scale(1); opacity: 0.02; }
            to { transform: scale(1.2); opacity: 0.05; }
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 3rem 2.5rem;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.03);
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        [data-theme="dark"] .login-card {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.05);
        }

        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s ease;
        }

        /* Theme responsive logo coloring */
        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: filter 0.5s ease;
        }

        /* On light theme, logo remains original (darkish) */
        [data-theme="dark"] .brand-logo img {
            filter: invert(1) brightness(2);
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .form-group {
            text-align: left;
            margin-bottom: 1rem;
        }

        .form-control {
            height: 54px;
            background: var(--input-bg);
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 1rem;
            padding: 0 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            color: var(--text-primary);
        }

        .form-control:hover {
            background: var(--input-bg-hover);
        }

        .form-control:focus {
            background: var(--bg-surface);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-login {
            width: 100%;
            height: 54px;
            background: var(--primary);
            color: white;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-login:hover {
            opacity: 0.95;
            transform: scale(0.985);
            box-shadow: 0 8px 24px rgba(0, 113, 227, 0.2);
        }

        .btn-login:active {
            transform: scale(0.96);
        }

        .theme-switch {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--bg-surface);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            z-index: 100;
        }

        [data-theme="dark"] .theme-switch {
            background: rgba(255, 255, 255, 0.05);
            color: #f5f5f7;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .theme-switch:hover {
            color: var(--text-primary);
            transform: rotate(15deg) scale(1.1);
        }

        .error-message {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            padding: 1rem;
            border-radius: 14px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border: 1px solid rgba(255, 59, 48, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-accounts {
            margin-top: 2.5rem;
            padding: 1rem;
            background: var(--bg-main);
            border-radius: 16px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .demo-accounts b {
            color: var(--text-primary);
        }

        /* Mobile specific optimizations */
        @media (max-width: 480px) {
            body {
                padding: 1rem;
                align-items: center;
                justify-content: center;
            }
            .login-card, [data-theme="dark"] .login-card {
                padding: 2.5rem 1.5rem;
                border: none;
                background: transparent;
                box-shadow: none;
            }
            .theme-switch {
                top: 1rem;
                right: 1rem;
            }
            .footer-text {
                bottom: 1rem;
            }
        }

        .footer-text {
            position: fixed;
            bottom: 2rem;
            left: 0; right: 0;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
            opacity: 0.5;
            pointer-events: none;
        }

    </style>
</head>
<body>

<button class="theme-switch" id="themeToggle" title="Switch appearance">
    <i data-feather="moon" id="themeIcon"></i>
</button>

<div class="login-card">
    <div class="brand-logo">
        <img src="assets/logo.svg" alt="Doon University Logo">
    </div>
    
    <div class="login-header">
        <h1>Welcome back</h1>
        <p>Enter your details to access the ERP</p>
    </div>

    <?php if ($error): ?>
        <div class="error-message">
            <i data-feather="alert-circle" style="width: 18px;"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="login_id" class="form-control" placeholder="Phone Number" required autocomplete="username">
        </div>
        
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin: 1.5rem 0.25rem; font-size: 0.9rem;">
            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; color: var(--text-secondary);">
                <input type="checkbox" name="remember" style="width: 18px; height: 18px; accent-color: var(--primary);"> Remember
            </label>
            <a href="#" style="color: var(--primary); text-decoration: none; font-weight: 600;">Forgot?</a>
        </div>

        <button type="submit" class="btn-login">
            Sign In
            <i data-feather="chevron-right" style="width: 18px;"></i>
        </button>
    </form>

    <div class="demo-accounts">
        Demo Access: <b>9876543210</b> / <b>admin123</b>
    </div>
</div>

<div class="footer-text">
    &copy; <?= date('Y') ?> Doon University
</div>

<script>
    feather.replace();

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    function updateThemeUI(theme) {
        themeIcon.setAttribute('data-feather', theme === 'dark' ? 'sun' : 'moon');
        feather.replace();
    }

    updateThemeUI(document.documentElement.getAttribute('data-theme'));

    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Smooth transition trigger
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeUI(newTheme);
    });

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            updateThemeUI(newTheme);
        }
    });
</script>
</body>
</html>
