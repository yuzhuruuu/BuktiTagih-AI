<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Evidence;
use App\Jobs\ProcessAiEvidence;
use App\Services\AiAnalysisService;

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
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();
        $hashFile = hash_file('sha256', $file->getRealPath()); 

        $evidenceId = 1; 

        // Panggil Service AI untuk memproses dokumen
        $aiService = new AiAnalysisService();
        $aiResult =$aiService->analyzeDocument($evidenceId,$file->getRealPath());

        // Simpan file fisik dan ambil path-nya
        $filePath = $file->storeAs('public/evidence', $hashFile . '.' . $file->getClientOriginalExtension());

        $evidenceId = DB::table('evidence')->insertGetId([
            'user_id' => $request->user_id,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'hash_file' => $hashFile,
            'upload_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'evidence_id' => $evidenceId,
            'upload_status' => 'success',
            'ai_process' => $aiResult
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

    public function store(Request $request)
    {
        // 1. Validasi file dan user_id jika diperlukan
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'user_id' => 'required',
        ]);

        // 2. Proses simpan file ke storage (misal: public/evidence)
        $filePath = $request->file('file')->store('evidence', 'public');

        // 3. Simpan ke database menggunakan Model
        $evidence = Evidence::create([
            'user_id' => $request->user_id,
            'file' => $filePath,
        ]);

        ProcessAiEvidence::dispatch($evidenceId, $filePath);

        // 4. Kembalikan response beserta data yang baru dibuat (termasuk ID-nya)
        return response()->json([
            'success' => true,
            'message' => 'Berhasil disimpan!',
            'data' => $evidence // Di sini sudah termasuk id, user_id, file, dll.
        ], 200);
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
        // 1. Cek apakah data evidence dengan ID tersebut ada di database
        $evidence = DB::table('evidence')->where('evidence_id', $id)->first();

        if (!$evidence) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data evidence tidak ditemukan.'
            ], 404);
        }

        // 2. Ambil data analisis AI terkait evidence_id ini (jika sudah ada)
        $analysis = DB::table('ai_analysis')->where('evidence_id', $id)->first();

        // 3. Untuk tahap MVP awal, kita buat file PDF sederhana atau simulasi 
        // Menggunakan library bawaan atau teks sederhana yang di-download sebagai file PDF/TXT
        $fileName = 'BuktiTagih_Report_' . $id . '.pdf';
        $filePath = storage_path('app/public/reports/' . $fileName);

        // Pastikan folder storage/app/public/reports ada
        if (!file_exists(storage_path('app/public/reports'))) {
            mkdir(storage_path('app/public/reports'), 0755, true);
        }

        // Jika file fisik PDF belum ada, buat file dummy laporan sebagai placeholder darurat
        if (!file_exists($filePath)) {
            $content = "=== LAPORAN BUKTI TAGIH AI ===\n";
            $content .= "Evidence ID: " . $evidence->evidence_id . "\n";
            $content .= "Nama File: " . $evidence->file_name . "\n";
            $content .= "Waktu Upload: " . $evidence->upload_time . "\n";
            $content .= "Kategori Pelanggaran: " . ($analysis->category ?? 'Menunggu Analisis AI') . "\n";
            $content .= "Tingkat Keparahan (Severity): " . ($analysis->severity ?? '-') . "\n";
            $content .= "Referensi Regulasi: " . ($analysis->regulation_reference ?? '-') . "\n";
            
            file_put_contents($filePath, $content);
        }

        // 4. Kirim file sebagai respons unduhan ke pengguna/frontend[cite: 1]
        return response()->download($filePath, $fileName);
    }
}
