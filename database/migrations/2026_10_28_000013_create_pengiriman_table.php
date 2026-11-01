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
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_darah_id')->constrained('permintaan_darah')->cascadeOnDelete();
            $table->string('nomor_surat_jalan', 40)->unique();
            $table->string('nama_kurir');
            $table->string('plat_kendaraan', 20);
            $table->enum('status_pengiriman', [
                'Kantong Disiapkan',
                'Dalam Perjalanan',
                'Diterima RS',
            ])->default('Kantong Disiapkan');
            $table->dateTime('waktu_berangkat')->nullable();
            $table->dateTime('estimasi_tiba')->nullable();
            $table->decimal('suhu_kotak_pendingin', 4, 1)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
    }
};

