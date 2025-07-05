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
        Schema::table('lapangans', function (Blueprint $table) {
            // Menambahkan kolom limit_sewa setelah kolom biayasewa
            // Default 7 berarti setiap lapangan memiliki batas 7 sewa per hari
            $table->integer('limit_sewa')->after('biayasewa')->default(7);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lapangans', function (Blueprint $table) {
            $table->dropColumn('limit_sewa');
        });
    }
};