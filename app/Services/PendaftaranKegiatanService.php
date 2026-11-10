<?php

namespace App\Services;

use App\Models\KegiatanDonor;
use App\Models\LogTransaksi;
use App\Models\PendaftaranKegiatan;
use App\Models\Pendonor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * pendonor mendaftar ke kegiatan donor secara daring.
 *
 * Aturan yang diperiksa, berurutan:
 *  1. kegiatan masih Terbuka dan tanggalnya belum lewat
 *  2. pendonor belum terdaftar pada kegiatan yang sama
 *  3. jeda minimal sejak donasi terakhir sudah terpenuhi pada tanggal kegiatan
 *  4. kuota masih tersedia
 * Pemeriksaan kuota dan penyimpanan berjalan dalam satu transaksi dengan kunci baris kegiatan,
 * sehingga dua pendonor yang mendaftar bersamaan tidak bisa melewati kuota.
 */
class PendaftaranKegiatanService
{
    /** @throws PendaftaranDitolak bila salah satu aturan tidak terpenuhi */
    public function daftar(Pendonor $pendonor, KegiatanDonor $kegiatan): PendaftaranKegiatan
    {
        try {
            return DB::transaction(function () use ($pendonor, $kegiatan) {
                $k = KegiatanDonor::whereKey($kegiatan->id)->lockForUpdate()->firstOrFail();

                if (! $k->bisaDidaftar()) {
                    throw new PendaftaranDitolak(
                        $k->status === 'terbuka'
                            ? 'Kegiatan ini sudah lewat dan tidak dapat didaftar.'
                            : 'Kegiatan ini sudah ' . ($k->status === 'dibatalkan' ? 'dibatalkan' : 'ditutup') . ' dan tidak menerima pendaftaran.'
                    );
                }

                if (PendaftaranKegiatan::where('pendonor_id', $pendonor->id)->where('kegiatan_id', $k->id)->exists()) {
                    throw new PendaftaranDitolak('Anda sudah terdaftar pada kegiatan ini.');
                }

                if (! $pendonor->layakPada($k->tanggal)) {
                    throw new PendaftaranDitolak(sprintf(
                        'Jeda minimal %d hari sejak donasi terakhir belum terpenuhi. Anda dapat mendonor kembali mulai %s.',
                        config('bloodconnect.interval_donor_hari'),
                        $pendonor->tanggalLayak()->translatedFormat('d F Y')
                    ));
                }

                if ($k->pendaftaran()->count() >= $k->kuota) {
                    throw new PendaftaranDitolak('Kuota kegiatan ini sudah penuh. Silakan pilih kegiatan lain.');
                }

                $pendaftaran = PendaftaranKegiatan::create([
                    'pendonor_id'      => $pendonor->id,
                    'kegiatan_id'      => $k->id,
                    'tanggal_daftar'   => now(),
                    'status_kehadiran' => 'terdaftar',
                ]);

                LogTransaksi::catat(
                    'pendaftaran_kegiatan', 'kegiatan', $k->id,
                    "{$pendonor->nama} ({$pendonor->kode_donor}) mendaftar pada kegiatan \"{$k->judul}\".",
                    ['baru' => ['pendonor_id' => $pendonor->id, 'kegiatan_id' => $k->id]]
                );

                return $pendaftaran;
            });
        } catch (UniqueConstraintViolationException) {
            // Jaring pengaman: constraint unik database menolak pendaftaran ganda yang lolos bersamaan.
            throw new PendaftaranDitolak('Anda sudah terdaftar pada kegiatan ini.');
        }
    }
}
