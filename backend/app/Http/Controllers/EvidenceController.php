<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Evidence;
use App\Services\AiAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;

class EvidenceController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'user_id' => 'required|string'
        ]);

        $file = $request->file('file');

        // Ekstrak metadata file
        $hashFile = hash_file('sha256', $file->getRealPath());

        // Simpan file fisik dan ambil path-nya
        $filePath = $file->storeAs('public/evidence', $hashFile . '.' . $file->getClientOriginalExtension());

        // Simpan record ke DB terlebih dahulu untuk mendapatkan evidence_id yang valid
        $evidenceId = DB::table('evidence')->insertGetId([
            'user_id'     => $request->user_id,
            'file_name'   => $file->getClientOriginalName(),
            'file_type'   => $file->getClientMimeType(),
            'hash_file'   => $hashFile,
            'file'        => $filePath,
            'upload_time' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Panggil Service AI dengan evidence_id yang sudah valid
        $aiService = new AiAnalysisService();
        $aiResult = $aiService->analyzeDocument($evidenceId, $file->getRealPath());

        return response()->json([
            'evidence_id'   => $evidenceId,
            'upload_status' => 'success',
            'ai_process'    => $aiResult
        ], 201);
    }

    public function index()
    {
        // Mengambil seluruh data dari tabel evidence, diurutkan dari yang terbaru
        $evidences = DB::table('evidence')->latest('created_at')->get();

        // Mengembalikan data dalam format JSON untuk dibaca oleh Postman/Frontend
        return response()->json([
            'status' => 'success',
            'message' => 'Data tagihan berhasil diambil',
            'data' => $evidences
        ], 200);
    }

    public function show($id)
    {
        $evidence = Evidence::find($id);

        if (!$evidence) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $evidence], 200);
    }

    public function startAnalysis(Request $request)
    {
        $request->validate([
            'evidence_id' => 'required|integer|exists:evidence,evidence_id',
        ]);

        $evidenceId = $request->input('evidence_id');

        // Di sini nanti kita bisa integrasikan pemanggilan ke service Langflow (Person A)
        // Untuk tahap sekarang, kita buat respons sukses untuk simulasi pemicu analisis
        
        return response()->json([
            'analysis_id' => rand(1000, 9999), // Contoh ID analisis sementara
            'evidence_id' => $evidenceId,
            'status' => 'processing',
            'message' => 'Analisis AI berhasil dimulai.'
        ], 200);
    }

    public function generatePdfReport($id)
    {
        $evidence = DB::table('evidence')->where('evidence_id', $id)->first();

        if (!$evidence) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data evidence tidak ditemukan.',
            ], 404);
        }

        $analysis = DB::table('ai_analysis')->where('evidence_id', $id)->first();

        $severityLabel = [
            'HIGH'    => 'Tinggi',
            'MEDIUM'  => 'Sedang',
            'LOW'     => 'Rendah',
            'PENDING' => 'Menunggu Proses AI',
        ];

        $categoryLabel = [
            'HARASSMENT'    => 'Intimidasi / Harassment',
            'THREAT'        => 'Ancaman / Threat',
            'DATA_EXPOSURE' => 'Kebocoran Data',
            'SPAM'          => 'Spam Berlebihan',
            'NORMAL'        => 'Normal',
            'PENDING'       => 'Menunggu Proses AI',
        ];

        $data = [
            'evidence'       => $evidence,
            'analysis'       => $analysis,
            'generated_at'   => now()->format('d F Y, H:i') . ' WIB',
            'category_label' => $categoryLabel[$analysis->category ?? 'PENDING'] ?? ($analysis->category ?? 'Menunggu Proses AI'),
            'severity_label' => $severityLabel[$analysis->severity ?? 'PENDING'] ?? ($analysis->severity ?? '-'),
            'confidence_pct' => $analysis ? round(floatval($analysis->confidence) * 100) . '%' : '0%',
        ];

        $pdf = Pdf::loadView('reports.evidence_report', $data)
                  ->setPaper('a4', 'portrait');

        $fileName = 'BuktiTagih_Laporan_' . $id . '.pdf';
        return $pdf->download($fileName);
    }
}
