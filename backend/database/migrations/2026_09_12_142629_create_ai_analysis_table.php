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
            $table->id('analysis_id'); //[cite: 1]
            $table->foreignId('evidence_id')->references('evidence_id')->on('evidence')->onDelete('cascade'); //[cite: 1]
            $table->string('category'); //[cite: 1]
            $table->string('severity'); //[cite: 1]
            $table->text('reason')->nullable(); //[cite: 1]
            $table->text('regulation_reference')->nullable(); //[cite: 1]
            $table->decimal('confidence', 5, 4)->nullable(); //[cite: 1]
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
