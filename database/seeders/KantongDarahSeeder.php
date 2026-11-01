<?php

namespace Database\Seeders;

use App\Models\Donasi;
use App\Models\KantongDarah;
use App\Models\KegiatanDonor;
use App\Models\KomponenDarah;
use App\Models\Pendonor;
use App\Models\UjiSaring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KantongDarahSeeder extends Seeder
{
    public function run(): void
    {
        $petugasLab = User::where('email', 'admin.pmi@bloodconnect.id')->first();
        $prc = KomponenDarah::where('kode_komponen', 'PRC')->first();
        $wb  = KomponenDarah::where('kode_komponen', 'WB')->first();
        $tc  = KomponenDarah::where('kode_komponen', 'TC')->first();
        $ffp = KomponenDarah::where('kode_komponen', 'FFP')->first();

        $pendonorO  = Pendonor::where('nik', '3173031012920003')->first(); // Kevin O+
        $pendonorA  = Pendonor::where('nik', '3171012304950001')->first(); // Ahmad A+
        $pendonorB  = Pendonor::where('nik', '3172021508980002')->first(); // Siti B+
        $pendonorAB = Pendonor::where('nik', '3174042203970004')->first(); // Dian AB+
        $pendonorOneg = Pendonor::where('nik', '3175050506000005')->first(); // Rian O-

        $eventSelesai = KegiatanDonor::where('kode_kegiatan', 'EVT-202610-003')->first();

        // 1. Buat Riwayat Donasi
        $donasi1 = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202609-001'],
            [
                'pendonor_id' => $pendonorO->id,
                'kegiatan_id' => null,
                'tanggal_donasi' => now()->subDays(38)->toDateString(),
                'volume_ml' => 450,
                'status_donasi' => 'Selesai',
                'catatan' => 'Donor rutin di Gedung UTD Pusat',
            ]
        );

        $donasi2 = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202609-002'],
            [
                'pendonor_id' => $pendonorA->id,
                'kegiatan_id' => null,
                'tanggal_donasi' => now()->subDays(39)->toDateString(),
                'volume_ml' => 350,
                'status_donasi' => 'Selesai',
                'catatan' => 'Donor sukarela',
            ]
        );

        $donasi3 = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202610-003'],
            [
                'pendonor_id' => $pendonorB->id,
                'kegiatan_id' => $eventSelesai?->id,
                'tanggal_donasi' => now()->subDays(4)->toDateString(),
                'volume_ml' => 450,
                'status_donasi' => 'Selesai',
                'catatan' => 'Aferesis konsentrat trombosit',
            ]
        );

        $donasi4 = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202610-004'],
            [
                'pendonor_id' => $pendonorO->id,
                'kegiatan_id' => $eventSelesai?->id,
                'tanggal_donasi' => now()->subDays(10)->toDateString(),
                'volume_ml' => 450,
                'status_donasi' => 'Selesai',
                'catatan' => 'Donor mobile unit SCBD',
            ]
        );

        $donasi5 = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202610-005'],
            [
                'pendonor_id' => $pendonorOneg->id,
                'kegiatan_id' => null,
                'tanggal_donasi' => now()->subDays(8)->toDateString(),
                'volume_ml' => 350,
                'status_donasi' => 'Selesai',
                'catatan' => 'Donor khusus rhesus negatif langka',
            ]
        );

        $donasiKarantina = Donasi::firstOrCreate(
            ['kode_donasi' => 'DON-202610-006'],
            [
                'pendonor_id' => $pendonorA->id,
                'kegiatan_id' => null,
                'tanggal_donasi' => now()->toDateString(),
                'volume_ml' => 350,
                'status_donasi' => 'Selesai',
                'catatan' => 'Donasi hari ini, menunggu uji saring lab',
            ]
        );

        // 2. Daftar Kantong Darah
        $kantongList = [
            // === KATEGORI 1: READY & MENDEKATI KEDALUWARSA (FEFO ALERT) ===
            [
                'donasi_id' => $donasi1->id,
                'komponen_darah_id' => $prc->id,
                'kode_batch' => 'BD-PRC-202609-001',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(38)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(38)->addDays($prc->masa_simpan_hari)->toDateString(), // Sisa 4 hari!
                'lokasi_simpan' => 'Chamber A-01 (Rak 1)',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi2->id,
                'komponen_darah_id' => $prc->id,
                'kode_batch' => 'BD-PRC-202609-002',
                'golongan_darah' => 'A',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(39)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(39)->addDays($prc->masa_simpan_hari)->toDateString(), // Sisa 3 hari!
                'lokasi_simpan' => 'Chamber A-01 (Rak 2)',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi3->id,
                'komponen_darah_id' => $tc->id,
                'kode_batch' => 'BD-TC-202610-001',
                'golongan_darah' => 'B',
                'rhesus' => '+',
                'volume' => 250,
                'tanggal_donor' => now()->subDays(4)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(4)->addDays($tc->masa_simpan_hari)->toDateString(), // Sisa 1 hari! (TC 5 hari)
                'lokasi_simpan' => 'Platelet Agitator Unit 1',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],

            // === KATEGORI 2: READY SEGAR (> 14 HARI) ===
            [
                'donasi_id' => $donasi4->id,
                'komponen_darah_id' => $prc->id,
                'kode_batch' => 'BD-PRC-202610-003',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(10)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(10)->addDays($prc->masa_simpan_hari)->toDateString(), // Sisa 32 hari
                'lokasi_simpan' => 'Chamber A-02 (Rak 3)',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi5->id,
                'komponen_darah_id' => $prc->id,
                'kode_batch' => 'BD-PRC-202610-004',
                'golongan_darah' => 'O',
                'rhesus' => '-',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(8)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(8)->addDays($prc->masa_simpan_hari)->toDateString(), // Sisa 34 hari
                'lokasi_simpan' => 'Chamber Rare Blood Unit R-1',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi4->id,
                'komponen_darah_id' => $ffp->id,
                'kode_batch' => 'BD-FFP-202610-001',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'volume' => 200,
                'tanggal_donor' => now()->subDays(10)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(10)->addDays($ffp->masa_simpan_hari)->toDateString(), // Sisa 355 hari
                'lokasi_simpan' => 'Deep Freezer -25°C (Box 04)',
                'status_kantong' => 'Ready',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],

            // === KATEGORI 3: STATUS LAIN (KARANTINA, RESERVED, KEDALUWARSA, DIMUSNAHKAN) ===
            [
                'donasi_id' => $donasiKarantina->id,
                'komponen_darah_id' => $wb->id,
                'kode_batch' => 'BD-WB-202610-005',
                'golongan_darah' => 'A',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->toDateString(),
                'tanggal_kedaluwarsa' => now()->addDays($wb->masa_simpan_hari)->toDateString(),
                'lokasi_simpan' => 'Quarantine Cold Room Q-01',
                'status_kantong' => 'Karantina',
                'screening' => null, // Belum diuji saring
            ],
            [
                'donasi_id' => $donasi1->id,
                'komponen_darah_id' => $prc->id,
                'kode_batch' => 'BD-PRC-202610-006',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(14)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(14)->addDays($prc->masa_simpan_hari)->toDateString(),
                'lokasi_simpan' => 'Dispatch Staging Rack B-01',
                'status_kantong' => 'Reserved', // Sudah dialokasikan untuk permintaan CITO RS Siloam
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi3->id,
                'komponen_darah_id' => $tc->id,
                'kode_batch' => 'BD-TC-202610-007',
                'golongan_darah' => 'O',
                'rhesus' => '+',
                'volume' => 250,
                'tanggal_donor' => now()->subDays(8)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(8)->addDays($tc->masa_simpan_hari)->toDateString(), // Sudah lewat 3 hari lalu!
                'lokasi_simpan' => 'Expired Quarantine Chamber D-01',
                'status_kantong' => 'Kedaluwarsa',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Non-Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Lolos'],
            ],
            [
                'donasi_id' => $donasi2->id,
                'komponen_darah_id' => $wb->id,
                'kode_batch' => 'BD-WB-202610-008',
                'golongan_darah' => 'A',
                'rhesus' => '+',
                'volume' => 350,
                'tanggal_donor' => now()->subDays(20)->toDateString(),
                'tanggal_kedaluwarsa' => now()->subDays(20)->addDays($wb->masa_simpan_hari)->toDateString(),
                'lokasi_simpan' => 'Incinerator Waste Storage',
                'status_kantong' => 'Dimusnahkan',
                'screening' => ['hiv' => 'Non-Reaktif', 'hbsag' => 'Reaktif', 'hcv' => 'Non-Reaktif', 'sifilis' => 'Non-Reaktif', 'kesimpulan' => 'Tidak Lolos'],
            ],
        ];

        $screeningCounter = 1;

        foreach ($kantongList as $k) {
            $screeningData = $k['screening'];
            unset($k['screening']);

            $kantong = KantongDarah::firstOrCreate(
                ['kode_batch' => $k['kode_batch']],
                $k
            );

            // Simpan uji saring jika sudah dilakukan
            if ($screeningData && $petugasLab) {
                $noBA = sprintf('BA-LAB-2026-%04d', $screeningCounter++);
                UjiSaring::firstOrCreate(
                    ['kantong_darah_id' => $kantong->id],
                    [
                        'petugas_id' => $petugasLab->id,
                        'tanggal_uji' => Carbon::parse($kantong->tanggal_donor)->addDay()->toDateString(),
                        'hiv' => $screeningData['hiv'],
                        'hbsag' => $screeningData['hbsag'],
                        'hcv' => $screeningData['hcv'],
                        'sifilis' => $screeningData['sifilis'],
                        'kesimpulan' => $screeningData['kesimpulan'],
                        'nomor_berita_acara' => $noBA,
                        'berkas_berita_acara' => "documents/berita-acara/{$noBA}.pdf",
                        'keterangan' => $screeningData['kesimpulan'] === 'Lolos'
                            ? 'Lolos 4 parameter IMLTD standar Permenkes 91/2015'
                            : 'Gagal screening lab: Sampel Reaktif HBsAg. Kantong diisolasi untuk dimusnahkan.',
                    ]
                );
            }
        }
    }
}

