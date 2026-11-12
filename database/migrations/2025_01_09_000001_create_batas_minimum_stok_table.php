<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // batas minimum stok per golongan, rhesus, komponen.
        // Kombinasi yang belum ada memakai nilai bawaan dari config/bloodconnect.php.
        Schema::create('batas_minimum_stok', function (Blueprint $table) {
            $table->id();
            $table->enum('golongan', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['pos', 'neg']);
            $table->enum('komponen', ['prc', 'wb', 'tc', 'ffp']);
            $table->unsignedSmallInteger('minimum');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['golongan', 'rhesus', 'komponen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batas_minimum_stok');
    }
};
