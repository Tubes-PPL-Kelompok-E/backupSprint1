<?php

namespace Database\Seeders;

use App\Models\KegiatanDonor;
use App\Models\PendaftaranKegiatan;
use App\Models\Pendonor;
use Illuminate\Database\Seeder;

class KegiatanDonorSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'kode_kegiatan' => 'EVT-202611-001',
                'judul' => 'Aksi Donor Darah Peduli Sesama - Mall Grand Indonesia',
                'deskripsi' => 'Program mobile unit UTD bekerjasama dengan Manajemen Grand Indonesia. Terbuka untuk umum dengan target 100 kantong darah.',
                'tanggal' => now()->addDays(5)->toDateString(),
                'jam_mulai' => '09:00:00',
                'jam_selesai' => '15:00:00',
                'kuota' => 100,
                'nama_tempat' => 'Main Atrium East Mall Grand Indonesia',
                'alamat' => 'Jl. M.H. Thamrin No. 1, Menteng, Jakarta Pusat',
                'status' => 'Terbuka',
            ],
            [
                'kode_kegiatan' => 'EVT-202611-002',
                'judul' => 'Blood Donation Day - Universitas Indonesia Salemba',
                'deskripsi' => 'Aksi donor darah mahasiswa dan sivitas akademika UI bekerjasama dengan UTD PMI.',
                'tanggal' => now()->addDays(12)->toDateString(),
                'jam_mulai' => '08:30:00',
                'jam_selesai' => '14:00:00',
                'kuota' => 75,
                'nama_tempat' => 'Aula Fakultas Kedokteran UI Salemba',
                'alamat' => 'Jl. Salemba Raya No. 6, Senen, Jakarta Pusat',
                'status' => 'Terbuka',
            ],
            [
                'kode_kegiatan' => 'EVT-202610-003',
                'judul' => 'Corporate CSR Blood Drive - SCBD Sudirman',
                'deskripsi' => 'Donor darah korporasi area perkantoran SCBD Jakarta Selatan. Kegiatan telah selesai.',
                'tanggal' => now()->subDays(10)->toDateString(),
                'jam_mulai' => '09:00:00',
                'jam_selesai' => '16:00:00',
                'kuota' => 150,
                'nama_tempat' => 'Lobby Pacific Century Place SCBD',
                'alamat' => 'Jl. Jend. Sudirman Kav 52-53, Kebayoran Baru, Jakarta Selatan',
                'status' => 'Ditutup',
            ],
        ];

        foreach ($events as $eventData) {
            $event = KegiatanDonor::firstOrCreate(
                ['kode_kegiatan' => $eventData['kode_kegiatan']],
                $eventData
            );

            // Daftarkan dummy pendonor ke kegiatan terbuka
            if ($event->kode_kegiatan === 'EVT-202611-001') {
                $p1 = Pendonor::where('kode_pendonor', '#D-000001')->first();
                $p2 = Pendonor::where('kode_pendonor', '#D-000002')->first();

                if ($p1) {
                    PendaftaranKegiatan::firstOrCreate(
                        ['kegiatan_id' => $event->id, 'pendonor_id' => $p1->id],
                        ['tanggal_pendaftaran' => now()->subDay(), 'status_kehadiran' => 'Belum Hadir']
                    );
                }
                if ($p2) {
                    PendaftaranKegiatan::firstOrCreate(
                        ['kegiatan_id' => $event->id, 'pendonor_id' => $p2->id],
                        ['tanggal_pendaftaran' => now()->subHours(5), 'status_kehadiran' => 'Belum Hadir']
                    );
                }
            }
        }
    }
}

