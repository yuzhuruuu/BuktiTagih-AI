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
        Schema::create('regulation', function (Blueprint $table) {
            $table->id('regulation_id'); //[cite: 1]
            $table->string('source'); //[cite: 1]
            $table->string('category'); //[cite: 1]
            $table->string('topic')->nullable(); //[cite: 1]
            $table->string('title')->nullable(); //[cite: 1]
            $table->string('page')->nullable(); //[cite: 1]
            $table->text('content'); //[cite: 1]
            $table->text('keywords')->nullable(); //[cite: 1]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulation');
    }
};
