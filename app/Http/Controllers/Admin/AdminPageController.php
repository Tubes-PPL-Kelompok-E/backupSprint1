<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermintaanDarah;
use App\Models\User;

/**
 * Halaman Admin UTD PMI. Angka dashboard, Laporan, dan jadwal kegiatan masih contoh (dummy).
 * Stok, uji saring, dan permintaan RS memakai database.
 * Data pendonor di halaman Kegiatan Donor diambil dari database.
 */
class AdminPageController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'title'  => 'Dashboard Admin UTD PMI',
            'stok'   => [['A', 340], ['B', 210], ['AB', 45], ['O', 280]],
            'min'    => 50,
            'pending' => PermintaanDarah::menunggu()->count(),   // dari database
            'alerts' => [
                ['BATCH-20260921-A01', 'Gol. A (+) • Sisa 4 Kantong PRC', 'Exp: 3 Hari', 'Tanggal Expired: 09 Oktober 2026', 'red'],
                ['BATCH-20260925-O02', 'Gol. O (+) • Sisa 2 Kantong WB', 'Exp: 5 Hari', 'Tanggal Expired: 11 Oktober 2026', 'amber'],
            ],
        ]);
    }

    public function stok()
    {
        // [urutan, batch, donor, gol, komponen, volume, kedaluwarsa, sisa, tone, lokasi, status]
        $batches = [
            [1, 'BATCH-20260921-A01', 'Raffi',   'A (+)',  'PRC', '350 ml', '12-12-2025', 'KADALUWARSA', 'expired', 'Karantina', 'Karantina'],
            [2, 'BATCH-20260922-A02', 'Ilham',   'AB (+)', 'PRC', '350 ml', '13-11-2026', '2 Hari Lagi', 'warn', 'Cold Room 1 - Rak A-02', 'Ready'],
            [3, 'BATCH-20261001-A01', 'Fahrozi', 'B (+)',  'PRC', '350 ml', '01-12-2026', '8 Hari Lagi', 'warn', 'Cold Room 1 - Rak A-02', 'Reserved'],
            [4, 'BATCH-20261002-A02', 'Rombong', 'O (+)',  'PRC', '350 ml', '2026-10-19', '2 Hari Lagi', 'warn', 'Cold Room 1 - Rak A-02', 'Reserved'],
            [5, 'BATCH-20261103-A01', 'Darsano', 'AB (+)', 'PRC', '350 ml', '2026-10-19', '2 Hari Lagi', 'warn', 'Cold Room 1 - Rak A-02', 'Reserved'],
            [6, 'BATCH-20261104-A02', 'Fahrozi', 'A (+)',  'PRC', '350 ml', '2026-10-19', '2 Hari Lagi', 'warn', 'Cold Room 1 - Rak A-02', 'Reserved'],
        ];

        return view('admin.stok', ['title' => 'Stok Darah', 'batches' => $batches]);
    }

    public function laporan()
    {
        // [golongan, masuk, keluar, kedaluwarsa, dimusnahkan, stok siap]
        $rekap = [
            ['Gol. A (+)', 145, 98, 6, 4, 340],
            ['Gol. B (+)', 110, 75, 4, 2, 210],
            ['Gol. AB (+)', 25, 38, 3, 1, 45],
            ['Gol. O (+)', 180, 120, 5, 3, 280],
            ['Rhesus Negatif (-)', 12, 10, 0, 0, 18],
        ];

        $audit = [
            ['2026-10-27 08:12', 'Admin UTD PMI Kota', 'Input darah masuk', 'BATCH-20261104-A02'],
            ['2026-10-27 08:40', 'Admin UTD PMI Kota', 'Validasi & alokasi FEFO', 'REQ-2026-1002'],
            ['2026-10-26 15:05', 'Petugas RS Siloam', 'Konfirmasi penerimaan', 'REQ-2026-0988'],
            ['2026-10-26 10:21', 'Admin UTD PMI Kota', 'Catat pemusnahan', 'BATCH-20260921-A01'],
            ['2026-10-25 09:00', 'Admin UTD PMI Kota', 'Uji saring lab selesai', 'BATCH-20261001-A01'],
        ];

        return view('admin.laporan', ['title' => 'Laporan', 'rekap' => $rekap, 'audit' => $audit]);
    }

    public function kegiatan()
    {
        $events = [
            ['Donor Darah Peduli Kampus - Telkom University', '08:00 - 14:00 WIB', 'Gedung TULT Lt.1', 'Jl. Telekomunikasi No. 1, Terusan Buahbatu', '2026-10-10', 120, 150, 30],
            ['Mobile Unit - Plaza Indonesia', '10:00 - 16:00 WIB', 'Atrium Utama Mall', 'Jl. M.H. Thamrin No. 28-30, Jakarta Pusat', '2026-10-15', 89, 100, 11],
            ['Aksi Donor Rutin UTD PMI Kota', '08:30 - 17:00 WIB', 'Gedung Donor PMI Lt. 2', 'Jl. Kramat Raya No. 47, Jakarta Pusat', '2026-10-25', 165, 200, 35],
        ];

        return view('admin.kegiatan', [
            'title'    => 'Kegiatan Donor',
            'events'   => $events,
            'pendonor' => User::role('pendonor')->latest()->get(), // data nyata dari database
        ]);
    }
}
