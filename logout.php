<?php
session_start();
require_once 'includes/db.php';

// If the user has a remember cookie, clear it in the DB
if (isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
    $stmt->execute(['token' => $token]);

    // Clear the cookie by setting it to the past
    setcookie('remember_user', '', time() - 3600, '/');
}

session_unset();
session_destroy();
header("Location: index.php");
exit;
?>
