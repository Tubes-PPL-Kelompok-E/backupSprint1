<?php

namespace Tests\Feature;

use App\Models\KantongDarah;
use App\Models\Pendonor;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/** Bantuan bersama untuk tes fitur. */
trait Concerns
{
    protected function seedPeran(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function akun(string $role, ?string $instansi = 'Test'): User
    {
        $u = User::create(['name' => ucfirst($role), 'email' => $role . uniqid() . '@test.id', 'instansi' => $instansi, 'password' => 'password123']);
        $u->assignRole($role);

        return $u;
    }

    protected function pendonor(array $o = [], ?User $akun = null): Pendonor
    {
        static $n = 0;
        $n++;

        return Pendonor::create($o + [
            'user_id' => $akun?->id, 'kode_donor' => sprintf('D-%06d', $n), 'nama' => "Donor $n",
            'tanggal_lahir' => '1995-01-01', 'jenis_kelamin' => 'L', 'golongan' => 'O', 'rhesus' => 'pos', 'kota' => 'Jakarta Pusat',
        ]);
    }

    /** Buat kantong siap pakai (Ready) dengan sisa masa simpan $sisaHari. */
    protected function kantong(array $o = [], int $sisaHari = 20): KantongDarah
    {
        static $n = 0;
        $n++;

        return KantongDarah::create($o + [
            'kode_batch' => 'B-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT) . uniqid(), 'tanggal_donor' => today()->subDays(5),
            'volume_ml' => 350, 'komponen' => 'prc', 'golongan' => 'A', 'rhesus' => 'pos',
            'tanggal_kedaluwarsa' => today()->addDays($sisaHari), 'status' => 'ready', 'lokasi_simpan' => 'Cold Room 1',
        ]);
    }
}
