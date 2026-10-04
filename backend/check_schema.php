<?php
require 'vendor/autoload.php';

$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check ai_analysis table schema
echo "=== AI_ANALYSIS SCHEMA ===\n";
$stmt = $db->query("PRAGMA table_info(ai_analysis)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n=== EXTRACTED_ENTITY SCHEMA ===\n";
$stmt = $db->query("PRAGMA table_info(extracted_entity)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n=== EVIDENCE SCHEMA ===\n";
$stmt = $db->query("PRAGMA table_info(evidence)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n✅ SCHEMA CHECK COMPLETE\n";
?>
