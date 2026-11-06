<?php

namespace Database\Seeders;

use App\Models\AlokasiKantong;
use App\Models\DetailPermintaan;
use App\Models\KantongDarah;
use App\Models\KomponenDarah;
use App\Models\Pengiriman;
use App\Models\PermintaanDarah;
use App\Models\RumahSakit;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermintaanDarahSeeder extends Seeder
{
    public function run(): void
    {
        $rsSiloam = RumahSakit::where('kode_rs', 'RS-001')->first();
        $rsTarakan = RumahSakit::where('kode_rs', 'RS-002')->first();

        $userSiloam = User::where('email', 'petugas.rs@bloodconnect.id')->first();
        $userTarakan = User::where('email', 'admin.tarakan@bloodconnect.id')->first();

        $prc = KomponenDarah::where('kode_komponen', 'PRC')->first();

        // 1. Permintaan Cito RS Siloam Semanggi
        if ($rsSiloam && $userSiloam && $prc) {
            $reqCito = PermintaanDarah::firstOrCreate(
                ['nomor_permintaan' => '#REQ-202610-0001'],
                [
                    'rumah_sakit_id' => $rsSiloam->id,
                    'user_id' => $userSiloam->id,
                    'nama_pasien' => 'Ny. Mariam Susanti (54 th)',
                    'nomor_rekam_medis' => 'RM-SIL-2026-9812',
                    'urgensi' => 'Cito',
                    'status_permintaan' => 'Diproses',
                    'catatan_klinis' => 'Perdarahan masif intra-abdomen pasca operasi laparatomi darurat. Hb 6.2 g/dL.',
                ]
            );

            $detailCito = DetailPermintaan::firstOrCreate(
                [
                    'permintaan_darah_id' => $reqCito->id,
                    'komponen_darah_id' => $prc->id,
                    'golongan_darah' => 'O',
                    'rhesus' => '+',
                ],
                [
                    'jumlah_diminta' => 1,
                    'jumlah_dialokasikan' => 1,
                ]
            );

            // Hubungkan kantong yang status Reserved
            $kantongReserved = KantongDarah::where('kode_batch', 'BD-PRC-202610-006')->first();
            if ($kantongReserved) {
                AlokasiKantong::firstOrCreate(
                    [
                        'detail_permintaan_id' => $detailCito->id,
                        'kantong_darah_id' => $kantongReserved->id,
                    ],
                    [
                        'tanggal_alokasi' => now()->subMinutes(40),
                        'status_alokasi' => 'Reserved',
                    ]
                );
            }

            // Pengiriman Cold-Chain
            Pengiriman::firstOrCreate(
                ['nomor_surat_jalan' => 'SJ-202610-001'],
                [
                    'permintaan_darah_id' => $reqCito->id,
                    'nama_kurir' => 'Hendra Gunawan (Kurir PMI DKI)',
                    'plat_kendaraan' => 'B 1984 UTD',
                    'status_pengiriman' => 'Dalam Perjalanan',
                    'waktu_berangkat' => now()->subMinutes(25),
                    'estimasi_tiba' => now()->addMinutes(15),
                    'suhu_kotak_pendingin' => 3.8,
                    'catatan' => 'Cooler box terkalibrasi cold-chain dengan thermo-logger aktif. Suhu terjaga 3.8°C.',
                ]
            );
        }

        // 2. Permintaan Rutin RSUD Tarakan
        if ($rsTarakan && $userTarakan && $prc) {
            $reqRutin = PermintaanDarah::firstOrCreate(
                ['nomor_permintaan' => '#REQ-202610-0002'],
                [
                    'rumah_sakit_id' => $rsTarakan->id,
                    'user_id' => $userTarakan->id,
                    'nama_pasien' => 'Tn. Hendra Wijaya (42 th)',
                    'nomor_rekam_medis' => 'RM-TRK-2026-4431',
                    'urgensi' => 'Rutin',
                    'status_permintaan' => 'Menunggu Persetujuan',
                    'catatan_klinis' => 'Persiapan tindakan elektif operasi ortopedi esok hari. Kebutuhan 2 kantong PRC A+.',
                ]
            );

            DetailPermintaan::firstOrCreate(
                [
                    'permintaan_darah_id' => $reqRutin->id,
                    'komponen_darah_id' => $prc->id,
                    'golongan_darah' => 'A',
                    'rhesus' => '+',
                ],
                [
                    'jumlah_diminta' => 2,
                    'jumlah_dialokasikan' => 0,
                ]
            );
        }
    }
}

