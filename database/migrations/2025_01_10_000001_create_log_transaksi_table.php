<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan transaksi darah (kantong masuk, uji saring, kedaluwarsa, pemusnahan, dst). Tambah-saja.
        Schema::create('log_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();   // tanpa FK agar log tidak ikut berubah
            $table->string('nama_pengguna', 100)->nullable();
            $table->string('aksi', 40);
            $table->string('entitas_tipe', 40);
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('ringkasan', 255);
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['aksi', 'created_at']);
            $table->index(['entitas_tipe', 'entitas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_transaksi');
    }
};
