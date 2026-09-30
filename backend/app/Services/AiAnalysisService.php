<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    protected string $langflowUrl;
    protected string $flowId;
    protected string $apiKey;
    protected string $chatInputId;

    public function __construct()
    {
        $this->langflowUrl  = rtrim(config('services.langflow.url', 'http://localhost:7860'), '/');
        $this->flowId       = config('services.langflow.flow_id', '');
        $this->apiKey       = config('services.langflow.api_key', '');
        $this->chatInputId  = config('services.langflow.chat_input_id', 'ChatInput-0');
    }

    /**
     * Kirim evidence ke Langflow untuk diproses AI.
     * Jika Langflow belum dikonfigurasi (LANGFLOW_FLOW_ID kosong), kembalikan status simulasi.
     */
    public function analyzeDocument(int $evidenceId, string $filePath): array
    {
        if (empty($this->flowId)) {
            return [
                'status'      => 'processing',
                'message'     => 'Dokumen berhasil diteruskan ke sistem AI.',
                'evidence_id' => $evidenceId,
            ];
        }

        return $this->sendToLangflow($evidenceId, $filePath);
    }

    /**
     * Alur dua langkah sesuai usulan Person A:
     * 1. Upload file ke Langflow /api/v1/files/upload/{flow_id}
     * 2. Run flow dengan file tersebut di-tweak ke komponen Chat Input
     *
     * @return array ['status', 'category', 'severity', 'reason', 'confidence', 'regulation_reference', 'entities']
     */
    public function sendToLangflow(int $evidenceId, string $filePath): array
    {
        try {
            // ── Langkah 1: upload file ──────────────────────────────────────────
            $uploadEndpoint = "{$this->langflowUrl}/api/v1/files/upload/{$this->flowId}";

            Log::info('Langflow: Mulai upload file', ['evidence_id' => $evidenceId, 'endpoint' => $uploadEndpoint]);

            // Tidak set Content-Type manual — biarkan Laravel set multipart/form-data + boundary otomatis
            $uploadResponse = Http::withHeaders($this->authHeaders())
                ->timeout(60)
                ->attach('file', fopen($filePath, 'r'), basename($filePath))
                ->post($uploadEndpoint);

            if (! $uploadResponse->successful()) {
                Log::error('Langflow file upload gagal', [
                    'evidence_id' => $evidenceId,
                    'status'      => $uploadResponse->status(),
                    'body'        => $uploadResponse->body(),
                ]);
                return ['status' => 'error', 'message' => 'Upload file ke Langflow gagal.'];
            }

            $langflowFilePath = $uploadResponse->json('file_path') ?? $uploadResponse->json('flowId');

            if (empty($langflowFilePath)) {
                Log::error('Langflow upload response tidak mengandung file_path', [
                    'evidence_id' => $evidenceId,
                    'response'    => $uploadResponse->json(),
                ]);
                return ['status' => 'error', 'message' => 'Respons upload Langflow tidak valid.'];
            }

            Log::info('Langflow: Upload file berhasil', ['evidence_id' => $evidenceId, 'file_path' => $langflowFilePath]);

            // ── Langkah 2: run flow ─────────────────────────────────────────────
            $runEndpoint = "{$this->langflowUrl}/api/v1/run/{$this->flowId}";

            Log::info('Langflow: Mulai run flow', ['evidence_id' => $evidenceId, 'endpoint' => $runEndpoint]);

            $runResponse = Http::withHeaders($this->authHeaders())
                ->timeout(120)
                ->asJson()
                ->post($runEndpoint, [
                    'input_type'  => 'chat',
                    'output_type' => 'chat',
                    'input_value' => 'Analisis bukti terlampir.',
                    'tweaks'      => [
                        $this->chatInputId => [
                            'files' => $langflowFilePath,
                        ],
                    ],
                ]);

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

    /**
     * Ekstrak field yang dibutuhkan dari respons JSON Langflow /run.
     * Langflow membungkus output di outputs[0].outputs[0].results.message.text
     */
    private function parseRunResponse(array $response): array
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
