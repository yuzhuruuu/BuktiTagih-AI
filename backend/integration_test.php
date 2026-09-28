#!/usr/bin/env php
<?php
/**
 * Integration Test Script untuk BuktiTagih AI
 * Test flow: Upload → Analyze → Download PDF
 */

$apiBase = 'http://127.0.0.1:8000/api';
$testDir = __DIR__ . '/tests/integration';

// Create test directory if not exists
@mkdir($testDir, 0755, true);

echo "=== BuktiTagih AI — Integration Test ===\n\n";

// ─── Helper Functions ───────────────────────────────────────────────────────
function http($method, $endpoint, $data = null, $file = null) {
    global $apiBase;
    $url = $apiBase . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($method === 'POST') {
        if ($file) {
            // Multipart form data untuk file
            $cfile = curl_file_create($file['path'], $file['type'], $file['name']);
            $postData = array_merge(['file' => $cfile], $data ?? []);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data ?? []));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

function test($name, $fn) {
    echo "TEST: $name\n";
    try {
        $fn();
        echo "  ✓ PASS\n\n";
        return true;
    } catch (Exception $e) {
        echo "  ✗ FAIL: {$e->getMessage()}\n\n";
        return false;
    }
}

$passed = 0;
$failed = 0;

// ─── Test 1: Check API Connection ───────────────────────────────────────────
if (test('API Connection', function() use ($apiBase) {
    $ch = curl_init($apiBase . '/evidence');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$response || ($code !== 200 && $code !== 0)) {
        throw new Exception("Connection failed (HTTP $code). Is backend running on port 8000?");
    }
})) { $passed++; } else { $failed++; }

// ─── Test 2: Create Test Image ──────────────────────────────────────────────
$testImagePath = null;
if (test('Create Test Image', function() use (&$testImagePath, $testDir) {
    // Create a minimal valid PDF for testing
    $testImagePath = "$testDir/test_evidence.pdf";
    
    // Minimal PDF structure
    $pdf = "%PDF-1.4\n"
         . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
         . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
         . "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n"
         . "4 0 obj\n<< >>\nstream\nBT\n/F1 12 Tf\n50 750 Td\n(Test Evidence Screenshot) Tj\nET\nendstream\nendobj\n"
         . "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n"
         . "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000074 00000 n\n0000000133 00000 n\n0000000244 00000 n\n0000000338 00000 n\n"
         . "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n423\n%%EOF";
    
    file_put_contents($testImagePath, $pdf);
    
    if (!file_exists($testImagePath)) {
        throw new Exception("Failed to create test PDF");
    }
})) { $passed++; } else { $failed++; }

if (!$testImagePath) {
    echo "\n✗ Cannot continue without test image\n";
    exit(1);
}

$sessionUserId = uniqid('test_');
echo "Session User ID: $sessionUserId\n\n";

// ─── Test 3: Upload Evidence ────────────────────────────────────────────────
$evidenceId = null;
if (test('Upload Evidence', function() use (&$evidenceId, $testImagePath, $sessionUserId) {
    $res = http('POST', '/evidence/upload', 
        ['user_id' => $sessionUserId],
        ['path' => $testImagePath, 'type' => 'image/png', 'name' => 'test.png']
    );
    
    if ($res['code'] !== 201) {
        throw new Exception("Upload failed (HTTP {$res['code']}): " . print_r($res['data'], true));
    }
    
    if (empty($res['data']['evidence_id'])) {
        throw new Exception("No evidence_id in response: " . print_r($res['data'], true));
    }
    
    $evidenceId = $res['data']['evidence_id'];
    echo "  Evidence ID: $evidenceId\n";
})) { $passed++; } else { $failed++; }

if (!$evidenceId) {
    echo "\n✗ Cannot continue without evidence_id\n";
    exit(1);
}

// ─── Test 4: Check Evidence was Saved ────────────────────────────────────────
if (test('Retrieve Uploaded Evidence', function() use ($evidenceId) {
    $res = http('GET', "/evidence/$evidenceId");
    
    if ($res['code'] !== 200) {
        throw new Exception("Retrieve failed (HTTP {$res['code']}): " . print_r($res['data'], true));
    }
    
    if (empty($res['data']['data'])) {
        throw new Exception("Evidence not found in DB");
    }
    
    echo "  File: {$res['data']['data']['file_name']}\n";
})) { $passed++; } else { $failed++; }

// ─── Test 5: Get Analysis by Evidence ID ────────────────────────────────────
$analysisId = null;
if (test('Get Analysis by Evidence', function() use (&$analysisId, $evidenceId) {
    $res = http('GET', "/analysis/by-evidence/$evidenceId");
    
    if ($res['code'] !== 200) {
        throw new Exception("Get analysis failed (HTTP {$res['code']}): " . print_r($res['data'], true));
    }
    
    if (empty($res['data']['analysis_id'])) {
        throw new Exception("No analysis_id found");
    }
    
    $analysisId = $res['data']['analysis_id'];
    echo "  Analysis ID: $analysisId\n";
    echo "  Category: {$res['data']['category']}\n";
    echo "  Confidence: {$res['data']['confidence']}%\n";
})) { $passed++; } else { $failed++; }

// ─── Test 6: Download PDF Report ────────────────────────────────────────────
if (test('Download PDF Report', function() use ($evidenceId, $testDir) {
    $res = http('GET', "/report/$evidenceId");
    
    if ($res['code'] !== 200) {
        throw new Exception("PDF download failed (HTTP {$res['code']})");
    }
    
    // PDF should start with %PDF
    if (strpos($res['body'], '%PDF') !== 0) {
        throw new Exception("Response is not a valid PDF");
    }
    
    $pdfPath = "$testDir/test_report.pdf";
    file_put_contents($pdfPath, $res['body']);
    
    if (!file_exists($pdfPath)) {
        throw new Exception("Failed to save PDF");
    }
    
    echo "  PDF saved: $pdfPath\n";
})) { $passed++; } else { $failed++; }

// ─── Summary ────────────────────────────────────────────────────────────────
echo "\n=== Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed === 0) {
    echo "\n✓ All tests passed! Integration is working.\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Check errors above.\n";
    exit(1);
}
