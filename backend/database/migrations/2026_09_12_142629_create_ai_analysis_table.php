<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_analysis', function (Blueprint $table) {
                $table->id('analysis_id');
                // Menghubungkan foreign key ke tabel evidence
                $table->unsignedBigInteger('evidence_id');
                $table->foreign('evidence_id')->references('evidence_id')->on('evidence')->onDelete('cascade');
                
                $table->string('category');
                $table->string('severity');
                $table->text('reason')->nullable();
                $table->text('regulation_reference')->nullable();
                $table->decimal('confidence', 5, 4)->nullable();
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analysis');
    }
};
