<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN signature_image VARCHAR(255) DEFAULT NULL AFTER profile_image");
    echo "Database updated successfully: signature_image column added.\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column name')) {
        echo "Column 'signature_image' already exists. No changes needed.\n";
    } else {
        echo "Error updating database: " . $e->getMessage() . "\n";
    }
}
?>
