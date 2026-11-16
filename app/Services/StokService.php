<?php

namespace App\Services;

use App\Models\BatasMinimumStok;
use App\Models\KantongDarah;
use Illuminate\Support\Collection;

/**
 * Logika stok untuk dashboard: ringkasan, stok per golongan, dan FEFO Alert.
 * Dipakai bersama oleh halaman dashboard dan endpoint polling JSON.
 */
class StokService
{
    /** Kantong siap pakai per golongan, rhesus, komponen: ["A|pos|prc" => 12, ...] */
    public function stokSiapPerKombinasi(): Collection
    {
        return KantongDarah::siapPakai()
            ->selectRaw('golongan, rhesus, komponen, count(*) as total')
            ->groupBy('golongan', 'rhesus', 'komponen')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->golongan}|{$r->rhesus}|{$r->komponen}" => (int) $r->total]);
    }

    /**
     * Stok siap per golongan + rhesus (8 baris) lengkap dengan batas minimum dan penanda kritis.
     * Tanpa filter komponen: jumlah semua komponen dibandingkan dengan jumlah batas keempat komponen.
     * Dengan filter komponen: hanya komponen itu yang dibandingkan dengan batasnya sendiri.
     */
    public function perGolongan(?string $komponen = null): array
    {
        $stok = $this->stokSiapPerKombinasi();
        $batas = BatasMinimumStok::peta();
        $komponen = in_array($komponen, BatasMinimumStok::KOMPONEN, true) ? $komponen : null;
        $daftarKomponen = $komponen ? [$komponen] : BatasMinimumStok::KOMPONEN;

        $baris = [];
        foreach (BatasMinimumStok::GOLONGAN as $g) {
            foreach (BatasMinimumStok::RHESUS as $r => $simbol) {
                $jumlah = 0;
                $minimum = 0;
                foreach ($daftarKomponen as $k) {
                    $jumlah += $stok["$g|$r|$k"] ?? 0;
                    $minimum += $batas["$g|$r|$k"];
                }

                $baris[] = [
                    'golongan' => $g,
                    'rhesus'   => $r,
                    'label'    => "$g ($simbol)",
                    'jumlah'   => $jumlah,
                    'minimum'  => $minimum,
                    'kritis'   => $minimum > 0 && $jumlah < $minimum,
                ];
            }
        }

        return $baris;
    }

    /** Kantong Ready dengan sisa masa simpan kurang dari ambang (FEFO Alert), kedaluwarsa terdekat dulu. */
    public function queryAlertKedaluwarsa()
    {
        $ambang = config('bloodconnect.ambang_kedaluwarsa_hari');

        return KantongDarah::siapPakai()
            ->whereDate('tanggal_kedaluwarsa', '<', today()->addDays($ambang))
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id');
    }

    /** Data dashboard lengkap (halaman & endpoint JSON polling). */
    public function dashboard(?string $komponen = null): array
    {
        $baris = $this->perGolongan($komponen);
        $alert = $this->queryAlertKedaluwarsa();

        return [
            'ada_data'  => KantongDarah::query()->exists(),
            'ringkasan' => [
                'siap_pakai'         => KantongDarah::siapPakai()->count(),
                'segera_kedaluwarsa' => (clone $alert)->count(),
            ],
            'ambang_hari' => config('bloodconnect.ambang_kedaluwarsa_hari'),
            'komponen'    => in_array($komponen, BatasMinimumStok::KOMPONEN, true) ? $komponen : null,
            'stok'        => $baris,
            'kritis'      => collect($baris)->where('kritis', true)->values()->all(),
            'alert'       => $alert->limit(5)->get()->map(fn (KantongDarah $k) => [
                'id'          => $k->id,
                'kode'        => $k->kode_batch,
                'golongan'    => $k->golonganLengkap(),
                'komponen'    => strtoupper($k->komponen),
                'sisa_hari'   => $k->sisaHari(),
                'kedaluwarsa' => $k->tanggal_kedaluwarsa->translatedFormat('d F Y'),
            ])->all(),
            'diperbarui' => now()->format('H:i:s'),
        ];
    }
}
