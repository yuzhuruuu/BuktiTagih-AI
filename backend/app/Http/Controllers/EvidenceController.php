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
        $path = $file->store('evidence');

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
}
