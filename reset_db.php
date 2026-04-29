<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database
    $pdo->exec("DROP DATABASE IF EXISTS doon_university_erp");
    echo "Database dropped.<br>";
    
    // Create new database
    $pdo->exec("CREATE DATABASE doon_university_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created.<br>";
    
    // Connect to new database
    $pdo->exec("USE doon_university_erp");
    
    // Load and execute schema.sql
    $schema = file_get_contents('database/schema.sql');
    $pdo->exec($schema);
    echo "Schema imported successfully.<br>";
    
    // Load and execute seed.php by including it
    echo "Running seed.php...<br>";
    include 'seed.php';
    
    echo "<br><b>Database Reset Complete!</b> You can now login.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
