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
            $table->id('entity_id'); //[cite: 1]
            $table->foreignId('evidence_id')->references('evidence_id')->on('evidence')->onDelete('cascade'); //[cite: 1]
            $table->string('entity_type'); //[cite: 1]
            $table->text('entity_value'); //[cite: 1]
            $table->decimal('confidence', 5, 4)->nullable(); //[cite: 1]
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
