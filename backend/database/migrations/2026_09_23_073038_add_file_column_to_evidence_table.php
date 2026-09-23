<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->string('file')->nullable()->after('hash_file');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn('file');
        });
    }
};
