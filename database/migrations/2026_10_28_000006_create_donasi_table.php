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
        Schema::create('donasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_donasi', 30)->unique();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatan_donor')->nullOnDelete();
            $table->date('tanggal_donasi');
            $table->unsignedInteger('volume_ml')->default(350);
            $table->enum('status_donasi', ['Selesai', 'Dibatalkan'])->default('Selesai');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donasi');
    }
};

