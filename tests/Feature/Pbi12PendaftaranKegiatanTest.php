<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\KegiatanDonor;
use App\Models\PendaftaranKegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** pendaftaran peserta kegiatan donor. */
class Pbi12PendaftaranKegiatanTest extends TestCase
{
    use RefreshDatabase, Concerns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPeran();
    }

    private function kegiatan(array $o = []): KegiatanDonor
    {
        return KegiatanDonor::create($o + [
            'judul' => 'Donor Kampus', 'tanggal' => today()->addDays(7), 'jam_mulai' => '08:00', 'jam_selesai' => '14:00',
            'kuota' => 10, 'nama_tempat' => 'Gedung A', 'alamat' => 'Jl. Contoh', 'status' => 'terbuka',
        ]);
    }

    private function masukSebagaiPendonor(array $o = [])
    {
        $akun = $this->akun('pendonor');
        $p = $this->pendonor($o, $akun);
        $this->actingAs($akun);

        return $p;
    }

    public function test_pendonor_melihat_kegiatan_terbuka_saja(): void
    {
        $this->kegiatan(['judul' => 'Kegiatan Terbuka']);
        $this->kegiatan(['judul' => 'Kegiatan Ditutup', 'status' => 'ditutup']);
        $this->kegiatan(['judul' => 'Kegiatan Kemarin', 'tanggal' => today()->subDay()]);
        $this->masukSebagaiPendonor();

        $this->get('/pendonor/kegiatan')->assertOk()
            ->assertSee('Kegiatan Terbuka')->assertDontSee('Kegiatan Ditutup')->assertDontSee('Kegiatan Kemarin');
    }

    public function test_pendaftaran_berhasil_dan_tercatat(): void
    {
        $k = $this->kegiatan();
        $p = $this->masukSebagaiPendonor();

        $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('pendaftaran_kegiatan', ['pendonor_id' => $p->id, 'kegiatan_id' => $k->id, 'status_kehadiran' => 'terdaftar']);
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'pendaftaran_kegiatan', 'entitas_id' => $k->id]);
    }

    public function test_pendaftaran_ganda_ditolak(): void
    {
        $k = $this->kegiatan();
        $this->masukSebagaiPendonor();

        $this->post("/pendonor/kegiatan/{$k->id}/daftar");
        $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertSessionHasErrors('pendaftaran');

        $this->assertSame(1, PendaftaranKegiatan::count());
    }

    public function test_kuota_penuh_ditolak(): void
    {
        $k = $this->kegiatan(['kuota' => 1]);
        PendaftaranKegiatan::create(['pendonor_id' => $this->pendonor()->id, 'kegiatan_id' => $k->id, 'tanggal_daftar' => now()]);
        $this->masukSebagaiPendonor();

        $this->post("/pendonor/kegiatan/{$k->id}/daftar")
            ->assertSessionHasErrors(['pendaftaran' => 'Kuota kegiatan ini sudah penuh. Silakan pilih kegiatan lain.']);
        $this->assertSame(1, PendaftaranKegiatan::count());
    }

    public function test_kegiatan_ditutup_dibatalkan_atau_sudah_lewat_ditolak(): void
    {
        $this->masukSebagaiPendonor();

        foreach ([['status' => 'ditutup'], ['status' => 'dibatalkan'], ['tanggal' => today()->subDay()]] as $o) {
            $k = $this->kegiatan($o);
            $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertSessionHasErrors('pendaftaran');
        }
        $this->assertSame(0, PendaftaranKegiatan::count());
    }

    public function test_jeda_minimal_antar_donasi_ditegakkan(): void
    {
        $k = $this->kegiatan(['tanggal' => today()->addDays(7)]);
        $p = $this->masukSebagaiPendonor();
        Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays(20), 'volume_ml' => 350]);   // layak mulai +40 hari

        $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertSessionHasErrors('pendaftaran');
        $this->assertSame(0, PendaftaranKegiatan::count());

        // Kegiatan setelah tanggal layak -> boleh
        $k2 = $this->kegiatan(['tanggal' => today()->addDays(45)]);
        $this->post("/pendonor/kegiatan/{$k2->id}/daftar")->assertSessionHas('success');
    }

    public function test_akun_belum_terhubung_dengan_data_pendonor_diberi_pesan(): void
    {
        $k = $this->kegiatan();
        $this->actingAs($this->akun('pendonor'));

        $this->get('/pendonor/kegiatan')->assertOk()->assertSee('belum terhubung', false);
        $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertOk();
        $this->assertSame(0, PendaftaranKegiatan::count());
    }

    public function test_hanya_pendonor_yang_boleh_mendaftar(): void
    {
        $k = $this->kegiatan();
        $this->actingAs($this->akun('admin_pmi'))->post("/pendonor/kegiatan/{$k->id}/daftar")->assertForbidden();
        auth()->logout();
        $this->post("/pendonor/kegiatan/{$k->id}/daftar")->assertRedirect('/login');
    }

    public function test_constraint_unik_database_mencegah_pendaftaran_ganda(): void
    {
        $k = $this->kegiatan();
        $p = $this->pendonor();
        PendaftaranKegiatan::create(['pendonor_id' => $p->id, 'kegiatan_id' => $k->id, 'tanggal_daftar' => now()]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        PendaftaranKegiatan::create(['pendonor_id' => $p->id, 'kegiatan_id' => $k->id, 'tanggal_daftar' => now()]);
    }

    public function test_admin_membuat_kegiatan_dan_validasi_tanggal_lampau(): void
    {
        $this->actingAs($this->akun('admin_pmi'));
        $data = ['judul' => 'Aksi Baru', 'tanggal' => today()->addDays(3)->toDateString(), 'jam_mulai' => '08:00', 'jam_selesai' => '12:00',
            'kuota' => 50, 'nama_tempat' => 'Balai', 'alamat' => 'Jl. X'];

        $this->post('/admin/kegiatan-donor', $data)->assertRedirect();
        $this->assertDatabaseHas('kegiatan_donor', ['judul' => 'Aksi Baru', 'status' => 'terbuka']);

        $this->post('/admin/kegiatan-donor', ['tanggal' => today()->subDay()->toDateString(), 'jam_selesai' => '07:00'] + $data)
            ->assertSessionHasErrors(['tanggal', 'jam_selesai']);
    }
}
