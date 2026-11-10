<?php

namespace App\Console\Commands;

use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pengecekan berkala oleh scheduler: kantong Ready/Karantina yang sudah lewat
 * tanggal kedaluwarsa otomatis berstatus Kedaluwarsa dan keluar dari stok siap.
 * Aman dijalankan berulang: kantong yang sudah berstatus Kedaluwarsa tidak diproses lagi.
 */
class TandaiKedaluwarsa extends Command
{
    protected $signature = 'bloodconnect:tandai-kedaluwarsa';

    protected $description = 'Tandai kantong darah yang melewati tanggal kedaluwarsa.';

    public function handle(): int
    {
        $daftar = KantongDarah::whereIn('status', ['ready', 'karantina'])
            ->whereDate('tanggal_kedaluwarsa', '<', today())
            ->orderBy('id')
            ->get();

        foreach ($daftar as $k) {
            DB::transaction(function () use ($k) {
                $lama = $k->status;
                $k->update(['status' => 'kedaluwarsa', 'lokasi_simpan' => 'Kedaluwarsa']);
                LogTransaksi::catat('kantong_kedaluwarsa', 'kantong', $k->id,
                    "Kantong {$k->kode_batch} melewati tanggal kedaluwarsa dan dikeluarkan dari stok siap.",
                    ['lama' => ['status' => $lama], 'baru' => $k->untukLog() + ['status' => 'kedaluwarsa'], 'user' => null]);
            });
        }

        $this->info("Selesai: {$daftar->count()} kantong ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
