<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiAnalysis;
use App\Models\Evidence;

class AnalysisController extends Controller
{
    // POST /api/analysis/start
    public function start(Request $request)
    {
        $request->validate([
            'evidence_id' => 'required|exists:evidence,evidence_id'
        ]);

        // Simulasi inisialisasi proses AI / Langflow
        // Nantinya di sinilah koneksi ke Langflow API ditaruh
        $analysis = AiAnalysis::create([
            'evidence_id' => $request->evidence_id,
            'category' => 'PENDING', // Status awal sebelum diproses AI
            'severity' => 'PENDING',
            'reason' => 'Menunggu proses antrean AI pipeline...',
            'regulation_reference' => null,
            'confidence' => 0.0000
        ]);

        return response()->json([
            'message' => 'Analisis berhasil dimulai',
            'analysis_id' => $analysis->analysis_id,
            'status' => 'Processing'
        ], 201);
    }

    // GET /api/analysis/{id}
    public function show($id)
    {
        $analysis = AiAnalysis::with('evidence')->where('analysis_id', $id)->first();

        if (!$analysis) {
            return response()->json(['message' => 'Data analisis tidak ditemukan'], 404);
        }

        return response()->json([
            'analysis_id' => $analysis->analysis_id,
            'category' => $analysis->category,
            'severity' => $analysis->severity,
            'evidence' => $analysis->evidence,
            'regulation_reference' => $analysis->regulation_reference,
            'confidence' => $analysis->confidence
        ], 200);
    }

    public function startAnalysis(Request $request)
    {
        // Ambil data 'evidence_id' yang dikirim dari Postman
        $evidenceId = $request->input('evidence_id');

        // TODO: Tambahkan logika atau proses analisis di sini

        // Berikan respons kembali ke Postman
        return response()->json([
            'status' => 'success',
            'message' => 'Analysis started successfully',
            'evidence_id' => $evidenceId
        ]);
    }
}