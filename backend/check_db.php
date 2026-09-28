<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$analysis = DB::table('ai_analysis')->where('evidence_id', 1)->first();

echo "DATABASE CHECK:\n";
echo str_repeat("-", 60) . "\n";
echo "Evidence ID: 1\n";
echo "Category: " . ($analysis->category ?? 'NULL') . "\n";
echo "Severity: " . ($analysis->severity ?? 'NULL') . "\n";
echo "Confidence: " . ($analysis->confidence ?? 'NULL') . "\n";
echo "Reason: " . substr($analysis->reason ?? '', 0, 100) . "\n";
echo "Regulation Ref: " . substr($analysis->regulation_reference ?? '', 0, 50) . "\n";
echo str_repeat("-", 60) . "\n";

// Check if matches what test showed
if ($analysis->category === 'NORMAL' && $analysis->confidence == 0) {
    echo "\n✓ Database matches test output (NORMAL, 0%)\n";
    echo "✓ But frontend showing PENDING — timing issue\n";
} else {
    echo "\n✗ Database doesn't match test output\n";
}
