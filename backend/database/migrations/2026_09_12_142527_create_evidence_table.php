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
        Schema::create('evidence', function (Blueprint $table) {
            $table->id('evidence_id'); //
            $table->string('user_id'); // Diisi identifier sesi anonim[cite: 1]
            $table->string('file_name'); //[cite: 1]
            $table->string('file_type'); //[cite: 1]
            $table->timestamp('upload_time')->useCurrent(); //[cite: 1]
            $table->string('hash_file'); //[cite: 1]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
