<?php

namespace Database\Seeders;

use App\Models\KomponenDarah;
use Illuminate\Database\Seeder;

class KomponenDarahSeeder extends Seeder
{
    public function run(): void
    {
        $komponen = [
            [
                'kode_komponen' => 'PRC',
                'nama_komponen' => 'Packed Red Cells (PRC)',
                'masa_simpan_hari' => 42,
                'suhu_simpan_min' => 2.0,
                'suhu_simpan_max' => 6.0,
                'deskripsi' => 'Sel darah merah pekat hasil pemisahan whole blood, diindikasikan untuk pasien anemia kronis/akut dan perdarahan bedah tanpa defisit volume intravaskular.',
            ],
            [
                'kode_komponen' => 'WB',
                'nama_komponen' => 'Whole Blood (WB)',
                'masa_simpan_hari' => 35,
                'suhu_simpan_min' => 2.0,
                'suhu_simpan_max' => 6.0,
                'deskripsi' => 'Darah lengkap mengandung eritrosit, leukosit, plasma, dan trombosit dalam antikoagulan CPDA-1, digunakan untuk perdarahan masif akut dan transfusi tukar.',
            ],
            [
                'kode_komponen' => 'TC',
                'nama_komponen' => 'Thrombocyte Concentrate (TC)',
                'masa_simpan_hari' => 5,
                'suhu_simpan_min' => 20.0,
                'suhu_simpan_max' => 24.0,
                'deskripsi' => 'Konsentrat trombosit yang disimpan dalam platelet agitator berputar konstan, untuk pasien trombositopenia akut, DBD berat, dan kemoterapi leukemia.',
            ],
            [
                'kode_komponen' => 'FFP',
                'nama_komponen' => 'Fresh Frozen Plasma (FFP)',
                'masa_simpan_hari' => 365,
                'suhu_simpan_min' => -25.0,
                'suhu_simpan_max' => -18.0,
                'deskripsi' => 'Plasma segar yang dibekukan dalam waktu 8 jam pasca donasi, kaya faktor pembekuan darah labil (Faktor V & VIII) untuk defisiensi koagulasi ganda.',
            ],
            [
                'kode_komponen' => 'AHF',
                'nama_komponen' => 'Cryoprecipitate (AHF)',
                'masa_simpan_hari' => 365,
                'suhu_simpan_min' => -25.0,
                'suhu_simpan_max' => -18.0,
                'deskripsi' => 'Presipitat dingin dari FFP kaya Fibrinogen, Faktor VIII, Faktor von Willebrand, dan Faktor XIII untuk Hemofilia A dan hipofibrinogenemia.',
            ],
        ];

        foreach ($komponen as $item) {
            KomponenDarah::firstOrCreate(
                ['kode_komponen' => $item['kode_komponen']],
                $item
            );
        }
    }
}

