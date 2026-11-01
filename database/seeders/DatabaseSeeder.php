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
<<<<<<< HEAD
        $this->call([RoleSeeder::class, UserSeeder::class, InstansiSeeder::class]);
=======
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
>>>>>>> 97273f945f0e085eb6ccceff1bb4058df17dfc78
    }
}
