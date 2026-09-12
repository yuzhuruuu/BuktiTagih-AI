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
        Schema::table('evidence', function (Blueprint $table) {
            // Add the missing column (adjust the type if user_id is an integer/foreignId)
            $table->string('user_id')->after('id'); 
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
