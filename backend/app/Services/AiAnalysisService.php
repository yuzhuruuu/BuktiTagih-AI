<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class AiAnalysisService
{
    public function analyzeDocument($evidenceId, $filePath)
    {
        // Di sini nantinya kita akan menulis kode untuk mengirim file ke API AI 
        // (misalnya menggunakan cURL atau HTTP Client bawaan Laravel)
        
        // Untuk tahap awal, kita kembalikan respon simulasi sukses terlebih dahulu
        return [
            'status' => 'processing',
            'message' => 'Dokumen berhasil diteruskan ke sistem AI.',
            'evidence_id' => $evidenceId
        ];
    }
    public function sendToLangflow($evidenceId, $filePath)
    {
        $response = Http::post('URL_ENDPOINT_LANGFLOW', [
            'evidence_id' => $evidenceId,
            'file_path' => $filePath,
        ]);

        return $response->json();
    }
}