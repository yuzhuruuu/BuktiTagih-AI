<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\AnalysisController;

// 1. Endpoint Evidence (Upload & Retrieval)
Route::post('/evidence/upload', [EvidenceController::class, 'upload']);
Route::get('/evidence', [EvidenceController::class, 'index']);
Route::get('/evidence/{id}', [EvidenceController::class, 'show']);

// 2. Endpoint Analysis (Sesuai API Contract Final)
Route::post('/analysis/start', [AnalysisController::class, 'start']);
Route::get('/analysis/{id}', [AnalysisController::class, 'show']);

// 3. Endpoint PDF Report (Sesuai API Contract Final)
Route::get('/report/{id}', [EvidenceController::class, 'generatePdfReport']);