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
        // ── Langkah 1: upload file ──────────────────────────────────────────
        $uploadEndpoint = "{$this->langflowUrl}/api/v1/files/upload/{$this->flowId}";

        $uploadResponse = Http::withHeaders($this->headers())
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

        // ── Langkah 2: run flow ─────────────────────────────────────────────
        $runEndpoint = "{$this->langflowUrl}/api/v1/run/{$this->flowId}";

        $runResponse = Http::withHeaders($this->headers())
            ->post($runEndpoint, [
                'input_type'  => 'chat',
                'output_type' => 'chat',
                'tweaks'      => [
                    $this->chatInputId => [
                        'files' => $langflowFilePath,
                    ],
                ],
            ]);

        if (! $runResponse->successful()) {
            Log::error('Langflow run flow gagal', [
                'evidence_id' => $evidenceId,
                'status'      => $runResponse->status(),
                'body'        => $runResponse->body(),
            ]);
            return ['status' => 'error', 'message' => 'Run flow Langflow gagal.'];
        }

        return $this->parseRunResponse($runResponse->json());
    }

    /**
     * Ekstrak field yang dibutuhkan dari respons JSON Langflow /run.
     * Langflow membungkus output di outputs[0].outputs[0].results.message.text
     */
    private function parseRunResponse(array $response): array
    {
        $raw = $response['outputs'][0]['outputs'][0]['results']['message']['text'] ?? null;

        if (empty($raw)) {
            Log::warning('Langflow run response tidak mengandung output teks', ['response' => $response]);
            return ['status' => 'error', 'message' => 'Tidak ada output teks dari Langflow.'];
        }

        // Langflow kadang membungkus JSON di dalam markdown code fence ```json ... ```
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $parsed = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
            Log::warning('Output Langflow bukan JSON valid', ['raw' => $raw]);
            return ['status' => 'error', 'message' => 'Output Langflow bukan JSON valid.', 'raw' => $raw];
        }

        return [
            'status'               => 'success',
            'category'             => $parsed['category']             ?? 'PENDING',
            'severity'             => $parsed['severity']             ?? 'PENDING',
            'reason'               => $parsed['reason']               ?? null,
            // confidence dari Langflow skala 0–100, simpan apa adanya (kolom decimal(5,2))
            'confidence'           => $parsed['confidence']           ?? 0,
            'regulation_reference' => isset($parsed['regulation_reference'])
                                        ? json_encode($parsed['regulation_reference'])
                                        : null,
            'entities'             => $parsed['entities']             ?? [],
        ];
    }

    private function headers(): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if (! empty($this->apiKey)) {
            $headers['x-api-key'] = $this->apiKey;
        }

        return $headers;
    }
}
