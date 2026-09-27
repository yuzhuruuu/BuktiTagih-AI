<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom confidence dari decimal(5,4) — skala 0–1 —
 * menjadi decimal(5,2) — skala 0–100 — sesuai output Langflow/Gemini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_analysis', function (Blueprint $table) {
            $table->decimal('confidence', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_analysis', function (Blueprint $table) {
            $table->decimal('confidence', 5, 4)->nullable()->change();
        });
    }
};
