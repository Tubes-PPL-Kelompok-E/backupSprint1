<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ERD: KEGIATAN_DONOR
        Schema::create('kegiatan_donor', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedSmallInteger('kuota');
            $table->string('nama_tempat', 150);
            $table->text('alamat');
            $table->enum('status', ['terbuka', 'ditutup', 'dibatalkan'])->default('terbuka');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'tanggal']);
        });

        // ERD: PENDAFTARAN_KEGIATAN. Unik per (pendonor, kegiatan) agar tidak ada pendaftaran ganda.
        Schema::create('pendaftaran_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan_donor')->cascadeOnDelete();
            $table->dateTime('tanggal_daftar');
            $table->enum('status_kehadiran', ['terdaftar', 'hadir', 'tidak_hadir'])->default('terdaftar');
            $table->timestamps();

            $table->unique(['pendonor_id', 'kegiatan_id']);
            $table->index('kegiatan_id');
        });

        Schema::table('donasi', function (Blueprint $table) {
            $table->foreign('kegiatan_id')->references('id')->on('kegiatan_donor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donasi', fn (Blueprint $t) => $t->dropForeign(['kegiatan_id']));
        Schema::dropIfExists('pendaftaran_kegiatan');
        Schema::dropIfExists('kegiatan_donor');
    }
};
