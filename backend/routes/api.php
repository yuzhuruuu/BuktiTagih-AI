<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\AnalysisController;

// Handle preflight requests
Route::options('/evidence/upload', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
});

Route::options('/analysis/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
})->where('any', '.*');

// Routes dengan CORS headers
// 1. Endpoint Evidence (Upload & Retrieval)
Route::post('/evidence/upload', [EvidenceController::class, 'upload'])
    ->middleware('cors');
Route::get('/evidence', [EvidenceController::class, 'index'])
    ->middleware('cors');
Route::get('/evidence/{id}', [EvidenceController::class, 'show'])
    ->middleware('cors');

// 2. Endpoint Analysis (Sesuai API Contract Final)
Route::post('/analysis/start', [AnalysisController::class, 'start'])
    ->middleware('cors');
Route::get('/analysis/by-evidence/{evidence_id}', [AnalysisController::class, 'showByEvidence'])
    ->middleware('cors');
Route::get('/analysis/{id}', [AnalysisController::class, 'show'])
    ->middleware('cors');

// 3. Endpoint PDF Report (Sesuai API Contract Final)
Route::get('/report/{id}', [EvidenceController::class, 'generatePdfReport'])
    ->middleware('cors');