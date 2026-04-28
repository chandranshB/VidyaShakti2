<?php
require_once 'includes/db.php';

try {
    // Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL
    )");

    // Insert default values if not exists
    $defaults = [
        'sec_a_count' => '10',
        'sec_a_marks' => '2',
        'sec_b_count' => '5',
        'sec_b_marks' => '5',
        'sec_c_count' => '3',
        'sec_c_marks' => '10'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    echo "Database updated successfully with settings table.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
