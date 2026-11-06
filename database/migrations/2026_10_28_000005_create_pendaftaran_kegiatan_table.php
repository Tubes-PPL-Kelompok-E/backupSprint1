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
        Schema::create('pendaftaran_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan_donor')->cascadeOnDelete();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->dateTime('tanggal_pendaftaran');
            $table->enum('status_kehadiran', ['Hadir', 'Tidak Hadir', 'Belum Hadir'])->default('Belum Hadir');
            $table->timestamps();

            $table->unique(['kegiatan_id', 'pendonor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_kegiatan');
    }
};

