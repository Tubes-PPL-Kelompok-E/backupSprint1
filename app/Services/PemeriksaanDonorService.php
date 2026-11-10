<?php

namespace App\Services;

use App\Models\LogTransaksi;
use App\Models\PemeriksaanDonor;
use App\Models\Pendonor;
use App\Models\User;


class PemeriksaanDonorService
{
    /** @return array{hasil:string,alasan:string[]} */
    public function evaluasi(Pendonor $pendonor, array $d): array
    {
        $c = config('bloodconnect.pemeriksaan');
        $alasan = [];

        if ($d['hemoglobin'] < $c['hb_min']) {
            $alasan[] = "Hemoglobin {$d['hemoglobin']} g/dL di bawah batas {$c['hb_min']}.";
        } elseif ($d['hemoglobin'] > $c['hb_maks']) {
            $alasan[] = "Hemoglobin {$d['hemoglobin']} g/dL di atas batas {$c['hb_maks']}.";
        }
        if ($d['sistolik'] < $c['sistolik_min'] || $d['sistolik'] > $c['sistolik_maks']) {
            $alasan[] = "Tekanan sistolik {$d['sistolik']} mmHg di luar rentang {$c['sistolik_min']}-{$c['sistolik_maks']}.";
        }
        if ($d['diastolik'] < $c['diastolik_min'] || $d['diastolik'] > $c['diastolik_maks']) {
            $alasan[] = "Tekanan diastolik {$d['diastolik']} mmHg di luar rentang {$c['diastolik_min']}-{$c['diastolik_maks']}.";
        }
        if ($d['berat_kg'] < $c['berat_min']) {
            $alasan[] = "Berat badan {$d['berat_kg']} kg di bawah batas {$c['berat_min']} kg.";
        }
        if ($d['suhu_tubuh'] > $c['suhu_maks']) {
            $alasan[] = "Suhu tubuh {$d['suhu_tubuh']} °C melebihi {$c['suhu_maks']} °C.";
        }
        if ($d['nadi'] < $c['nadi_min'] || $d['nadi'] > $c['nadi_maks']) {
            $alasan[] = "Denyut nadi {$d['nadi']}/menit di luar rentang {$c['nadi_min']}-{$c['nadi_maks']}.";
        }

        $umur = $pendonor->umur();
        if ($umur < $c['umur_min'] || $umur > $c['umur_maks']) {
            $alasan[] = "Usia {$umur} tahun di luar rentang {$c['umur_min']}-{$c['umur_maks']}.";
        }
        if (! $pendonor->layakPada()) {
            $alasan[] = 'Jeda minimal ' . config('bloodconnect.interval_donor_hari') . ' hari sejak donasi terakhir belum terpenuhi (layak kembali '
                . $pendonor->tanggalLayak()->translatedFormat('d F Y') . ').';
        }

        return ['hasil' => $alasan ? 'tidak_layak' : 'layak', 'alasan' => $alasan];
    }

    public function catat(Pendonor $pendonor, array $d, User $petugas): PemeriksaanDonor
    {
        $e = $this->evaluasi($pendonor, $d);

        $p = PemeriksaanDonor::create([
            'pendonor_id' => $pendonor->id, 'diperiksa_oleh' => $petugas->id, 'tanggal' => today(),
            'hemoglobin' => $d['hemoglobin'], 'sistolik' => $d['sistolik'], 'diastolik' => $d['diastolik'],
            'berat_kg' => $d['berat_kg'], 'suhu_tubuh' => $d['suhu_tubuh'], 'nadi' => $d['nadi'],
            'hasil' => $e['hasil'], 'alasan' => implode("\n", $e['alasan']) ?: null, 'catatan' => $d['catatan'] ?? null,
        ]);

        LogTransaksi::catat('pemeriksaan_donor', 'pendonor', $pendonor->id,
            "Pemeriksaan pra-donor {$pendonor->nama} ({$pendonor->kode_donor}): " . ($e['hasil'] === 'layak' ? 'LAYAK' : 'TIDAK LAYAK') . '.',
            ['baru' => ['pemeriksaan_id' => $p->id, 'hasil' => $e['hasil'], 'alasan' => $e['alasan']], 'user' => $petugas]);

        return $p;
    }
}
