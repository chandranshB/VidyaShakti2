<?php
// includes/db.php

$host = 'localhost';
$dbname = 'doon_university_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, we can display a friendly error or setup instructions
    die("Database Connection failed: " . $e->getMessage() . "<br><br>Please make sure you have imported the database schema.");
}
?>
