<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            RumahSakitSeeder::class,
            UserSeeder::class,
            KomponenDarahSeeder::class,
            PendonorSeeder::class,
            KegiatanDonorSeeder::class,
            KantongDarahSeeder::class,
            PermintaanDarahSeeder::class,
            PanggilanNotifikasiAuditSeeder::class,
        ]);
    }
}
