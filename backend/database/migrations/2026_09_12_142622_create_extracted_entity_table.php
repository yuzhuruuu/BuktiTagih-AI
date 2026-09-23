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
        Schema::create('extracted_entity', function (Blueprint $table) {
                $table->id('entity_id');
                // Menghubungkan foreign key ke tabel evidence
                $table->unsignedBigInteger('evidence_id');
                $table->foreign('evidence_id')->references('evidence_id')->on('evidence')->onDelete('cascade');
                
                $table->string('entity_type');
                $table->text('entity_value');
                $table->decimal('confidence', 5, 4)->nullable();
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracted_entity');
    }
};
