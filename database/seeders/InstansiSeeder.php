<?php

namespace Database\Seeders;

use App\Models\Instansi;
use Illuminate\Database\Seeder;

class InstansiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['PMI-001', 'UTD PMI Kota', 'pmi', 'Jl. Kramat Raya No. 47', 'Jakarta Pusat', '021-3906666', 'utd@pmi-kota.id'],
            ['RS-001', 'RS Siloam Semanggi', 'rumah_sakit', 'Jl. Jend. Sudirman Kav. 5', 'Jakarta Selatan', '021-29962888', null],
            ['RS-002', 'RSUD Jakarta', 'rumah_sakit', 'Jl. Merdeka No. 10', 'Jakarta Pusat', '021-5551234', null],
        ];

        foreach ($data as [$kode, $nama, $tipe, $alamat, $kota, $telepon, $email]) {
            $instansi = Instansi::withTrashed()->firstOrCreate(
                ['kode' => $kode],
                compact('nama', 'tipe', 'alamat', 'kota', 'telepon', 'email')
            );

            if ($instansi->trashed()) {
                $instansi->restore();
            }
        }
    }
}
