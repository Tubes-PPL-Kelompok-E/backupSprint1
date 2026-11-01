<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_darah', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();                       // REQ-2026-1001
            $table->foreignId('instansi_id')->constrained('instansi');  // rumah sakit peminta

            // Pasien
            $table->string('nama_pasien', 100);
            $table->string('ruang', 100)->nullable();
            $table->string('no_rm', 30);

            // Kebutuhan darah
            $table->enum('golongan', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['pos', 'neg']);
            $table->enum('komponen', ['prc', 'wb', 'tc', 'ffp']);
            $table->unsignedTinyInteger('jumlah');
            $table->enum('prioritas', ['cito', 'rutin'])->default('rutin');

            // Proses
            $table->enum('status', ['menunggu', 'dalam_perjalanan', 'diterima', 'ditolak'])->default('menunggu');
            $table->dateTime('diajukan_pada');
            $table->text('alasan_penolakan')->nullable();

            // Pengiriman cold chain
            $table->string('kurir_nama', 100)->nullable();
            $table->string('kurir_kendaraan', 30)->nullable();
            $table->decimal('suhu_cold_box', 4, 1)->nullable();
            $table->string('posisi_terakhir', 150)->nullable();

            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('divalidasi_pada')->nullable();
            $table->dateTime('selesai_pada')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'prioritas']);
        });

        // Kantong yang dialokasikan (FEFO) untuk tiap permintaan
        Schema::create('permintaan_kantong', function (Blueprint $table) {
            $table->foreignId('permintaan_id')->constrained('permintaan_darah')->cascadeOnDelete();
            $table->foreignId('kantong_id')->unique()->constrained('kantong_darah'); // satu kantong hanya untuk satu permintaan
            $table->timestamps();

            $table->primary(['permintaan_id', 'kantong_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_kantong');
        Schema::dropIfExists('permintaan_darah');
    }
};
