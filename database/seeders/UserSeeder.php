<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Admin PMI',  'admin.pmi@bloodconnect.id',  'PMI Pusat',    'admin_pmi'],
            ['Petugas RS', 'petugas.rs@bloodconnect.id', 'RSUD Jakarta', 'petugas_rs'],
            ['Pendonor',   'pendonor@bloodconnect.id',   'Umum',         'pendonor'],
        ];

        foreach ($data as [$name, $email, $instansi, $role]) {
            // withTrashed: akun nonaktif tetap ditemukan lalu diaktifkan kembali
            $user = User::withTrashed()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'instansi' => $instansi, 'password' => 'password123']
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $user->syncRoles([$role]);
        }
    }
}
