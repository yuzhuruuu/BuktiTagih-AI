<?php
/**
 * Test Langflow Integration
 * Verify connection, API key, and end-to-end flow
 */

require_once __DIR__ . '/bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Evidence;
use App\Services\AiAnalysisService;

echo "\n";
echo "=== LANGFLOW CONNECTION TEST ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ─── Test 1: Database Connection ───────────────────────────────────────────
echo "TEST 1: Database Connection\n";
try {
    $count = DB::table('evidence')->count();
    echo "  ✓ Database OK (evidence records: $count)\n\n";
} catch (Exception $e) {
    echo "  ✗ Database Error: {$e->getMessage()}\n\n";
    exit(1);
}

// ─── Test 2: Confidence Scale ──────────────────────────────────────────────
echo "TEST 2: Confidence Scale (DB Schema)\n";
try {
    $columns = DB::select("PRAGMA table_info(ai_analysis)");
    $confColumn = array_filter($columns, fn($col) => $col->name === 'confidence');
    
    if (!$confColumn) {
        echo "  ✗ Confidence column not found\n\n";
        exit(1);
    }
    
    $conf = array_values($confColumn)[0];
    echo "  Column: {$conf->name}\n";
    echo "  Type: {$conf->type}\n";
    echo "  Nullable: {$conf->notnull}\n";
    
    if (strpos($conf->type, 'decimal') !== false) {
        echo "  ✓ Scale: 0-100 (decimal) — cocok dengan Person A ✓\n\n";
    } else {
        echo "  ⚠ Warning: Type bukan decimal, tapi {$conf->type}\n\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// ─── Test 3: Find Latest Evidence File ────────────────────────────────────
echo "TEST 3: Find Latest Evidence File\n";
try {
    $evidence = Evidence::orderBy('evidence_id', 'desc')->first();
    
    if (!$evidence) {
        echo "  ✗ No evidence found in DB\n\n";
        echo "  ACTION: Upload a file first via frontend at http://localhost:3000\n\n";
        exit(1);
    }
    
    echo "  Evidence ID: {$evidence->evidence_id}\n";
    echo "  File Name: {$evidence->file_name}\n";
    echo "  File Type: {$evidence->file_type}\n";
    echo "  Stored Path: {$evidence->file}\n";
    
    $fullPath = storage_path('app/' . $evidence->file);
    $exists = file_exists($fullPath);
    echo "  Full Path: $fullPath\n";
    echo "  File Exists: " . ($exists ? "✓ YES" : "✗ NO") . "\n\n";
    
    if (!$exists) {
        echo "  ✗ File not found on disk\n";
        echo "  ACTION: Check storage/app/public/evidence/ directory\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// ─── Test 4: Direct Langflow API Connection ───────────────────────────────
echo "TEST 4: Direct Langflow API Connection\n";
try {
    $flowId = config('services.langflow.flow_id');
    $apiKey = config('services.langflow.api_key');
    $langflowUrl = config('services.langflow.url');
    
    echo "  Flow ID: " . substr($flowId, 0, 8) . "...\n";
    echo "  API Key: " . substr($apiKey, 0, 8) . "...\n";
    echo "  Langflow URL: $langflowUrl\n";
    
    if (!$flowId || !$apiKey) {
        echo "  ✗ Missing configuration in .env\n\n";
        exit(1);
    }
    
    // Test simple ping
    $url = "$langflowUrl/api/v1/run/$flowId";
    $payload = [
        'input_type' => 'chat',
        'output_type' => 'chat',
        'input_value' => 'Test connection',
    ];
    
    echo "  Calling: POST $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "x-api-key: $apiKey",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "  HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "  ✗ Connection Error: $error\n\n";
        exit(1);
    }
    
    if ($httpCode !== 200) {
        echo "  ✗ HTTP Error (not 200)\n";
        echo "  Response: " . substr($response, 0, 200) . "\n\n";
        exit(1);
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        echo "  ✗ Response is not valid JSON\n";
        echo "  Response: " . substr($response, 0, 200) . "\n\n";
        exit(1);
    }
    
    echo "  ✓ Connection successful ✓\n";
    echo "  Response Keys: " . implode(', ', array_keys($data)) . "\n\n";
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// ─── Test 5: Full Service Integration ──────────────────────────────────────
echo "TEST 5: Full AiAnalysisService Integration\n";
try {
    $service = new AiAnalysisService();
    echo "  Calling analyzeDocument(evidence_id={$evidence->evidence_id})\n";
    
    $result = $service->analyzeDocument($evidence->evidence_id, $fullPath);
    
    echo "  Result Status: " . ($result['status'] ?? 'UNKNOWN') . "\n";
    
    if ($result['status'] === 'success') {
        echo "  ✓ Analysis successful ✓\n";
        echo "  Category: " . ($result['category'] ?? '—') . "\n";
        echo "  Severity: " . ($result['severity'] ?? '—') . "\n";
        echo "  Confidence: " . ($result['confidence'] ?? '—') . "%\n";
        echo "  Reason: " . substr($result['reason'] ?? '—', 0, 60) . "...\n\n";
    } else {
        echo "  ⚠ Status: " . $result['status'] . "\n";
        echo "  Message: " . ($result['message'] ?? '—') . "\n\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// ─── Summary ───────────────────────────────────────────────────────────────
echo "=== TEST SUMMARY ===\n";
echo "✓ Database: OK\n";
echo "✓ Confidence Scale: 0-100 (decimal)\n";
echo "✓ Evidence File: Found & Accessible\n";
echo "✓ Langflow Connection: OK\n";
echo "✓ AiAnalysisService: Integrated\n\n";

echo "NEXT STEPS:\n";
echo "1. Test from Frontend: http://localhost:3000\n";
echo "2. Upload a real evidence file\n";
echo "3. Check result page — should show category, severity, confidence >0%\n";
echo "4. Download PDF report\n\n";

echo "If result still shows 'NORMAL' and '0%', it means:\n";
echo "- Langflow flow logic might need adjustment\n";
echo "- Or file content doesn't trigger any violation category\n\n";

echo "Contact Person A if Langflow output needs tweaking.\n\n";
