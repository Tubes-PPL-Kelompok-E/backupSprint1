<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pemeriksaan pra-donor (hemoglobin, tekanan darah, dll.) dan hasil kelayakan.
        Schema::create('pemeriksaan_donor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendonor_id')->constrained('pendonor')->cascadeOnDelete();
            $table->foreignId('diperiksa_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('hemoglobin', 3, 1);
            $table->unsignedSmallInteger('sistolik');
            $table->unsignedSmallInteger('diastolik');
            $table->decimal('berat_kg', 4, 1);
            $table->decimal('suhu_tubuh', 3, 1);
            $table->unsignedSmallInteger('nadi');
            $table->string('hasil', 12);                 // layak | tidak_layak
            $table->text('alasan')->nullable();          // alasan bila tidak layak (satu per baris)
            $table->string('catatan', 500)->nullable();
            $table->timestamps();

            $table->index(['pendonor_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_donor');
    }
};
