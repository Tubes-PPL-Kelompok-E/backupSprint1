<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instansi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();          // kode unik, contoh: PMI-001 / RS-001
            $table->string('nama', 150);
            $table->enum('tipe', ['pmi', 'rumah_sakit']);
            $table->text('alamat');
            $table->string('kota', 100);
            $table->string('telepon', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipe', 'kota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instansi');
    }
};
