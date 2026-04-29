<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['user_role'];
$name = $_SESSION['user_name'];

$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doon University ERP</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        // Professional Theme Management
        (function() {
            const getTheme = () => {
                const saved = localStorage.getItem('theme');
                if (saved) return saved;
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };
            document.documentElement.setAttribute('data-theme', getTheme());
        })();
        
        // Setup initial sidebar state for desktop
        if (window.innerWidth > 1024) {
            const sidebarState = localStorage.getItem('sidebarState') || 'expanded';
            if (sidebarState === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed-init');
            }
        }
    </script>
    <style>
        .sidebar-collapsed-init .sidebar { width: 80px; padding: 2rem 0.5rem; align-items: center; }
        .sidebar-collapsed-init .sidebar .sidebar-text, 
        .sidebar-collapsed-init .sidebar .user-profile div:last-child { display: none; }
        .sidebar-collapsed-init .sidebar .sidebar-logo { justify-content: center; }
        .sidebar-collapsed-init .sidebar .sidebar-logo svg,
        .sidebar-collapsed-init .sidebar .sidebar-logo img { margin: 0; display: block; }
        .sidebar-collapsed-init .sidebar .nav-links li a { justify-content: center; padding: 0.85rem; }
        .sidebar-collapsed-init .sidebar .nav-links li a svg { margin: 0; }
        .sidebar-collapsed-init .sidebar .action-btn { padding: 1rem 0; }
    </style>
</head>
<body>

<div class="app-container">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo" style="display: flex; align-items: center; gap: 12px;">
            <img src="assets/logo.svg" alt="Doon University" style="width: 32px; height: 32px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
            <span class="sidebar-text">Doon ERP</span>
        </div>
        
        <div class="user-profile mb-2">
            <div class="user-avatar" style="padding: 0; overflow: hidden; background: <?= empty($_SESSION['profile_image']) ? 'linear-gradient(135deg, var(--primary), var(--accent))' : 'transparent' ?>;">
                <?php if (!empty($_SESSION['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?= strtoupper(substr($name, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($name) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;"><?= htmlspecialchars($role) ?></div>
            </div>
        </div>

        <ul class="nav-links">
            <?php if ($role === 'admin'): ?>
                <li><a href="admin_dashboard.php" class="<?= isActive('admin_dashboard.php') ?>"><i data-feather="grid"></i> <span class="sidebar-text">Dashboard</span></a></li>
                <li><a href="profile.php" class="<?= isActive('profile.php') ?>"><i data-feather="user"></i> <span class="sidebar-text">My Profile</span></a></li>
                <li><a href="manage_users.php" class="<?= isActive('manage_users.php') ?>"><i data-feather="users"></i> <span class="sidebar-text">Manage Users</span></a></li>
                <li><a href="exam_settings.php" class="<?= isActive('exam_settings.php') ?>"><i data-feather="settings"></i> <span class="sidebar-text">Exam Settings</span></a></li>
                <li><a href="settings.php" class="<?= isActive('settings.php') ?>"><i data-feather="shield"></i> <span class="sidebar-text">Settings</span></a></li>
            <?php elseif ($role === 'faculty'): ?>
                <li><a href="faculty_dashboard.php" class="<?= isActive('faculty_dashboard.php') ?>"><i data-feather="grid"></i> <span class="sidebar-text">Dashboard</span></a></li>
                <li><a href="profile.php" class="<?= isActive('profile.php') ?>"><i data-feather="user"></i> <span class="sidebar-text">My Profile</span></a></li>
                <li><a href="manage_questions.php" class="<?= isActive('manage_questions.php') ?>"><i data-feather="database"></i> <span class="sidebar-text">Question Pool</span></a></li>
                <li><a href="generate_exam.php" class="<?= isActive('generate_exam.php') ?>"><i data-feather="file-text"></i> <span class="sidebar-text">Generate Exam</span></a></li>
                <li><a href="settings.php" class="<?= isActive('settings.php') ?>"><i data-feather="shield"></i> <span class="sidebar-text">Settings</span></a></li>
            <?php elseif ($role === 'student'): ?>
                <li><a href="student_dashboard.php" class="<?= isActive('student_dashboard.php') ?>"><i data-feather="grid"></i> <span class="sidebar-text">Dashboard</span></a></li>
                <li><a href="profile.php" class="<?= isActive('profile.php') ?>"><i data-feather="user"></i> <span class="sidebar-text">My Profile</span></a></li>
                <li><a href="attendance.php" class="<?= isActive('attendance.php') ?>"><i data-feather="calendar"></i> <span class="sidebar-text">My Attendance</span></a></li>
                <li><a href="admit_card.php" class="<?= isActive('admit_card.php') ?>" target="_blank"><i data-feather="credit-card"></i> <span class="sidebar-text">Admit Card</span></a></li>
                <li><a href="settings.php" class="<?= isActive('settings.php') ?>"><i data-feather="shield"></i> <span class="sidebar-text">Settings</span></a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-controls">
            <button class="action-btn" id="themeToggle" title="Toggle Theme">
                <i data-feather="moon" id="themeIcon"></i>
                <span class="sidebar-text" id="themeText">Dark Mode</span>
            </button>
            <button class="action-btn" id="collapseToggle" title="Collapse Sidebar" style="display: none;">
                <i data-feather="sidebar"></i>
                <span class="sidebar-text">Collapse</span>
            </button>
            <a href="logout.php" class="action-btn logout-btn">
                <i data-feather="log-out"></i> <span class="sidebar-text">Log Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div style="display: flex; align-items: center; gap: 0.75rem; font-weight: 700; color: var(--text-primary);">
                <img src="assets/logo.svg" alt="Logo" style="height: 28px; width: 28px; object-fit: contain;"> Doon ERP
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i data-feather="menu"></i>
            </button>
        </header>

        <div class="content-wrapper">
