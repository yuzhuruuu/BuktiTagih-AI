<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Evidence;

$evidences = Evidence::orderBy('evidence_id', 'desc')->limit(5)->get(['evidence_id', 'file_name', 'file']);

echo "Latest uploaded files in DB:\n";
echo str_repeat("-", 100) . "\n";

foreach ($evidences as $e) {
    $fullPath = storage_path('app/' . $e->file);
    $exists = file_exists($fullPath) ? '✓' : '✗';
    echo "ID: {$e->evidence_id} | {$exists} | {$e->file}\n";
}

echo str_repeat("-", 100) . "\n";
echo "\nActual files in storage/app/public/evidence/:\n";

$dir = storage_path('app/public/evidence');
if (is_dir($dir)) {
    $files = array_diff(scandir($dir), ['.', '..']);
    echo "Count: " . count($files) . "\n";
    foreach (array_slice($files, 0, 5) as $file) {
        echo "  - $file\n";
    }
    if (count($files) > 5) {
        echo "  ... and " . (count($files) - 5) . " more\n";
    }
}
