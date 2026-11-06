<?php

namespace Database\Seeders;

use App\Models\Pendonor;
use App\Models\User;
use Illuminate\Database\Seeder;

class PendonorSeeder extends Seeder
{
    public function run(): void
    {
        $userPendonor = User::where('email', 'pendonor@bloodconnect.id')->first();

        $dataPendonor = [
            [
                'user_id' => $userPendonor?->id,
                'kode_pendonor' => '#D-000001',
                'nik' => '3171012304950001',
                'nama' => 'Ahmad Pratama',
                'tanggal_lahir' => '1995-04-23',
                'jenis_kelamin' => 'Laki-laki',
                'golongan_darah' => 'A',
                'rhesus' => '+',
                'telepon' => '081234567890',
                'kota' => 'Jakarta Selatan',
                'alamat' => 'Jl. Tebet Timur Dalam No. 12, Tebet, Jakarta Selatan',
                'tanggal_terakhir_donor' => now()->subMonths(4)->toDateString(),
                'status_kelayakan' => 'Layak',
            ],
            [
                'user_id' => null,
                'kode_pendonor' => '#D-000002',
                'nik' => '3172021508980002',
                'nama' => 'Siti Nurhaliza',
                'tanggal_lahir' => '1998-08-15',
                'jenis_kelamin' => 'Perempuan',
                'golongan_darah' => 'B',
                'rhesus' => '+',
                'telepon' => '081298765432',
                'kota' => 'Jakarta Pusat',
                'alamat' => 'Jl. Salemba Raya No. 45, Senen, Jakarta Pusat',
                'tanggal_terakhir_donor' => now()->subMonths(3)->toDateString(),
                'status_kelayakan' => 'Layak',
            ],
            [
                'user_id' => null,
                'kode_pendonor' => '#D-000003',
                'nik' => '3173031012920003',
                'nama' => 'Kevin Sanjaya',
                'tanggal_lahir' => '1992-12-10',
                'jenis_kelamin' => 'Laki-laki',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'telepon' => '081311223344',
                'kota' => 'Jakarta Barat',
                'alamat' => 'Jl. Kemanggisan Utama No. 8, Palmerah, Jakarta Barat',
                'tanggal_terakhir_donor' => now()->subMonths(5)->toDateString(),
                'status_kelayakan' => 'Layak',
            ],
            [
                'user_id' => null,
                'kode_pendonor' => '#D-000004',
                'nik' => '3174042203970004',
                'nama' => 'Dian Sastro',
                'tanggal_lahir' => '1997-03-22',
                'jenis_kelamin' => 'Perempuan',
                'golongan_darah' => 'AB',
                'rhesus' => '+',
                'telepon' => '081377889900',
                'kota' => 'Jakarta Selatan',
                'alamat' => 'Jl. Senopati No. 18, Kebayoran Baru, Jakarta Selatan',
                'tanggal_terakhir_donor' => now()->subMonths(6)->toDateString(),
                'status_kelayakan' => 'Layak',
            ],
            [
                'user_id' => null,
                'kode_pendonor' => '#D-000005',
                'nik' => '3175050506000005',
                'nama' => 'Rian Ardianto',
                'tanggal_lahir' => '2000-06-05',
                'jenis_kelamin' => 'Laki-laki',
                'golongan_darah' => 'O',
                'rhesus' => '-',
                'telepon' => '081555667788',
                'kota' => 'Jakarta Timur',
                'alamat' => 'Jl. Otista Raya No. 99, Jatinegara, Jakarta Timur',
                'tanggal_terakhir_donor' => now()->subWeeks(2)->toDateString(), // Baru 2 minggu lalu
                'status_kelayakan' => 'Belum Layak', // Belum melewati interval 60 hari
            ],
            [
                'user_id' => null,
                'kode_pendonor' => '#D-000006',
                'nik' => '3176061402940006',
                'nama' => 'Maya Indah',
                'tanggal_lahir' => '1994-02-14',
                'jenis_kelamin' => 'Perempuan',
                'golongan_darah' => 'A',
                'rhesus' => '-',
                'telepon' => '081712344321',
                'kota' => 'Jakarta Utara',
                'alamat' => 'Jl. Pluit Raya No. 27, Penjaringan, Jakarta Utara',
                'tanggal_terakhir_donor' => null, // Pendonor pemula
                'status_kelayakan' => 'Layak',
            ],
        ];

        foreach ($dataPendonor as $item) {
            Pendonor::firstOrCreate(['kode_pendonor' => $item['kode_pendonor']], $item);
        }
    }
}

