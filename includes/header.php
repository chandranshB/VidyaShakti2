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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doon University ERP</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Feather Icons for Beautiful SVGs -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>

<div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i data-feather="hexagon"></i> Doon ERP
        </div>
        
        <div class="mb-2" style="padding-bottom: 1rem; border-bottom: 1px solid var(--surface-border);">
            <div style="color: #fff; font-weight: 500;"><?= htmlspecialchars($name) ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;"><?= htmlspecialchars($role) ?></div>
        </div>

        <ul class="nav-links">
            <?php if ($role === 'admin'): ?>
                <li><a href="admin_dashboard.php"><i data-feather="grid"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i data-feather="users"></i> Manage Users</a></li>
            <?php elseif ($role === 'faculty'): ?>
                <li><a href="faculty_dashboard.php"><i data-feather="grid"></i> Dashboard</a></li>
                <li><a href="manage_questions.php"><i data-feather="database"></i> Question Pool</a></li>
                <li><a href="generate_exam.php"><i data-feather="file-text"></i> Generate Exam</a></li>
            <?php elseif ($role === 'student'): ?>
                <li><a href="student_dashboard.php"><i data-feather="grid"></i> Dashboard</a></li>
                <li><a href="admit_card.php"><i data-feather="credit-card"></i> Admit Card</a></li>
            <?php endif; ?>
            <li style="margin-top: auto;"><a href="logout.php" style="color: var(--danger);"><i data-feather="log-out"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
