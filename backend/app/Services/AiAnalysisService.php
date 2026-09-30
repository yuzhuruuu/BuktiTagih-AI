<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    private $langflowUrl;
    private $langflowFlowId;
    private $langflowApiKey;
    private $langflowChatInputId;

    public function __construct()
    {
        $this->langflowUrl = env('LANGFLOW_URL', 'http://127.0.0.1:7860');
        $this->langflowFlowId = env('LANGFLOW_FLOW_ID');
        $this->langflowApiKey = env('LANGFLOW_API_KEY');
        $this->langflowChatInputId = env('LANGFLOW_CHAT_INPUT_ID', 'ChatInput-aPEX5');

        if (!$this->langflowFlowId || !$this->langflowApiKey) {
            throw new \Exception('Langflow configuration missing in .env');
        }
    }

    /**
     * Analyze document (image/PDF) through Langflow
     * 2-step process:
     *   1. Upload file to Langflow
     *   2. Run flow with file reference
     *
     * @param int $evidenceId
     * @param string $filePath Full path to file
     * @return array Analysis result with category, severity, reason, confidence, entities
     * @throws \Exception
     */
    public function analyzeDocument($evidenceId, $filePath)
    {
        try {
            Log::info("Starting Langflow analysis", ['evidence_id' => $evidenceId, 'file' => basename($filePath)]);

            // Step 1: Upload file
            $uploadedFilePath = $this->uploadFileToLangflow($filePath);
            Log::info("File uploaded to Langflow", ['uploaded_path' => $uploadedFilePath]);

            // Step 2: Run flow with uploaded file
            $analysisResult = $this->runLangflowAnalysis($uploadedFilePath);
            Log::info("Langflow analysis completed", ['evidence_id' => $evidenceId, 'category' => $analysisResult['category']]);

            // Step 3: Enrich with regulation references
            $category = $analysisResult['category'] ?? 'PENDING';
            $regulationRef = $this->getRegulationReferenceForCategory($category);

            return [
                'status' => 'success',
                'analysis_id' => null,  // Will be set after DB insert
                'category' => $analysisResult['category'],
                'severity' => $analysisResult['severity'],
                'reason' => $analysisResult['reason'],
                'confidence' => $analysisResult['confidence'],
                'regulation_reference' => $regulationRef,
                'entities' => $analysisResult['entities'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error("Langflow analysis failed", [
                'evidence_id' => $evidenceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'category' => 'PENDING',
                'severity' => 'PENDING',
                'reason' => 'Analisis gagal memproses bukti. Silakan coba lagi.',
                'confidence' => 0,
            ];
        }
    }

    /**
     * Step 1: Upload file to Langflow
     * POST /api/v1/files/upload/{FLOW_ID}
     */
    private function uploadFileToLangflow($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $uploadUrl = "{$this->langflowUrl}/api/v1/files/upload/{$this->langflowFlowId}";
        
        $response = Http::withHeaders([
            'x-api-key' => $this->langflowApiKey,
        ])->attach(
            'file',
            fopen($filePath, 'r'),
            basename($filePath)
        )->post($uploadUrl);

        if (!$response->successful()) {
            throw new \Exception(
                "File upload failed: " . $response->status() . " " . $response->body()
            );
        }

        $data = $response->json();
        $uploadedPath = $data['file_path'] ?? null;

        if (!$uploadedPath) {
            throw new \Exception("No file_path in Langflow response");
        }

        return $uploadedPath;
    }

    /**
     * Step 2: Run Langflow flow with uploaded file
     * POST /api/v1/run/{FLOW_ID}
     */
    private function runLangflowAnalysis($uploadedFilePath)
    {
        $runUrl = "{$this->langflowUrl}/api/v1/run/{$this->langflowFlowId}";

        $payload = [
            'output_type' => 'chat',
            'input_type' => 'chat',
            'input_value' => 'Analisis bukti terlampir.',
            'tweaks' => [
                $this->langflowChatInputId => [
                    'files' => $uploadedFilePath,
                ]
            ]
        ];

        $response = Http::withHeaders([
            'x-api-key' => $this->langflowApiKey,
            'Content-Type' => 'application/json',
        ])->post($runUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception(
                "Langflow flow failed: " . $response->status() . " " . $response->body()
            );
        }

        $data = $response->json();

        // Extract message from nested response
        $messageText = $data['outputs'][0]['outputs'][0]['results']['message']['text'] ?? null;

        if (!$messageText) {
            throw new \Exception("Unexpected Langflow response structure");
        }

        // Parse JSON from message
        $analysis = json_decode($messageText, true);

        if (!$analysis) {
            throw new \Exception("Failed to parse JSON from Langflow response: " . substr($messageText, 0, 200));
        }

        // Validate required fields
        $required = ['category', 'severity', 'reason', 'confidence'];
        foreach ($required as $field) {
            if (!isset($analysis[$field])) {
                throw new \Exception("Missing required field in analysis: $field");
            }
        }

        // Ensure confidence is integer (0-100)
        $analysis['confidence'] = (int)$analysis['confidence'];

        return $analysis;
    }

    /**
     * Get regulation references untuk category tertentu
     * Simplified RAG: hardcoded per category
     */
    private function getRegulationReferenceForCategory($category)
    {
        $regulationMap = [
            'HARASSMENT' => [
                [
                    'law' => 'POJK No. 22/POJK.07/2020',
                    'article' => 'Perlindungan Konsumen Sektor Jasa Keuangan',
                    'note' => 'Larangan perilaku intimidatif dan merendahkan martabat dalam penagihan'
                ],
                [
                    'law' => 'AFPI Code of Conduct',
                    'article' => 'Standar Etika Penagihan',
                    'note' => 'Penagihan hanya boleh dilakukan dengan cara yang hormat dan profesional'
                ]
            ],
            'THREAT' => [
                [
                    'law' => 'KUHP',
                    'article' => 'Pasal 335',
                    'note' => 'Perbuatan yang menyebabkan ketakutan atau kecemasan bagi orang lain'
                ],
                [
                    'law' => 'UU ITE',
                    'article' => 'Pasal 29',
                    'note' => 'Ancaman kekerasan melalui media elektronik'
                ],
                [
                    'law' => 'POJK No. 22/POJK.07/2020',
                    'article' => 'Perlindungan Konsumen',
                    'note' => 'Larangan penggunaan kekerasan atau ancaman dalam penagihan'
                ]
            ],
            'DATA_EXPOSURE' => [
                [
                    'law' => 'UU No. 27 Tahun 2022',
                    'article' => 'Perlindungan Data Pribadi (UU PDP)',
                    'note' => 'Perlindungan data pribadi termasuk identitas, lokasi, dan informasi sensitif'
                ],
                [
                    'law' => 'POJK No. 1/POJK.07/2013',
                    'article' => 'Kerahasiaan Data Konsumen',
                    'note' => 'Lembaga jasa keuangan wajib menjaga kerahasiaan data konsumen'
                ]
            ],
            'SPAM' => [
                [
                    'law' => 'AFPI Code of Conduct',
                    'article' => 'Pembatasan Frekuensi Kontak',
                    'note' => 'Penagihan hanya boleh dilakukan pada jam yang wajar dan tidak berlebihan'
                ],
                [
                    'law' => 'UU ITE',
                    'article' => 'Pasal 27',
                    'note' => 'Larangan penggunaan sistem informasi untuk mengganggu atau membuat malu'
                ]
            ],
            'NORMAL' => [
                [
                    'law' => 'Tidak ada indikasi pelanggaran',
                    'article' => 'Proses penagihan normal',
                    'note' => 'Bukti yang diupload tidak menunjukkan tanda-tanda pelanggaran regulasi'
                ]
            ],
        ];

        return $regulationMap[$category] ?? $regulationMap['NORMAL'];
    }
}
