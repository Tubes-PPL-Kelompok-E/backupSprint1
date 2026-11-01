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
        Schema::create('permintaan_darah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rumah_sakit_id')->constrained('rumah_sakit')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nomor_permintaan', 40)->unique();
            $table->string('nama_pasien');
            $table->string('nomor_rekam_medis', 50);
            $table->enum('urgensi', ['Rutin', 'Cito'])->default('Rutin');
            $table->enum('status_permintaan', [
                'Menunggu Persetujuan',
                'Disetujui',
                'Ditolak',
                'Diproses',
                'Selesai',
            ])->default('Menunggu Persetujuan');
            $table->text('alasan_penolakan')->nullable();
            $table->text('catatan_klinis')->nullable();
            $table->timestamps();

            $table->index(['status_permintaan', 'urgensi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_darah');
    }
};

