#!/usr/bin/env php
<?php
/**
 * Test Script: End-to-End Upload → Langflow → DB Save
 * 
 * Usage: php test_full_flow.php
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();
$BACKEND_URL = 'http://127.0.0.1:8000/api';

// Generate test evidence file
$testContent = "Kalau tidak bayar hari ini, kami sebar data keluarga kamu.";
$testFile = tempnam(sys_get_temp_dir(), 'bukti_');
file_put_contents($testFile, $testContent);

echo "═════════════════════════════════════════════════════════════════\n";
echo "END-TO-END TEST: Upload → Langflow → DB\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

try {
    echo "[1] Creating test file...\n";
    echo "    File: " . basename($testFile) . "\n";
    echo "    Content: $testContent\n\n";

    echo "[2] Testing POST /api/evidence/upload...\n";
    
    try {
        $response = $client->post("$BACKEND_URL/evidence/upload", [
            'http_errors' => false,
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($testFile, 'r'),
                    'filename' => 'test_evidence.txt'
                ],
                [
                    'name' => 'user_id',
                    'contents' => 'test_user_' . uniqid()
                ]
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 201) {
            echo "    ✗ Upload failed with status " . $response->getStatusCode() . "\n";
            echo "    Response:\n";
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            throw new Exception("Upload failed");
        }
    } catch (Exception $e) {
        echo "    ✗ Exception: " . $e->getMessage() . "\n";
        throw $e;
    }

    echo "    ✓ Upload successful (201)\n\n";

    echo "[3] Response Data:\n";
    echo "    ────────────────────────────────────────────\n";
    echo "    Evidence ID:     " . ($responseData['evidence_id'] ?? 'N/A') . "\n";
    echo "    Upload Status:   " . ($responseData['upload_status'] ?? 'N/A') . "\n";
    
    if (isset($responseData['ai_process'])) {
        $ai = $responseData['ai_process'];
        echo "\n    AI Process Result:\n";
        echo "      Status:        " . ($ai['status'] ?? 'N/A') . "\n";
        echo "      Category:      " . ($ai['category'] ?? 'N/A') . "\n";
        echo "      Severity:      " . ($ai['severity'] ?? 'N/A') . "\n";
        echo "      Confidence:    " . ($ai['confidence'] ?? 'N/A') . "\n";
        echo "      Entities:      " . ($ai['entities_count'] ?? 0) . " extracted\n";
        echo "      Reason:        " . substr($ai['reason'] ?? '', 0, 80) . "...\n";
    }

    echo "    ────────────────────────────────────────────\n\n";

    // Test 4: Fetch analysis result
    $evidenceId = $responseData['evidence_id'] ?? null;
    if ($evidenceId) {
        echo "[4] Testing GET /api/analysis/by-evidence/$evidenceId...\n";
        
        $analysisResponse = $client->get("$BACKEND_URL/analysis/by-evidence/$evidenceId");
        $analysisData = json_decode($analysisResponse->getBody()->getContents(), true);

        if ($analysisResponse->getStatusCode() === 200) {
            echo "    ✓ Analysis fetched successfully\n\n";
            echo "    Analysis Data:\n";
            echo "      Category:           " . ($analysisData['category'] ?? 'N/A') . "\n";
            echo "      Severity:           " . ($analysisData['severity'] ?? 'N/A') . "\n";
            echo "      Confidence:         " . ($analysisData['confidence'] ?? 'N/A') . "\n";
            echo "      Regulation Ref:     " . json_encode($analysisData['regulation_reference'] ?? []) . "\n";
            echo "\n";
        } else {
            echo "    ✗ Analysis fetch failed\n\n";
        }
    }

    echo "═════════════════════════════════════════════════════════════════\n";
    echo "✓ TEST PASSED\n";
    echo "═════════════════════════════════════════════════════════════════\n";

    unlink($testFile);

} catch (Exception $e) {
    echo "\n═════════════════════════════════════════════════════════════════\n";
    echo "✗ TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "═════════════════════════════════════════════════════════════════\n";
    
    if (file_exists($testFile)) {
        unlink($testFile);
    }
    exit(1);
}
