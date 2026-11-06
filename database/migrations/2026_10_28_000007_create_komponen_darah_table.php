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
        Schema::create('komponen_darah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_komponen');
            $table->string('kode_komponen', 20)->unique();
            $table->unsignedInteger('masa_simpan_hari');
            $table->decimal('suhu_simpan_min', 4, 1)->nullable();
            $table->decimal('suhu_simpan_max', 4, 1)->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komponen_darah');
    }
};

