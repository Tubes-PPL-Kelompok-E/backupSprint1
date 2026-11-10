<?php

namespace Database\Seeders;

use App\Models\LokasiPenyimpanan;
use Illuminate\Database\Seeder;

class LokasiSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Karantina',      'karantina', 200, null, null, 'Kantong menunggu hasil uji saring'],
            ['Cold Room 1',    'cold_room', 400, 2.0, 6.0, 'PRC dan whole blood'],
            ['Cold Room 2',    'cold_room', 300, 2.0, 6.0, 'Cadangan'],
            ['Freezer Plasma', 'freezer',   150, -40.0, -25.0, 'FFP'],
            ['Agitator',       'agitator',  60, 20.0, 24.0, 'Trombosit (TC)'],
        ] as [$nama, $jenis, $kap, $min, $maks, $ket]) {
            LokasiPenyimpanan::firstOrCreate(['nama' => $nama], ['jenis' => $jenis, 'kapasitas' => $kap, 'suhu_min' => $min, 'suhu_maks' => $maks, 'aktif' => true, 'keterangan' => $ket]);
        }
    }
}
