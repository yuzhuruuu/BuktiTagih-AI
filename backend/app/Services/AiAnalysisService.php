<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiAnalysisService
{
    protected string $langflowUrl;
    protected string $flowId;
    protected string $apiKey;

    public function __construct()
    {
        $this->langflowUrl = rtrim(config('services.langflow.url', 'http://localhost:7860'), '/');
        $this->flowId      = config('services.langflow.flow_id', '');
        $this->apiKey      = config('services.langflow.api_key', '');
    }

    /**
     * Kirim evidence ke Langflow untuk diproses AI.
     * Jika Langflow belum tersedia, kembalikan status simulasi.
     */
    public function analyzeDocument(int $evidenceId, string $filePath): array
    {
        if (empty($this->flowId)) {
            // Langflow belum dikonfigurasi — kembalikan simulasi
            return [
                'status'      => 'processing',
                'message'     => 'Dokumen berhasil diteruskan ke sistem AI.',
                'evidence_id' => $evidenceId,
            ];
        }

        return $this->sendToLangflow($evidenceId, $filePath);
    }

    /**
     * Kirim file ke endpoint Langflow sesuai flow_id dari .env
     */
    public function sendToLangflow(int $evidenceId, string $filePath): array
    {
        $endpoint = "{$this->langflowUrl}/api/v1/run/{$this->flowId}";

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->post($endpoint, [
            'evidence_id' => $evidenceId,
            'file_path'   => $filePath,
        ]);

        return $response->json() ?? ['status' => 'error', 'message' => 'Tidak ada respons dari Langflow'];
    }
}