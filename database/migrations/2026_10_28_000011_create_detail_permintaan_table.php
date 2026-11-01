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
        Schema::create('detail_permintaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_darah_id')->constrained('permintaan_darah')->cascadeOnDelete();
            $table->foreignId('komponen_darah_id')->constrained('komponen_darah')->cascadeOnDelete();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['+', '-']);
            $table->unsignedInteger('jumlah_diminta');
            $table->unsignedInteger('jumlah_dialokasikan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_permintaan');
    }
};

