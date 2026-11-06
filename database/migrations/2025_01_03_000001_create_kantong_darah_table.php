<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kantong_darah', function (Blueprint $table) {
            $table->id();
            $table->string('kode_batch', 40)->unique();
            $table->date('tanggal_donor');
            $table->unsignedSmallInteger('volume_ml');
            $table->enum('komponen', ['prc', 'wb', 'tc', 'ffp']);
            $table->enum('golongan', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['pos', 'neg']);
            $table->date('tanggal_kedaluwarsa');
            $table->enum('status', ['karantina', 'ready', 'reserved', 'ditahan', 'dimusnahkan'])->default('karantina');
            $table->string('lokasi_simpan')->nullable();

            // Hasil uji saring dan berita acara
            $table->enum('hasil_uji', ['non_reaktif', 'reaktif'])->nullable();
            $table->string('nomor_berita_acara', 50)->nullable();
            $table->date('tanggal_uji')->nullable();
            $table->string('berkas_berita_acara')->nullable();
            $table->text('catatan_uji')->nullable();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('tanggal_kedaluwarsa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kantong_darah');
    }
};
