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
        Schema::create('alokasi_kantong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_permintaan_id')->constrained('detail_permintaan')->cascadeOnDelete();
            $table->foreignId('kantong_darah_id')->constrained('kantong_darah')->cascadeOnDelete();
            $table->dateTime('tanggal_alokasi');
            $table->enum('status_alokasi', ['Reserved', 'Terpakai', 'Dibatalkan'])->default('Reserved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alokasi_kantong');
    }
};

