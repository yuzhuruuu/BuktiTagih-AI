<?php
require 'vendor/autoload.php';

$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get latest analysis
$stmt = $db->query("SELECT analysis_id, evidence_id, category, severity, confidence, regulation_reference FROM ai_analysis ORDER BY analysis_id DESC LIMIT 1");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== LATEST AI_ANALYSIS ===\n";
echo "Analysis ID: " . $result['analysis_id'] . "\n";
echo "Evidence ID: " . $result['evidence_id'] . "\n";
echo "Category: " . $result['category'] . "\n";
echo "Severity: " . $result['severity'] . "\n";
echo "Confidence: " . $result['confidence'] . "\n";
echo "Regulation Reference:\n";
$regRef = json_decode($result['regulation_reference'], true);
echo json_encode($regRef, JSON_PRETTY_PRINT) . "\n";

// Get extracted entities
$stmt = $db->query("SELECT COUNT(*) as count FROM extracted_entity WHERE evidence_id = " . $result['evidence_id']);
$entityCount = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nExtracted Entities Count: " . $entityCount['count'] . "\n";

// Show entities
if ($entityCount['count'] > 0) {
    echo "\n=== ENTITIES ===\n";
    $stmt = $db->query("SELECT entity_type, entity_value, confidence FROM extracted_entity WHERE evidence_id = " . $result['evidence_id']);
    $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entities as $entity) {
        echo $entity['entity_type'] . ": " . $entity['entity_value'] . " (confidence: " . $entity['confidence'] . ")\n";
    }
}

echo "\n✅ DB CHECK COMPLETE\n";
?>
