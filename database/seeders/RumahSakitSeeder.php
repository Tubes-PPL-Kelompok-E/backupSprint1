<?php

namespace Database\Seeders;

use App\Models\RumahSakit;
use Illuminate\Database\Seeder;

class RumahSakitSeeder extends Seeder
{
    public function run(): void
    {
        $dataRS = [
            [
                'kode_rs' => 'RS-001',
                'nama_rs' => 'RS Siloam Semanggi',
                'telepon' => '021-29962888',
                'email'   => 'info.siloam@bloodconnect.id',
                'alamat'  => 'Jl. Garnisun No. 2-3, Karet Semanggi, Setiabudi, Jakarta Selatan',
            ],
            [
                'kode_rs' => 'RS-002',
                'nama_rs' => 'RSUD Tarakan Jakarta',
                'telepon' => '021-3842959',
                'email'   => 'pelayanan.tarakan@bloodconnect.id',
                'alamat'  => 'Jl. Kyai Caringin No. 7, Cideng, Gambir, Jakarta Pusat',
            ],
            [
                'kode_rs' => 'RS-003',
                'nama_rs' => 'RSUP Nasional Dr. Cipto Mangunkusumo (RSCM)',
                'telepon' => '021-1500135',
                'email'   => 'bankdarah.rscm@bloodconnect.id',
                'alamat'  => 'Jl. Diponegoro No. 71, Kenari, Senen, Jakarta Pusat',
            ],
            [
                'kode_rs' => 'RS-004',
                'nama_rs' => 'RSUP Fatmawati',
                'telepon' => '021-7660552',
                'email'   => 'pelayanan.fatmawati@bloodconnect.id',
                'alamat'  => 'Jl. RS. Fatmawati Raya No. 4, Cilandak Barat, Jakarta Selatan',
            ],
        ];

        foreach ($dataRS as $rs) {
            RumahSakit::firstOrCreate(['kode_rs' => $rs['kode_rs']], $rs);
        }
    }
}

