<?php

namespace Database\Seeders;

use App\Models\Donasi;
use App\Models\KantongDarah;
use App\Models\KegiatanDonor;
use App\Models\LogTransaksi;
use App\Models\PendaftaranKegiatan;
use App\Models\Pendonor;
use App\Models\User;
use Illuminate\Database\Seeder;


class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (KantongDarah::exists()) {
            return;
        }

        mt_srand(2026);

        $admin = User::where('email', 'admin.pmi@bloodconnect.id')->first();
        $this->seedPendonor();
        $this->seedKantong($admin);
        $this->seedKegiatan($admin);
    }

    private function seedPendonor(): void
    {
        $data = [
            ['Pendonor Contoh',     'L', 'O',  'pos', 'Jakarta Pusat',    120],
            ['Lutfiyah Hidayat',    'P', 'O',  'pos', 'Jakarta Pusat',    10],   // belum layak (jeda 60 hari)
            ['Rafli Ilham',         'L', 'O',  'pos', 'Jakarta barat',    75],
            ['Aqila Karimah',       'P', 'A',  'pos', 'Jakarta Pusat',    90],
            ['Ilham Fahrozi',       'L', 'B',  'pos', 'Jakarta Selatan',  200],
            ['Muhammad Subhan',     'L', 'AB', 'pos', 'Jakarta Pusat',    null],
            ['Kartika Putri',       'P', 'AB', 'pos', 'Jakarta Pusat',    130],
            ['Agus Rozi',           'L', 'O',  'neg', 'Jakarta Pusat',    100],
            ['Dhafin Lestari',      'P', 'B',  'neg', 'Jakarta Timur',    80],
            ['Harjuno Juno',        'L', 'A',  'neg', 'Bandung',          150],
            ['Maya Padasari',       'P', 'O',  'pos', 'Bekasi',           95],
            ['Sumber Alam',         'L', 'AB', 'neg', 'Jakarta Pusat',    62],
        ];

        foreach ($data as $i => [$nama, $jk, $gol, $rh, $kota, $hari]) {
            $p = Pendonor::create([
                'kode_donor'    => Pendonor::generateKode(),
                'nama'          => $nama,
                'tanggal_lahir' => today()->subYears(22 + $i)->subDays($i * 11),
                'jenis_kelamin' => $jk,
                'golongan'      => $gol,
                'rhesus'        => $rh,
                'no_hp'         => '0812' . str_pad((string) (3000000 + $i * 7919), 8, '0', STR_PAD_LEFT),
                'kota'          => $kota,
                'alamat'        => 'Jl. Contoh No. ' . (10 + $i),
            ]);

            if ($hari !== null) {
                Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays($hari),
                    'volume_ml' => 350, 'lokasi' => 'UTD PMI Kota']);
                Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays($hari + 130),
                    'volume_ml' => 350, 'lokasi' => 'Mobile Unit']);
            }
        }

        // Hubungkan akun contoh dengan data pendonor agar portal pendonor bisa dicoba.
        $this->hubungkan('pendonor@bloodconnect.id', 'Pendonor Contoh', 'Pendonor Contoh');
        $this->hubungkan('pendonor2@bloodconnect.id', 'Siti Rahmawati', 'Siti Rahmawati');
    }

    private function hubungkan(string $email, string $namaAkun, string $namaPendonor): void
    {
        $user = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            ['name' => $namaAkun, 'instansi' => 'Umum', 'password' => 'password123']
        );
        $user->syncRoles(['pendonor']);
        Pendonor::where('nama', $namaPendonor)->update(['user_id' => $user->id]);
    }

    private function seedKantong(?User $admin): void
    {
        // jumlah kantong Ready per golongan + rhesus (sengaja ada yang di bawah batas minimum 50)
        $jumlah = [
            ['A', 'pos', 58], ['B', 'pos', 54], ['AB', 'pos', 36], ['O', 'pos', 66],
            ['A', 'neg', 21], ['B', 'neg', 18], ['AB', 'neg', 6],  ['O', 'neg', 24],
        ];
        $komponenBobot = ['prc' => 70, 'wb' => 15, 'tc' => 5, 'ffp' => 10];
        $seq = 0;
        $baris = [];
        $log = [];

        $buat = function (string $g, string $r, string $komp, int $hariLalu, string $status, ?string $hasil = null) use (&$seq, &$baris, $admin) {
            $donor = today()->subDays($hariLalu);
            $exp = $donor->copy()->addDays(KantongDarah::MASA_SIMPAN[$komp]);
            $seq++;
            $baris[] = [
                'kode_batch'          => sprintf('BATCH-%s-%s%s%03d', $donor->format('Ymd'), $g, $r === 'pos' ? 'P' : 'N', $seq),
                'tanggal_donor'       => $donor->toDateString(),
                'volume_ml'           => $komp === 'tc' ? 250 : 350,
                'komponen'            => $komp,
                'golongan'            => $g,
                'rhesus'              => $r,
                'tanggal_kedaluwarsa' => $exp->toDateString(),
                'status'              => $status,
                'lokasi_simpan'       => match ($status) { 'ready' => 'Cold Room 1', 'karantina' => 'Karantina', default => KantongDarah::STATUS[$status] },
                'hasil_uji'           => $hasil,
                'tanggal_uji'         => $hasil ? $donor->copy()->addDay()->toDateString() : null,
                'dibuat_oleh'         => $admin?->id,
                'created_at'          => $donor->copy()->setTime(9, 0),
                'updated_at'          => $donor->copy()->setTime(9, 0),
            ];
        };

        foreach ($jumlah as [$g, $r, $n]) {
            for ($i = 0; $i < $n; $i++) {
                $komp = $this->pilihBobot($komponenBobot);
                $maks = KantongDarah::MASA_SIMPAN[$komp];
                // umur kantong: 0..(masa simpan - 2) hari -> sisa masa simpan minimal 2 hari
                $umur = $komp === 'ffp' ? mt_rand(0, 200) : mt_rand(0, max(1, $maks - 2));
                $buat($g, $r, $komp, $umur, 'ready', 'non_reaktif');
            }
        }
        // karantina (antrean uji saring), ditahan (reaktif), kedaluwarsa & dimusnahkan
        foreach ([['A', 'pos'], ['O', 'pos'], ['B', 'pos'], ['AB', 'pos'], ['O', 'neg'], ['A', 'neg']] as [$g, $r]) {
            $buat($g, $r, 'prc', mt_rand(0, 2), 'karantina');
        }
        $buat('B', 'pos', 'prc', 5, 'ditahan', 'reaktif');
        $buat('O', 'pos', 'wb', 6, 'ditahan', 'reaktif');
        foreach ([['A', 'pos'], ['O', 'pos'], ['B', 'pos']] as [$g, $r]) {
            $buat($g, $r, 'prc', 38, 'kedaluwarsa', 'non_reaktif');
        }
        foreach ([['AB', 'pos'], ['O', 'neg']] as [$g, $r]) {
            $buat($g, $r, 'prc', 42, 'dimusnahkan', 'non_reaktif');
        }

        foreach (array_chunk($baris, 200) as $chunk) {
            KantongDarah::insert($chunk);
        }

        // Jejak audit: setiap kantong tercatat masuk pada tanggal donornya; yang kedaluwarsa/dimusnahkan juga dicatat.
        foreach (KantongDarah::orderBy('id')->get() as $k) {
            LogTransaksi::catat('kantong_masuk', 'kantong', $k->id,
                "Kantong {$k->kode_batch} dicatat masuk berstatus Karantina.",
                ['baru' => $k->untukLog() + ['status' => 'karantina'], 'user' => $admin, 'waktu' => $k->created_at]);

            if (in_array($k->status, ['kedaluwarsa', 'dimusnahkan'], true)) {
                LogTransaksi::catat('kantong_kedaluwarsa', 'kantong', $k->id,
                    "Kantong {$k->kode_batch} melewati tanggal kedaluwarsa.",
                    ['baru' => $k->untukLog() + ['status' => 'kedaluwarsa'], 'user' => null, 'waktu' => $k->tanggal_kedaluwarsa->copy()->addDay()->setTime(0, 5)]);
            }
            if ($k->status === 'dimusnahkan') {
                LogTransaksi::catat('kantong_dimusnahkan', 'kantong', $k->id,
                    "Pemusnahan kantong {$k->kode_batch} dicatat.",
                    ['baru' => $k->untukLog() + ['status' => 'dimusnahkan'], 'user' => $admin, 'waktu' => $k->tanggal_kedaluwarsa->copy()->addDays(2)->setTime(10, 0)]);
            }
        }
    }

    private function pilihBobot(array $bobot): string
    {
        $r = mt_rand(1, array_sum($bobot));
        foreach ($bobot as $k => $b) {
            if (($r -= $b) <= 0) {
                return $k;
            }
        }

        return array_key_first($bobot);
    }

    private function seedKegiatan(?User $admin): void
    {
        $data = [
            ['Donor Darah Peduli Kampus - Telkom University', 7, '08:00', '14:00', 150, 'Gedung TULT Lt.1', 'Jl. Telekomunikasi No. 1, Terusan Buahbatu'],
            ['Mobile Unit - Plaza Indonesia', 14, '10:00', '16:00', 100, 'Atrium Utama Mall', 'Jl. M.H. Thamrin No. 28-30, Jakarta Pusat'],
            ['Aksi Donor Rutin UTD PMI Kota', 21, '08:30', '17:00', 2, 'Gedung Donor PMI Lt. 2', 'Jl. Kramat Raya No. 47, Jakarta Pusat'],
        ];
        $kegiatan = [];
        foreach ($data as [$judul, $hari, $mulai, $selesai, $kuota, $tempat, $alamat]) {
            $kegiatan[] = KegiatanDonor::create([
                'judul' => $judul, 'tanggal' => today()->addDays($hari), 'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
                'kuota' => $kuota, 'nama_tempat' => $tempat, 'alamat' => $alamat, 'status' => 'terbuka', 'dibuat_oleh' => $admin?->id,
            ]);
        }
        KegiatanDonor::create([
            'judul' => 'Donor Darah Hari Pahlawan (ditutup)', 'tanggal' => today()->addDays(3), 'jam_mulai' => '09:00', 'jam_selesai' => '13:00',
            'kuota' => 80, 'nama_tempat' => 'Balai Kota', 'alamat' => 'Jl. Medan Merdeka Selatan No. 8', 'status' => 'ditutup', 'dibuat_oleh' => $admin?->id,
        ]);

        // Kegiatan ketiga sudah penuh (kuota 2) untuk mencoba penolakan "kuota penuh".
        foreach (['Budi Santoso', 'Dewi Anggraini'] as $nama) {
            PendaftaranKegiatan::create([
                'pendonor_id' => Pendonor::where('nama', $nama)->value('id'), 'kegiatan_id' => $kegiatan[2]->id,
                'tanggal_daftar' => now()->subDays(2), 'status_kehadiran' => 'terdaftar',
            ]);
        }
        PendaftaranKegiatan::create([
            'pendonor_id' => Pendonor::where('nama', 'Nadia Putri')->value('id'), 'kegiatan_id' => $kegiatan[0]->id,
            'tanggal_daftar' => now()->subDay(), 'status_kehadiran' => 'terdaftar',
        ]);
    }
}
