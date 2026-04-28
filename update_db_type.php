<?php
require_once 'includes/db.php';

try {
    // Add question_type column
    $pdo->exec("ALTER TABLE question_pool ADD COLUMN question_type ENUM('objective', 'short', 'long') NOT NULL DEFAULT 'short' AFTER difficulty");

    // Add settings for default types
    $defaults = [
        'sec_a_type' => 'objective',
        'sec_b_type' => 'short',
        'sec_c_type' => 'long'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    echo "Database updated successfully with question_type.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
