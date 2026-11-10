<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ERD: PENDONOR. Satu akun pengguna paling banyak terhubung ke satu pendonor.
        Schema::create('pendonor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('kode_donor', 20)->unique();            // D-000001
            $table->string('nama', 100);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->enum('golongan', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['pos', 'neg']);
            $table->string('no_hp', 20)->nullable();
            $table->string('kota', 100);
            $table->text('alamat')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['golongan', 'rhesus', 'kota']);
        });

        // ERD: DONASI. Riwayat donasi, dipakai menghitung kelayakan donor berikutnya.
        Schema::create('donasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->unsignedBigInteger('kegiatan_id')->nullable();
            $table->date('tanggal_donor');
            $table->unsignedSmallInteger('volume_ml')->default(350);
            $table->string('lokasi', 150)->nullable();
            $table->timestamps();

            $table->index(['pendonor_id', 'tanggal_donor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasi');
        Schema::dropIfExists('pendonor');
    }
};
