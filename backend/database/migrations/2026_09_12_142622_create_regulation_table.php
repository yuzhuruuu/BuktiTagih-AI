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
                $table->id('regulation_id');
                $table->string('source');
                $table->string('category');
                $table->string('topic')->nullable();
                $table->string('title')->nullable();
                $table->string('page')->nullable();
                $table->text('content');
                $table->text('keywords')->nullable();
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
