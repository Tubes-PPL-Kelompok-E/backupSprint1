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
        Schema::create('uji_saring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantong_darah_id')->constrained('kantong_darah')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_uji');
            $table->enum('hiv', ['Non-Reaktif', 'Reaktif'])->default('Non-Reaktif');
            $table->enum('hbsag', ['Non-Reaktif', 'Reaktif'])->default('Non-Reaktif');
            $table->enum('hcv', ['Non-Reaktif', 'Reaktif'])->default('Non-Reaktif');
            $table->enum('sifilis', ['Non-Reaktif', 'Reaktif'])->default('Non-Reaktif');
            $table->enum('kesimpulan', ['Lolos', 'Tidak Lolos'])->default('Lolos');
            $table->string('nomor_berita_acara', 50)->unique();
            $table->string('berkas_berita_acara')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uji_saring');
    }
};

