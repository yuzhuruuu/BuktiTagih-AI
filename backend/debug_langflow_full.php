<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$langflowUrl = 'http://127.0.0.1:7860';
$flowId = 'ac82f919-ae7e-42e7-8bbd-fd579de7af16';
$apiKey = 'sk-vTTKQGfeOsPGRiHu-CdBlPRuBI41zudaz7JRzozmAEE';
$chatInputId = 'ChatInput-aPEX5';
$filePath = 'test_bullying.txt';

echo "=== DEBUG LANGFLOW ===\n\n";

// Step 1: Upload file
echo "1. UPLOADING FILE...\n";
$uploadUrl = "$langflowUrl/api/v1/files/upload/$flowId";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uploadUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new \CURLFile($filePath)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $uploadHttpCode\n";
echo "Response: " . substr($uploadResponse, 0, 500) . "\n\n";

$uploadData = json_decode($uploadResponse, true);
$uploadedPath = $uploadData['file_path'] ?? null;

if (!$uploadedPath) {
    echo "❌ Upload failed. Response: $uploadResponse\n";
    exit(1);
}

echo "✅ File uploaded: $uploadedPath\n\n";

// Step 2: Run flow
echo "2. RUNNING LANGFLOW ANALYSIS...\n";
$runUrl = "$langflowUrl/api/v1/run/$flowId";

$payload = [
    'output_type' => 'chat',
    'input_type' => 'chat',
    'input_value' => 'Analisis bukti terlampir.',
    'tweaks' => [
        $chatInputId => [
            'files' => $uploadedPath,
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $runUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . $apiKey,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$runResponse = curl_exec($ch);
$runHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $runHttpCode\n";
echo "Response:\n" . json_encode(json_decode($runResponse, true), JSON_PRETTY_PRINT) . "\n\n";

// Extract message
$runData = json_decode($runResponse, true);
$messageText = trim($runData['outputs'][0]['outputs'][0]['results']['message']['text'] ?? '');

echo "3. EXTRACTED MESSAGE:\n";
echo $messageText . "\n\n";

// Parse JSON
$analysis = json_decode($messageText, true);
echo "4. PARSED ANALYSIS:\n";
echo json_encode($analysis, JSON_PRETTY_PRINT) . "\n";

?>
