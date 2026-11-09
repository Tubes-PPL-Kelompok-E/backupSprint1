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
    Schema::table('kegiatan_donor', function (Blueprint $table) {
        $table->time('jam_mulai')->nullable()->after('tanggal');
        $table->time('jam_selesai')->nullable()->after('jam_mulai');
    });
}

public function down(): void
{
    Schema::table('kegiatan_donor', function (Blueprint $table) {
        $table->dropColumn(['jam_mulai', 'jam_selesai']);
    });
}

};
