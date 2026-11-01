<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Notifikasi;
use App\Models\PanggilanPendonor;
use App\Models\Pendonor;
use App\Models\PermintaanDarah;
use App\Models\User;
use Illuminate\Database\Seeder;

class PanggilanNotifikasiAuditSeeder extends Seeder
{
    public function run(): void
    {
        $petugasUTD = User::where('email', 'admin.pmi@bloodconnect.id')->first();
        $adminRS = User::where('email', 'petugas.rs@bloodconnect.id')->first();
        $pendonorRare = Pendonor::where('nik', '3175050506000005')->first(); // Rian O-
        $reqCito = PermintaanDarah::where('nomor_permintaan', '#REQ-202610-0001')->first();

        // 1. Panggilan Pendonor Darurat (Matching Alert)
        if ($pendonorRare) {
            PanggilanPendonor::firstOrCreate(
                [
                    'pendonor_id' => $pendonorRare->id,
                    'tanggal_panggilan' => now()->subHours(6)->toDateTimeString(),
                ],
                [
                    'permintaan_darah_id' => $reqCito?->id,
                    'metode' => 'WhatsApp',
                    'status_respon' => 'Menunggu',
                    'catatan' => 'Panggilan otomatis Matching Alert: Pasien CITO membutuhkan darah rhesus negatif O-. Pendonor berjarak 4.2 km dari UTD.',
                ]
            );
        }

        // 2. Notifikasi Sistem
        if ($petugasUTD) {
            Notifikasi::firstOrCreate(
                ['judul' => 'Peringatan FEFO: 3 Kantong Mendekati Kedaluwarsa'],
                [
                    'user_id' => $petugasUTD->id,
                    'pesan' => 'Ditemukan 3 kantong darah (PRC & TC) dengan masa simpan kurang dari 5 hari. Prioritaskan alokasi sesuai prinsip FEFO.',
                    'tipe' => 'warning',
                    'is_read' => false,
                    'url' => '/admin/kantong-darah?filter=mendekati_kedaluwarsa',
                ]
            );

            Notifikasi::firstOrCreate(
                ['judul' => 'Permintaan Darah CITO Masuk: RS Siloam'],
                [
                    'user_id' => $petugasUTD->id,
                    'pesan' => 'Permintaan #REQ-202610-0001 untuk Ny. Mariam Susanti membutuhkan 1 kantong PRC O+ darurat.',
                    'tipe' => 'danger',
                    'is_read' => true,
                    'url' => '/admin/permintaan/REQ-202610-0001',
                ]
            );
        }

        if ($adminRS) {
            Notifikasi::firstOrCreate(
                ['judul' => 'Kurir Cold-Chain Sedang Menuju Rumah Sakit'],
                [
                    'user_id' => $adminRS->id,
                    'pesan' => 'Pengiriman surat jalan #SJ-202610-001 oleh kurir Hendra Gunawan (B 1984 UTD) diperkirakan tiba dalam 15 menit. Suhu cooler box: 3.8°C.',
                    'tipe' => 'info',
                    'is_read' => false,
                    'url' => '/rs/pengiriman/SJ-202610-001',
                ]
            );
        }

        // 3. Append-Only Audit Log (NFR-06)
        AuditLog::create([
            'user_id' => $petugasUTD?->id,
            'aktivitas' => 'Verifikasi Uji Saring Lab',
            'entitas' => 'uji_saring',
            'entitas_id' => 1,
            'data_lama' => ['status_kantong' => 'Karantina'],
            'data_baru' => [
                'status_kantong' => 'Ready',
                'nomor_berita_acara' => 'BA-LAB-2026-0001',
                'hasil' => 'Non-Reaktif (Lolos)',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'created_at' => now()->subHours(5),
        ]);

        AuditLog::create([
            'user_id' => $petugasUTD?->id,
            'aktivitas' => 'Alokasi Algoritma FEFO Stok Darah',
            'entitas' => 'alokasi_kantong',
            'entitas_id' => 1,
            'data_lama' => null,
            'data_baru' => [
                'permintaan' => '#REQ-202610-0001',
                'kantong_terpilih' => 'BD-PRC-202610-006',
                'alasan' => 'Kantong lolos skrining dengan masa kedaluwarsa terdekat yang memenuhi kriteria kompatibilitas (FEFO Priority)',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'created_at' => now()->subHours(1),
        ]);
    }
}

