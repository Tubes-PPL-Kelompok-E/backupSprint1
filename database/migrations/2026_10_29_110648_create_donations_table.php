<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();       // pendonor
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // petugas
            $table->string('blood_bag_code', 50);
            $table->string('blood_type', 2)->nullable();
            $table->date('donation_date');
            $table->unsignedSmallInteger('volume_ml');
            $table->string('notes')->nullable();
            $table->string('status', 20)->default('tercatat'); // tercatat | dibatalkan
            $table->string('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'donation_date']);
            $table->index('blood_bag_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};