#!/usr/bin/env php
<?php
// Verify Langflow config di Laravel

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

echo "═════════════════════════════════════════════════════════════════\n";
echo "LANGFLOW CONFIG VERIFICATION\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

echo "Current config values:\n";
echo "  LANGFLOW_URL:          " . env('LANGFLOW_URL') . "\n";
echo "  LANGFLOW_FLOW_ID:      " . env('LANGFLOW_FLOW_ID') . "\n";
echo "  LANGFLOW_API_KEY:      " . substr(env('LANGFLOW_API_KEY'), 0, 10) . "...\n";
echo "  LANGFLOW_CHAT_INPUT_ID:" . env('LANGFLOW_CHAT_INPUT_ID') . "\n\n";

// Try connect to Langflow
$flowId = env('LANGFLOW_FLOW_ID');
$url = env('LANGFLOW_URL');
$apiKey = env('LANGFLOW_API_KEY');

echo "Testing Langflow connectivity:\n";

try {
    $client = new GuzzleHttp\Client();
    $response = $client->get("$url/api/v1/flows", [
        'headers' => ['x-api-key' => $apiKey]
    ]);
    
    $flows = json_decode($response->getBody()->getContents(), true);
    
    echo "  ✓ Connected to Langflow\n";
    echo "  Available flows:\n";
    
    $foundFlow = false;
    if (is_array($flows)) {
        foreach ($flows as $flow) {
            $id = $flow['id'] ?? $flow['flow_id'] ?? null;
            $name = $flow['name'] ?? 'Unknown';
            
            echo "    - $name (ID: $id)\n";
            
            if ($id === $flowId) {
                $foundFlow = true;
                echo "      ✓ THIS IS YOUR CONFIGURED FLOW\n";
            }
        }
    }
    
    if (!$foundFlow) {
        echo "\n  ⚠ WARNING: Configured flow ID not found!\n";
        echo "  Make sure LANGFLOW_FLOW_ID in .env matches one of the flows above.\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Connection failed: " . $e->getMessage() . "\n";
}

echo "\n═════════════════════════════════════════════════════════════════\n";
