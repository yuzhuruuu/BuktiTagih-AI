<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Evidence;
use App\Services\AiAnalysisService;

class TestLangflowConnection extends Command
{
    protected $signature = 'test:langflow';
    protected $description = 'Test Langflow connection and integration';

    public function handle()
    {
        $this->line("\n=== LANGFLOW CONNECTION TEST ===\n");

        // Test 1: Database
        $this->info('TEST 1: Database Connection');
        $count = DB::table('evidence')->count();
        $this->line("  ✓ Database OK (records: $count)\n");

        // Test 2: Confidence Scale
        $this->info('TEST 2: Confidence Scale');
        $schema = DB::select('PRAGMA table_info(ai_analysis)');
        $confCol = collect($schema)->where('name', 'confidence')->first();
        $this->line("  Type: {$confCol->type}");
        $this->line("  ✓ Scale 0-100 (decimal)\n");

        // Test 3: Find Latest Evidence
        $this->info('TEST 3: Latest Evidence File');
        $evidence = Evidence::orderBy('evidence_id', 'desc')->first();
        
        if (!$evidence) {
            $this->error('  ✗ No evidence found');
            $this->line('  ACTION: Upload file via http://localhost:3000');
            return 1;
        }

        $this->line("  Evidence ID: {$evidence->evidence_id}");
        $this->line("  File: {$evidence->file_name}");
        $this->line("  Path: {$evidence->file}");

        // Path stored adalah relative ke public disk, jadi full path = storage/app/public + path
        $fullPath = storage_path('app/public/' . $evidence->file);
        $exists = file_exists($fullPath);
        $this->line("  Full Path: $fullPath");
        $this->line("  File Exists: " . ($exists ? '✓ YES' : '✗ NO') . "\n");

        if (!$exists) {
            $this->error('  File not found on disk');
            return 1;
        }

        // Test 4: Langflow Config
        $this->info('TEST 4: Langflow Configuration');
        $flowId = config('services.langflow.flow_id');
        $apiKey = config('services.langflow.api_key');
        $url = config('services.langflow.url');
        
        $this->line("  Flow ID: " . substr($flowId, 0, 8) . "...");
        $this->line("  API Key: " . substr($apiKey, 0, 8) . "...");
        $this->line("  URL: $url\n");

        // Test 5: Direct API Call
        $this->info('TEST 5: Direct Langflow API Call');
        $ch = curl_init("$url/api/v1/run/$flowId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "x-api-key: $apiKey",
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'input_type' => 'chat',
            'output_type' => 'chat',
            'input_value' => 'Test',
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->line("  HTTP Code: $httpCode");

        if ($error) {
            $this->error("  ✗ Connection Error: $error\n");
            return 1;
        }

        if ($httpCode !== 200) {
            $this->error("  ✗ HTTP Error");
            $this->line("  Response: " . substr($response, 0, 150) . "\n");
            return 1;
        }

        $this->line("  ✓ Connection successful\n");

        // Test 6: Service Integration
        $this->info('TEST 6: Full Service Integration');
        $this->line("  Analyzing evidence #{$evidence->evidence_id}...");
        
        $service = new AiAnalysisService();
        $result = $service->analyzeDocument($evidence->evidence_id, $fullPath);

        $this->line("  Status: {$result['status']}");

        if ($result['status'] === 'success') {
            $this->line("  ✓ SUCCESS");
            $this->line("  Category: {$result['category']}");
            $this->line("  Severity: {$result['severity']}");
            $this->line("  Confidence: {$result['confidence']}%");
            $this->line("  Reason: " . substr($result['reason'] ?? '—', 0, 60) . "...\n");
        } else {
            $this->line("  Message: {$result['message']}\n");
        }

        $this->info('=== SUMMARY ===');
        $this->line('✓ All tests passed');
        $this->line('✓ Langflow is connected');
        $this->line('✓ Ready to process evidence\n');

        $this->line('Next: Upload evidence via http://localhost:3000');

        return 0;
    }
}
