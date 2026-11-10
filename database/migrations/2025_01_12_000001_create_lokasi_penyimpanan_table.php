<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lokasi penyimpanan kantong (cold room, freezer, dll.) beserta kapasitasnya.
        Schema::create('lokasi_penyimpanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('jenis', 20)->default('cold_room');   // cold_room, freezer, agitator, karantina
            $table->unsignedSmallInteger('kapasitas');
            $table->decimal('suhu_min', 4, 1)->nullable();
            $table->decimal('suhu_maks', 4, 1)->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_penyimpanan');
    }
};
