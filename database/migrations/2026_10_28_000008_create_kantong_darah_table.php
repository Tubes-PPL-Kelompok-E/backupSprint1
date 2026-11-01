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
        Schema::create('kantong_darah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donasi_id')->constrained('donasi')->cascadeOnDelete();
            $table->foreignId('komponen_darah_id')->constrained('komponen_darah')->cascadeOnDelete();
            $table->string('kode_batch', 40)->unique();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['+', '-']);
            $table->unsignedInteger('volume')->default(350);
            $table->date('tanggal_donor');
            $table->date('tanggal_kedaluwarsa');
            $table->string('lokasi_simpan');
            $table->enum('status_kantong', [
                'Karantina',
                'Ready',
                'Reserved',
                'Terpakai',
                'Kedaluwarsa',
                'Dimusnahkan',
            ])->default('Karantina');
            $table->timestamps();

            // Indexes untuk optimasi query FEFO & pencarian stok
            $table->index(['golongan_darah', 'rhesus', 'status_kantong']);
            $table->index(['tanggal_kedaluwarsa', 'status_kantong']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kantong_darah');
    }
};

