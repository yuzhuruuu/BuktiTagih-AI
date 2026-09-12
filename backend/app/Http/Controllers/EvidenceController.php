<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvidenceController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'user_id' => 'required|string'
        ]);

        $file = $request->file('file');
        $hashFile = hash_file('sha256', $file->getRealPath());
        $path = $file->store('evidence', 'public');

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
            'upload_status' => 'success'
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
}
