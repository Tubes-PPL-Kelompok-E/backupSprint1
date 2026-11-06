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
        Schema::create('panggilan_pendonor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->foreignId('permintaan_darah_id')->nullable()->constrained('permintaan_darah')->nullOnDelete();
            $table->dateTime('tanggal_panggilan');
            $table->enum('metode', ['SMS', 'WhatsApp', 'Email'])->default('WhatsApp');
            $table->enum('status_respon', ['Menunggu', 'Bersedia', 'Menolak'])->default('Menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panggilan_pendonor');
    }
};

