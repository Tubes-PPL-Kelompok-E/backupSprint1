<?php

namespace Database\Seeders;

use App\Models\RumahSakit;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rsSiloam = RumahSakit::where('kode_rs', 'RS-001')->first();
        $rsTarakan = RumahSakit::where('kode_rs', 'RS-002')->first();

        $users = [
            [
                'name' => 'dr. Sarah Wijaya, Sp.PK',
                'email' => 'admin.pmi@bloodconnect.id',
                'instansi' => 'UTD PMI DKI Jakarta',
                'institution_id' => null,
                'roles' => ['admin_pmi', 'Petugas Bank Darah'],
                'password' => 'password123',
            ],
            [
                'name' => 'Budi Santoso, S.Kep',
                'email' => 'petugas.rs@bloodconnect.id',
                'instansi' => 'RS Siloam Semanggi',
                'institution_id' => $rsSiloam?->id,
                'roles' => ['petugas_rs', 'Admin Rumah Sakit'],
                'password' => 'password123',
            ],
            [
                'name' => 'Ns. Ratna Sari, S.Tr.Kep',
                'email' => 'admin.tarakan@bloodconnect.id',
                'instansi' => 'RSUD Tarakan Jakarta',
                'institution_id' => $rsTarakan?->id,
                'roles' => ['petugas_rs', 'Admin Rumah Sakit'],
                'password' => 'password123',
            ],
            [
                'name' => 'Ahmad Pratama',
                'email' => 'pendonor@bloodconnect.id',
                'instansi' => 'Masyarakat Umum',
                'institution_id' => null,
                'roles' => ['pendonor', 'Pendonor'],
                'password' => 'password123',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'instansi' => $userData['instansi'],
                    'institution_id' => $userData['institution_id'],
                    'password' => $userData['password'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles($userData['roles']);
        }
    }
}
