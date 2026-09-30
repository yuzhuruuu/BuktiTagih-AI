<?php
/**
 * Test Script: Langflow API Integration
 * Untuk test format response dan confidence scale
 * 
 * Run: php test_langflow_api.php
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();

// Config dari .env
$LANGFLOW_URL = 'http://127.0.0.1:7860';
$LANGFLOW_FLOW_ID = 'e05721f1-c3a2-4a33-bbd2-d30dee3df995';
$LANGFLOW_API_KEY = 'sk-kgQnr6-TXb89fohaLTZbhMCz7VISNRmCu8CleGY8A64';
$LANGFLOW_CHAT_INPUT_ID = 'ChatInput-aPEX5';

// Test case: simple text (THREAT category)
$testText = "Kalau tidak bayar hari ini, kami sebar data keluarga kamu.";

echo "═════════════════════════════════════════════════════════════════\n";
echo "LANGFLOW API TEST\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

try {
    // Step 1: Create a temporary test file dengan content
    $testFile = tempnam(sys_get_temp_dir(), 'buktitagih_test_');
    file_put_contents($testFile, $testText);
    
    echo "[1] Test file created: $testFile\n";
    echo "    Content: $testText\n\n";

    // Step 2: Upload file ke Langflow
    echo "[2] Uploading file to Langflow...\n";
    
    $uploadUrl = "$LANGFLOW_URL/api/v1/files/upload/$LANGFLOW_FLOW_ID";
    $uploadResponse = $client->post($uploadUrl, [
        'headers' => [
            'x-api-key' => $LANGFLOW_API_KEY,
        ],
        'multipart' => [
            [
                'name' => 'file',
                'contents' => fopen($testFile, 'r'),
                'filename' => 'test_bukti.txt'
            ]
        ]
    ]);

    $uploadData = json_decode($uploadResponse->getBody()->getContents(), true);
    $filePath = $uploadData['file_path'] ?? null;

    if (!$filePath) {
        throw new Exception('File upload failed, no file_path returned');
    }

    echo "    ✓ File uploaded successfully\n";
    echo "    File path: $filePath\n\n";

    // Step 3: Run Langflow flow dengan file
    echo "[3] Running Langflow flow...\n";

    $runUrl = "$LANGFLOW_URL/api/v1/run/$LANGFLOW_FLOW_ID";
    $runPayload = [
        'output_type' => 'chat',
        'input_type' => 'chat',
        'input_value' => 'Analisis bukti terlampir.',
        'tweaks' => [
            $LANGFLOW_CHAT_INPUT_ID => [
                'files' => $filePath
            ]
        ]
    ];

    $runResponse = $client->post($runUrl, [
        'headers' => [
            'x-api-key' => $LANGFLOW_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'json' => $runPayload
    ]);

    $runData = json_decode($runResponse->getBody()->getContents(), true);
    
    echo "    ✓ Flow executed successfully\n\n";

    // Step 4: Parse response
    echo "[4] Parsing response...\n\n";

    if (!isset($runData['outputs'][0]['outputs'][0]['results']['message']['text'])) {
        echo "    ERROR: Unexpected response structure\n";
        echo "    Full response:\n";
        echo json_encode($runData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit(1);
    }

    $analysisText = $runData['outputs'][0]['outputs'][0]['results']['message']['text'];
    
    echo "    Raw response (first 500 chars):\n";
    echo "    " . substr($analysisText, 0, 500) . "...\n\n";

    // Parse JSON dari response
    $analysis = json_decode($analysisText, true);

    if (!$analysis) {
        throw new Exception('Failed to parse JSON from Langflow response');
    }

    echo "    ✓ Successfully parsed JSON response\n\n";

    // Step 5: Display parsed data
    echo "[5] Parsed Analysis Result:\n";
    echo "    ────────────────────────────────────────────\n";
    echo "    Category:        " . ($analysis['category'] ?? 'N/A') . "\n";
    echo "    Severity:        " . ($analysis['severity'] ?? 'N/A') . "\n";
    echo "    Confidence:      " . ($analysis['confidence'] ?? 'N/A') . "\n";
    echo "    Confidence Type: " . (is_int($analysis['confidence'] ?? null) ? "Integer" : "Float") . "\n";
    echo "    Reason:          " . substr($analysis['reason'] ?? '', 0, 80) . "...\n";
    echo "    Regulation Ref:  " . json_encode($analysis['regulation_reference'] ?? []) . "\n";
    echo "    Entities Count:  " . count($analysis['entities'] ?? []) . "\n";

    if (!empty($analysis['entities'])) {
        echo "\n    Entities extracted:\n";
        foreach ($analysis['entities'] as $entity) {
            echo "      - {$entity['entity_type']}: {$entity['entity_value']} (confidence: {$entity['confidence']})\n";
        }
    }

    echo "\n    ────────────────────────────────────────────\n";

    // Step 6: Test case validation
    echo "\n[6] Test Case Validation:\n";
    $expectedCategory = 'THREAT';
    $actualCategory = $analysis['category'] ?? null;
    
    if ($actualCategory === $expectedCategory) {
        echo "    ✓ Category matches expected: $expectedCategory\n";
    } else {
        echo "    ✗ Category mismatch! Expected: $expectedCategory, Got: $actualCategory\n";
    }

    echo "\n    ════════════════════════════════════════════\n";
    echo "    TEST PASSED ✓\n";
    echo "    ════════════════════════════════════════════\n";

    // Cleanup
    unlink($testFile);

} catch (Exception $e) {
    echo "\n    ════════════════════════════════════════════\n";
    echo "    ERROR: " . $e->getMessage() . "\n";
    echo "    ════════════════════════════════════════════\n";
    exit(1);
}
