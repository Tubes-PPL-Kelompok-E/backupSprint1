<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\KantongDarah;
use App\Models\KegiatanDonor;
use App\Models\LogTransaksi;
use App\Models\LokasiPenyimpanan;
use App\Models\PendaftaranKegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Kelengkapan CRUD dan aturan bisnis hasil peninjauan kode Sprint 1. */
class PerbaikanSprint1Test extends TestCase
{
    use RefreshDatabase, Concerns;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seedPeran();
    }

    private function admin()
    {
        return $this->actingAs($this->akun('admin_pmi'));
    }

    private function kegiatan(array $o = []): KegiatanDonor
    {
        return KegiatanDonor::create($o + [
            'judul' => 'Donor Kampus', 'tanggal' => today()->addDays(7), 'jam_mulai' => '08:00', 'jam_selesai' => '14:00',
            'kuota' => 10, 'nama_tempat' => 'Gedung A', 'alamat' => 'Jl. Contoh', 'status' => 'terbuka',
        ]);
    }

    private function formKantong(array $o = []): array
    {
        return $o + ['tanggal_donor' => today()->subDays(2)->toDateString(), 'volume_ml' => 350, 'komponen' => 'prc', 'golongan' => 'A', 'rhesus' => 'pos'];
    }

    // ---------- Kantong darah: kelengkapan CRUD

    public function test_kantong_karantina_bisa_diubah_dan_kedaluwarsa_dihitung_ulang(): void
    {
        $this->admin();
        $k = $this->kantong(['status' => 'karantina', 'kode_batch' => 'BATCH-UBAH-1']);

        $this->get(route('admin.kantong.edit', $k))->assertOk()->assertSee('Ubah Kantong Darah');
        $this->put(route('admin.kantong.update', $k), $this->formKantong(['kode_batch' => 'BATCH-UBAH-1', 'komponen' => 'tc', 'golongan' => 'B']))
            ->assertRedirect(route('admin.stok'));

        $k->refresh();
        $this->assertSame('tc', $k->komponen);
        $this->assertSame('B', $k->golongan);
        $this->assertSame(today()->subDays(2)->addDays(5)->toDateString(), $k->tanggal_kedaluwarsa->toDateString());
        $this->assertTrue(LogTransaksi::where('aksi', 'kantong_diubah')->where('entitas_id', $k->id)->exists());
    }

    public function test_kantong_yang_sudah_ready_tidak_bisa_diubah_atau_dihapus(): void
    {
        $this->admin();
        $k = $this->kantong(['status' => 'ready']);

        $this->get(route('admin.kantong.edit', $k))->assertRedirect(route('admin.stok'));
        $this->put(route('admin.kantong.update', $k), $this->formKantong())->assertSessionHasErrors('kantong');
        $this->delete(route('admin.kantong.destroy', $k))->assertSessionHasErrors('kantong');
        $this->assertDatabaseHas('kantong_darah', ['id' => $k->id, 'status' => 'ready']);
    }

    public function test_kantong_karantina_bisa_dihapus_dan_tercatat_di_log(): void
    {
        $this->admin();
        $k = $this->kantong(['status' => 'karantina']);

        $this->delete(route('admin.kantong.destroy', $k))->assertRedirect(route('admin.stok'));

        $this->assertDatabaseMissing('kantong_darah', ['id' => $k->id]);
        $this->assertTrue(LogTransaksi::where('aksi', 'kantong_dihapus')->where('entitas_id', $k->id)->exists());
    }

    public function test_ubah_kantong_boleh_memakai_kode_sendiri_tetapi_tidak_kode_kantong_lain(): void
    {
        $this->admin();
        $a = $this->kantong(['status' => 'karantina', 'kode_batch' => 'BATCH-AAAA']);
        $this->kantong(['kode_batch' => 'BATCH-BBBB']);

        $this->put(route('admin.kantong.update', $a), $this->formKantong(['kode_batch' => 'BATCH-AAAA']))->assertSessionHasNoErrors();
        $this->put(route('admin.kantong.update', $a), $this->formKantong(['kode_batch' => 'BATCH-BBBB']))->assertSessionHasErrors('kode_batch');
    }

    public function test_kantong_yang_sudah_melewati_masa_simpan_ditolak_saat_dicatat(): void
    {
        $this->admin();

        // PRC berumur 35 hari: donor 40 hari lalu sudah lewat masa simpan, 30 hari lalu masih boleh
        $this->post(route('admin.kantong.store'), $this->formKantong(['tanggal_donor' => today()->subDays(40)->toDateString()]))
            ->assertSessionHasErrors('tanggal_donor');
        $this->post(route('admin.kantong.store'), $this->formKantong(['tanggal_donor' => today()->subDays(30)->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, KantongDarah::count());
    }

    // ---------- Uji saring dan lokasi

    private function hasilUji(array $o = []): array
    {
        return $o + ['hasil_uji' => 'non_reaktif', 'nomor_berita_acara' => 'BA-1', 'tanggal_uji' => today()->toDateString(),
            'berkas' => UploadedFile::fake()->create('ba.pdf', 50, 'application/pdf')];
    }

    public function test_tanpa_lokasi_dipilih_kantong_masuk_lokasi_aktif_yang_masih_punya_ruang(): void
    {
        $this->admin();
        LokasiPenyimpanan::create(['nama' => 'Cold A', 'jenis' => 'cold_room', 'kapasitas' => 1, 'aktif' => true]);
        LokasiPenyimpanan::create(['nama' => 'Cold B', 'jenis' => 'cold_room', 'kapasitas' => 5, 'aktif' => true]);
        $this->kantong(['status' => 'ready', 'lokasi_simpan' => 'Cold A']);              // Cold A sudah penuh
        $baru = $this->kantong(['status' => 'karantina', 'lokasi_simpan' => 'Karantina']);

        $this->post(route('admin.uji-saring.store', $baru), $this->hasilUji())->assertSessionHasNoErrors();

        $this->assertSame('Cold B', $baru->fresh()->lokasi_simpan);
        $this->assertSame('ready', $baru->fresh()->status);
    }

    public function test_tanpa_lokasi_yang_punya_ruang_uji_saring_ditolak_dan_status_tetap_karantina(): void
    {
        $this->admin();
        $baru = $this->kantong(['status' => 'karantina', 'lokasi_simpan' => 'Karantina']);

        $this->post(route('admin.uji-saring.store', $baru), $this->hasilUji())->assertSessionHasErrors('lokasi_simpan');

        $this->assertSame('karantina', $baru->fresh()->status);
        $this->assertNull($baru->fresh()->berkas_berita_acara);
    }

    public function test_uji_saring_kantong_yang_sudah_diuji_ditolak(): void
    {
        $this->admin();
        LokasiPenyimpanan::create(['nama' => 'Cold A', 'jenis' => 'cold_room', 'kapasitas' => 5, 'aktif' => true]);
        $k = $this->kantong(['status' => 'karantina']);

        $this->post(route('admin.uji-saring.store', $k), $this->hasilUji())->assertSessionHasNoErrors();
        $this->post(route('admin.uji-saring.store', $k), $this->hasilUji(['hasil_uji' => 'reaktif']))->assertSessionHasErrors('kantong');

        $this->assertSame('ready', $k->fresh()->status);
    }

    public function test_ganti_nama_lokasi_ikut_memperbarui_kantong_dan_hitungan_isi(): void
    {
        $this->admin();
        $l = LokasiPenyimpanan::create(['nama' => 'Cold Lama', 'jenis' => 'cold_room', 'kapasitas' => 3, 'aktif' => true]);
        $k = $this->kantong(['status' => 'ready', 'lokasi_simpan' => 'Cold Lama']);

        $this->put(route('admin.lokasi.update', $l), ['nama' => 'Cold Baru', 'jenis' => 'cold_room', 'kapasitas' => 3, 'aktif' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame('Cold Baru', $k->fresh()->lokasi_simpan);
        $this->assertSame(1, $l->fresh()->terisi());
    }

    // ---------- Kegiatan donor: ubah

    public function test_kegiatan_terbuka_bisa_diubah_dan_tercatat(): void
    {
        $this->admin();
        $g = $this->kegiatan();

        $this->get(route('admin.kegiatan.edit', $g))->assertOk()->assertSee('Ubah Kegiatan Donor');
        $this->put(route('admin.kegiatan.update', $g), [
            'judul' => 'Donor Kampus Baru', 'tanggal' => today()->addDays(9)->toDateString(), 'jam_mulai' => '09:00', 'jam_selesai' => '15:00',
            'kuota' => 20, 'nama_tempat' => 'Gedung B', 'alamat' => 'Jl. Baru',
        ])->assertRedirect(route('admin.kegiatan.show', $g));

        $this->assertSame('Donor Kampus Baru', $g->fresh()->judul);
        $this->assertSame(20, $g->fresh()->kuota);
        $this->assertTrue(LogTransaksi::where('aksi', 'kegiatan_diubah')->where('entitas_id', $g->id)->exists());
    }

    public function test_kuota_tidak_boleh_di_bawah_jumlah_pendaftar(): void
    {
        $this->admin();
        $g = $this->kegiatan(['kuota' => 5]);
        foreach ([1, 2, 3] as $i) {
            PendaftaranKegiatan::create(['pendonor_id' => $this->pendonor()->id, 'kegiatan_id' => $g->id, 'tanggal_daftar' => now(), 'status_kehadiran' => 'terdaftar']);
        }

        $this->put(route('admin.kegiatan.update', $g), [
            'judul' => $g->judul, 'tanggal' => $g->tanggal->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '14:00',
            'kuota' => 2, 'nama_tempat' => 'Gedung A', 'alamat' => 'x',
        ])->assertSessionHasErrors('kuota');
        $this->assertSame(5, $g->fresh()->kuota);
    }

    public function test_kegiatan_yang_ditutup_tidak_bisa_diubah(): void
    {
        $this->admin();
        $g = $this->kegiatan(['status' => 'ditutup']);

        $this->get(route('admin.kegiatan.edit', $g))->assertRedirect(route('admin.kegiatan.show', $g));
    }

    // ---------- Pendonor: batal pendaftaran

    public function test_pendonor_bisa_membatalkan_pendaftaran_dan_kuota_kembali(): void
    {
        $akun = $this->akun('pendonor');
        $p = $this->pendonor([], $akun);
        $g = $this->kegiatan(['kuota' => 1]);
        $this->actingAs($akun)->post(route('pendonor.kegiatan.daftar', $g))->assertSessionHasNoErrors();
        $this->assertSame(0, $g->fresh()->sisaKuota());

        $this->delete(route('pendonor.kegiatan.batal', $g))->assertSessionHasNoErrors();

        $this->assertSame(1, $g->fresh()->sisaKuota());
        $this->assertTrue(LogTransaksi::where('aksi', 'pendaftaran_dibatalkan')->exists());
        $this->delete(route('pendonor.kegiatan.batal', $g))->assertSessionHasErrors('pendaftaran');   // sudah tidak terdaftar
    }

    public function test_pendonor_tidak_bisa_membatalkan_kegiatan_yang_sudah_ditutup(): void
    {
        $akun = $this->akun('pendonor');
        $p = $this->pendonor([], $akun);
        $g = $this->kegiatan(['status' => 'ditutup']);
        PendaftaranKegiatan::create(['pendonor_id' => $p->id, 'kegiatan_id' => $g->id, 'tanggal_daftar' => now(), 'status_kehadiran' => 'terdaftar']);

        $this->actingAs($akun)->delete(route('pendonor.kegiatan.batal', $g))->assertSessionHasErrors('pendaftaran');
        $this->assertSame(1, PendaftaranKegiatan::count());
    }

    // ---------- Donasi: ubah, hapus, jeda

    public function test_donasi_ganda_atau_terlalu_rapat_ditolak_tetapi_donasi_lama_boleh_dicatat(): void
    {
        $this->admin();
        $p = $this->pendonor();
        $url = route('admin.pendonor.donasi', $p);
        $isi = fn (int $hariLalu) => ['tanggal_donor' => today()->subDays($hariLalu)->toDateString(), 'volume_ml' => 350];

        $this->post($url, $isi(100))->assertSessionHasNoErrors();
        $this->post($url, $isi(100))->assertSessionHasErrors('donasi');      // hari yang sama
        $this->post($url, $isi(70))->assertSessionHasErrors('donasi');       // 30 hari setelah donasi pertama
        $this->post($url, $isi(130))->assertSessionHasErrors('donasi');      // 30 hari sebelum donasi pertama
    }

    public function test_jarak_tepat_sama_dengan_jeda_diperbolehkan(): void
    {
        $this->admin();
        $p = $this->pendonor();
        $jeda = config('bloodconnect.interval_donor_hari');
        $url = route('admin.pendonor.donasi', $p);

        $this->post($url, ['tanggal_donor' => today()->subDays(200)->toDateString(), 'volume_ml' => 350])->assertSessionHasNoErrors();
        $this->post($url, ['tanggal_donor' => today()->subDays(200 - $jeda)->toDateString(), 'volume_ml' => 350])->assertSessionHasNoErrors();
        $this->post($url, ['tanggal_donor' => today()->subDays(200 + $jeda)->toDateString(), 'volume_ml' => 350])->assertSessionHasNoErrors();
        $this->assertSame(3, $p->donasi()->count());
    }

    public function test_donasi_bisa_diubah_dan_dihapus_dengan_log(): void
    {
        $this->admin();
        $p = $this->pendonor();
        $d = Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays(10), 'volume_ml' => 350, 'lokasi' => 'UTD']);

        $this->put(route('admin.pendonor.donasi.update', [$p, $d]), ['tanggal_donor' => today()->subDays(20)->toDateString(), 'volume_ml' => 400, 'lokasi' => 'RS'])
            ->assertSessionHasNoErrors();
        $this->assertSame(400, $d->fresh()->volume_ml);
        $this->assertSame(today()->subDays(20)->toDateString(), $d->fresh()->tanggal_donor->toDateString());

        $this->delete(route('admin.pendonor.donasi.destroy', [$p, $d]))->assertSessionHasNoErrors();
        $this->assertSame(0, $p->donasi()->count());
        $this->assertTrue($p->fresh()->layakPada());
        foreach (['donasi_diubah', 'donasi_dihapus'] as $aksi) {
            $this->assertTrue(LogTransaksi::where('aksi', $aksi)->exists(), $aksi);
        }
    }

    public function test_donasi_milik_pendonor_lain_tidak_bisa_diubah_lewat_url_pendonor_ini(): void
    {
        $this->admin();
        $a = $this->pendonor();
        $b = $this->pendonor();
        $d = Donasi::create(['pendonor_id' => $b->id, 'tanggal_donor' => today()->subDays(10), 'volume_ml' => 350]);

        $this->delete(route('admin.pendonor.donasi.destroy', [$a, $d]))->assertNotFound();
        $this->assertDatabaseHas('donasi', ['id' => $d->id]);
    }

    // ---------- Pendonor: akun yang dihubungkan

    public function test_pendonor_hanya_bisa_dihubungkan_ke_akun_berperan_pendonor(): void
    {
        $this->admin();
        $akunRs = $this->akun('petugas_rs');

        $this->post(route('admin.pendonor.store'), [
            'nama' => 'Budi Hubung', 'tanggal_lahir' => '1995-01-01', 'jenis_kelamin' => 'L', 'golongan' => 'A', 'rhesus' => 'pos',
            'kota' => 'Jakarta', 'user_id' => $akunRs->id,
        ])->assertSessionHasErrors('user_id');
    }

    // ---------- Reset kata sandi dibatasi

    public function test_reset_kata_sandi_dibatasi_percobaannya(): void
    {
        $data = ['token' => 'salah', 'email' => 'tidak.ada@test.id', 'password' => 'Password123', 'password_confirmation' => 'Password123'];
        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', $data);
        }

        $this->post('/reset-password', $data)->assertStatus(429);
    }
}
