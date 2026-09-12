<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvidenceController;

Route::post('/evidence/upload', [EvidenceController::class, 'upload']); //[cite: 1]

// Kerangka untuk diintegrasikan dengan Langflow nanti
Route::post('/analysis/start', function () { 
    return response()->json(['status' => 'Not Implemented Yet'], 501); 
}); //[cite: 1]
Route::get('/analysis/{id}', function ($id) { 
    return response()->json(['status' => 'Not Implemented Yet'], 501); 
}); //[cite: 1]
Route::get('/report/{id}', function ($id) { 
    return response()->json(['status' => 'Not Implemented Yet'], 501); 
}); //[cite: 1]

// Menambahkan rute GET
Route::get('/evidence', [EvidenceController::class, 'index']);

Route::get('/evidence/{id}', [EvidenceController::class, 'show']);