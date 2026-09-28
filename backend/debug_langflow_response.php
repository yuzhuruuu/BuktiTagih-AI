<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "DEBUG: Test Langflow Response Format\n";
echo str_repeat("=", 70) . "\n\n";

$flowId = config('services.langflow.flow_id');
$apiKey = config('services.langflow.api_key');
$url = config('services.langflow.url');

$runEndpoint = "$url/api/v1/run/$flowId";

echo "Calling: POST $runEndpoint\n\n";

$response = Http::withHeaders([
    'x-api-key' => $apiKey,
])
->timeout(300)
->asJson()
->post($runEndpoint, [
    'input_type' => 'chat',
    'output_type' => 'chat',
    'input_value' => 'Test dummy',
    'tweaks' => [
        'ChatInput-aPEX5' => [
            'files' => 'test_file',
        ],
    ],
]);

echo "HTTP Code: {$response->status()}\n";
echo "Response Time: Done\n\n";

echo "RESPONSE STRUCTURE:\n";
$data = $response->json();
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Try to parse like the service does
echo "PARSING TEST:\n";
$raw = $data['outputs'][0]['outputs'][0]['results']['message']['text'] ?? null;
echo "Raw output: " . substr($raw, 0, 200) . "...\n\n";

if ($raw) {
    $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
    $cleaned = preg_replace('/\s*```$/', '', $cleaned);
    $parsed = json_decode($cleaned, true);
    
    echo "Parsed JSON:\n";
    echo json_encode($parsed, JSON_PRETTY_PRINT) . "\n";
}
