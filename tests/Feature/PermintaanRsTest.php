<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\KantongDarah;
use App\Models\PermintaanDarah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanRsTest extends TestCase
{
    use RefreshDatabase;

    private Instansi $rs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.id', 'instansi' => 'PMI', 'password' => 'password123']);
        $admin->assignRole('admin_pmi');
        $this->actingAs($admin);

        $this->rs = Instansi::create([
            'kode' => 'RS-001', 'nama' => 'RS Siloam Semanggi', 'tipe' => 'rumah_sakit',
            'alamat' => 'Jl. Sudirman', 'kota' => 'Jakarta',
        ]);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'instansi_id' => $this->rs->id, 'nama_pasien' => 'Budi Santoso', 'ruang' => 'ICU Bed 04', 'no_rm' => 'RM-99482',
            'golongan' => 'A', 'rhesus' => 'pos', 'komponen' => 'prc', 'jumlah' => 2, 'prioritas' => 'cito',
        ], $o);
    }

    private function permintaan(array $o = []): PermintaanDarah
    {
        return PermintaanDarah::create(array_merge($this->payload(), [
            'kode' => PermintaanDarah::generateKode(), 'status' => 'menunggu', 'diajukan_pada' => now(),
        ], $o));
    }

    private function kantong(string $kode, int $hariKedaluwarsa, array $o = []): KantongDarah
    {
        return KantongDarah::create(array_merge([
            'kode_batch' => $kode, 'tanggal_donor' => now()->subDays(2)->toDateString(), 'volume_ml' => 350,
            'komponen' => 'prc', 'golongan' => 'A', 'rhesus' => 'pos',
            'tanggal_kedaluwarsa' => now()->addDays($hariKedaluwarsa)->toDateString(),
            'status' => 'ready', 'lokasi_simpan' => 'Cold Room 1',
        ], $o));
    }

    private function validasiData(): array
    {
        return ['kurir_nama' => 'Ahmad', 'kurir_kendaraan' => 'B 4125 PMI'];
    }

    // ---------- CATAT PERMINTAAN ----------
    public function test_form_catat_permintaan_tampil(): void
    {
        $this->get('/admin/permintaan-rs/create')->assertOk()->assertSee('Catat Permintaan RS')->assertSee('RS Siloam Semanggi');
    }

    public function test_simpan_permintaan_berstatus_menunggu_dengan_kode_otomatis(): void
    {
        $this->post('/admin/permintaan-rs', $this->payload())->assertRedirect('/admin/permintaan-rs');

        $p = PermintaanDarah::first();
        $this->assertEquals('REQ-' . now()->year . '-1001', $p->kode);
        $this->assertEquals('menunggu', $p->status);
        $this->assertEquals('cito', $p->prioritas);
        $this->assertEquals($this->rs->id, $p->instansi_id);

        $this->post('/admin/permintaan-rs', $this->payload(['nama_pasien' => 'Siti']));
        $this->assertDatabaseHas('permintaan_darah', ['kode' => 'REQ-' . now()->year . '-1002']);
    }

    public function test_validasi_form_permintaan(): void
    {
        $this->post('/admin/permintaan-rs', [])->assertSessionHasErrors(['instansi_id', 'nama_pasien', 'no_rm', 'golongan', 'rhesus', 'komponen', 'jumlah', 'prioritas']);
        $this->post('/admin/permintaan-rs', $this->payload(['jumlah' => 0]))->assertSessionHasErrors('jumlah');
        $this->post('/admin/permintaan-rs', $this->payload(['jumlah' => 21]))->assertSessionHasErrors('jumlah');
        $this->post('/admin/permintaan-rs', $this->payload(['golongan' => 'X']))->assertSessionHasErrors('golongan');
        $this->post('/admin/permintaan-rs', $this->payload(['prioritas' => 'penting']))->assertSessionHasErrors('prioritas');
        $this->assertEquals(0, PermintaanDarah::count());
    }

    public function test_peminta_harus_instansi_rumah_sakit_aktif(): void
    {
        $pmi = Instansi::create(['kode' => 'PMI-001', 'nama' => 'UTD PMI', 'tipe' => 'pmi', 'alamat' => 'x', 'kota' => 'Jakarta']);
        $this->post('/admin/permintaan-rs', $this->payload(['instansi_id' => $pmi->id]))->assertSessionHasErrors('instansi_id');

        $this->rs->delete();
        $this->post('/admin/permintaan-rs', $this->payload())->assertSessionHasErrors('instansi_id');
        $this->assertEquals(0, PermintaanDarah::count());
    }

    // ---------- DAFTAR ----------
    public function test_daftar_menampilkan_cito_di_atas_dan_filter_status(): void
    {
        $this->permintaan(['nama_pasien' => 'Pasien Rutin', 'prioritas' => 'rutin', 'diajukan_pada' => now()]);
        $this->permintaan(['nama_pasien' => 'Pasien Cito', 'prioritas' => 'cito', 'diajukan_pada' => now()->subHours(3)]);
        $this->permintaan(['nama_pasien' => 'Pasien Ditolak', 'status' => 'ditolak', 'alasan_penolakan' => 'Data tidak lengkap']);

        $this->get('/admin/permintaan-rs')->assertOk()->assertSeeInOrder(['Pasien Cito', 'Pasien Rutin']);
        $this->get('/admin/permintaan-rs?status=ditolak')->assertSee('Pasien Ditolak')->assertDontSee('Pasien Cito');
        $this->get('/admin/permintaan-rs?status=diterima')->assertSee('Tidak ada permintaan dengan status ini');
    }

    public function test_daftar_menampilkan_stok_siap(): void
    {
        $this->permintaan(['jumlah' => 2]);
        $this->kantong('BATCH-1', 5);
        $this->kantong('BATCH-2', 6);

        $this->get('/admin/permintaan-rs')->assertSee('Stok UTD Siap (Ready): 2 Kantong');
    }

    // ---------- VALIDASI + ALOKASI FEFO ----------
    public function test_validasi_mengalokasikan_kantong_kedaluwarsa_terdekat_lebih_dulu(): void
    {
        $p = $this->permintaan(['jumlah' => 2]);
        $dekat = $this->kantong('BATCH-DEKAT', 3);
        $tengah = $this->kantong('BATCH-TENGAH', 10);
        $jauh = $this->kantong('BATCH-JAUH', 30);

        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData())->assertSessionHas('success');

        $p->refresh();
        $this->assertEquals('dalam_perjalanan', $p->status);
        $this->assertEquals('Ahmad', $p->kurir_nama);
        $this->assertEquals('B 4125 PMI', $p->kurir_kendaraan);
        $this->assertNotNull($p->divalidasi_pada);

        $this->assertEqualsCanonicalizing([$dekat->id, $tengah->id], $p->kantong()->pluck('kantong_darah.id')->all());
        $this->assertEquals('reserved', $dekat->fresh()->status);
        $this->assertEquals('reserved', $tengah->fresh()->status);
        $this->assertEquals('ready', $jauh->fresh()->status);   // tidak ikut dialokasikan
        $this->assertStringContainsString($p->kode, $dekat->fresh()->lokasi_simpan);
    }

    public function test_alokasi_hanya_kantong_ready_yang_cocok_dan_belum_kedaluwarsa(): void
    {
        $p = $this->permintaan(['jumlah' => 1]);
        $this->kantong('BATCH-KADALUWARSA', -1);
        $this->kantong('BATCH-KARANTINA', 2, ['status' => 'karantina']);
        $this->kantong('BATCH-GOL-B', 2, ['golongan' => 'B']);
        $this->kantong('BATCH-RH-NEG', 2, ['rhesus' => 'neg']);
        $this->kantong('BATCH-WB', 2, ['komponen' => 'wb']);
        $cocok = $this->kantong('BATCH-COCOK', 20);

        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData());

        $this->assertEquals([$cocok->id], $p->fresh()->kantong()->pluck('kantong_darah.id')->all());
    }

    public function test_validasi_gagal_jika_stok_tidak_cukup_dan_tidak_mengubah_apa_pun(): void
    {
        $p = $this->permintaan(['jumlah' => 3]);
        $a = $this->kantong('BATCH-A', 4);
        $b = $this->kantong('BATCH-B', 5);

        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData())->assertSessionHasErrors('permintaan');

        $this->assertEquals('menunggu', $p->fresh()->status);
        $this->assertEquals(0, $p->fresh()->kantong()->count());
        $this->assertEquals('ready', $a->fresh()->status);
        $this->assertEquals('ready', $b->fresh()->status);
    }

    public function test_validasi_wajib_data_kurir(): void
    {
        $p = $this->permintaan(['jumlah' => 1]);
        $this->kantong('BATCH-A', 4);

        $this->post("/admin/permintaan-rs/{$p->id}/validasi", [])->assertSessionHasErrors(['kurir_nama', 'kurir_kendaraan']);
        $this->assertEquals('menunggu', $p->fresh()->status);
    }

    public function test_permintaan_tidak_bisa_divalidasi_dua_kali(): void
    {
        $p = $this->permintaan(['jumlah' => 1]);
        $this->kantong('BATCH-A', 4);
        $this->kantong('BATCH-B', 5);

        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData());
        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData())->assertSessionHasErrors('permintaan');

        $this->assertEquals(1, $p->fresh()->kantong()->count());
        $this->assertEquals(1, KantongDarah::where('status', 'reserved')->count());
    }

    public function test_dua_permintaan_tidak_berebut_kantong_yang_sama(): void
    {
        $p1 = $this->permintaan(['jumlah' => 1]);
        $p2 = $this->permintaan(['jumlah' => 1]);
        $this->kantong('BATCH-TUNGGAL', 5);

        $this->post("/admin/permintaan-rs/{$p1->id}/validasi", $this->validasiData())->assertSessionHas('success');
        $this->post("/admin/permintaan-rs/{$p2->id}/validasi", $this->validasiData())->assertSessionHasErrors('permintaan');

        $this->assertEquals('menunggu', $p2->fresh()->status);
    }

    // ---------- TOLAK ----------
    public function test_tolak_permintaan_wajib_alasan(): void
    {
        $p = $this->permintaan();

        $this->post("/admin/permintaan-rs/{$p->id}/tolak", [])->assertSessionHasErrors('alasan_penolakan');
        $this->assertEquals('menunggu', $p->fresh()->status);

        $this->post("/admin/permintaan-rs/{$p->id}/tolak", ['alasan_penolakan' => 'Data pasien tidak lengkap']);
        $this->assertEquals('ditolak', $p->fresh()->status);
        $this->assertEquals('Data pasien tidak lengkap', $p->fresh()->alasan_penolakan);
    }

    public function test_hanya_permintaan_menunggu_yang_bisa_ditolak(): void
    {
        $p = $this->permintaan(['status' => 'dalam_perjalanan']);

        $this->post("/admin/permintaan-rs/{$p->id}/tolak", ['alasan_penolakan' => 'x'])->assertSessionHasErrors('permintaan');
        $this->assertEquals('dalam_perjalanan', $p->fresh()->status);
    }

    // ---------- PELACAKAN + SELESAI ----------
    public function test_perbarui_suhu_dan_posisi_selama_perjalanan(): void
    {
        $p = $this->permintaan(['status' => 'dalam_perjalanan', 'kurir_nama' => 'Ahmad', 'kurir_kendaraan' => 'B 1 PMI']);

        $this->patch("/admin/permintaan-rs/{$p->id}/lacak", ['suhu_cold_box' => '3.9', 'posisi_terakhir' => 'Jl. Sudirman'])->assertSessionHas('success');

        $p->refresh();
        $this->assertEquals('3.9', $p->suhu_cold_box);
        $this->assertEquals('Jl. Sudirman', $p->posisi_terakhir);
        $this->get('/admin/permintaan-rs')->assertSee('Suhu Cold Box: 3.9°C')->assertSee('Jl. Sudirman');
    }

    public function test_validasi_pelacakan(): void
    {
        $p = $this->permintaan(['status' => 'dalam_perjalanan']);
        $this->patch("/admin/permintaan-rs/{$p->id}/lacak", ['suhu_cold_box' => 'dingin'])->assertSessionHasErrors('suhu_cold_box');
        $this->patch("/admin/permintaan-rs/{$p->id}/lacak", ['suhu_cold_box' => '99'])->assertSessionHasErrors('suhu_cold_box');

        $menunggu = $this->permintaan();
        $this->patch("/admin/permintaan-rs/{$menunggu->id}/lacak", ['suhu_cold_box' => '4'])->assertSessionHasErrors('permintaan');
    }

    public function test_suhu_di_luar_rentang_cold_chain_diberi_peringatan(): void
    {
        $this->permintaan(['status' => 'dalam_perjalanan', 'kurir_nama' => 'Ahmad', 'kurir_kendaraan' => 'B 1 PMI', 'suhu_cold_box' => 14.5]);

        $this->get('/admin/permintaan-rs')->assertSee('di luar 1-10');
    }

    public function test_tandai_diterima(): void
    {
        $p = $this->permintaan(['status' => 'dalam_perjalanan']);

        $this->post("/admin/permintaan-rs/{$p->id}/selesai")->assertSessionHas('success');

        $this->assertEquals('diterima', $p->fresh()->status);
        $this->assertNotNull($p->fresh()->selesai_pada);
    }

    public function test_tidak_bisa_diterima_sebelum_divalidasi(): void
    {
        $p = $this->permintaan();

        $this->post("/admin/permintaan-rs/{$p->id}/selesai")->assertSessionHasErrors('permintaan');
        $this->assertEquals('menunggu', $p->fresh()->status);
    }

    // ---------- DASHBOARD + AKSES ----------
    public function test_dashboard_menghitung_permintaan_pending_dari_database(): void
    {
        $this->permintaan();
        $this->permintaan();
        $this->permintaan(['status' => 'ditolak']);

        $this->get('/admin/dashboard')->assertOk()->assertViewHas('pending', 2);
    }

    public function test_peran_lain_dan_tamu_ditolak(): void
    {
        $p = $this->permintaan();
        auth()->logout();
        $this->get('/admin/permintaan-rs')->assertRedirect('/login');

        $petugas = User::create(['name' => 'Petugas', 'email' => 'p@test.id', 'instansi' => 'RS', 'password' => 'password123']);
        $petugas->assignRole('petugas_rs');
        $this->actingAs($petugas);

        $this->get('/admin/permintaan-rs')->assertForbidden();
        $this->post('/admin/permintaan-rs', $this->payload())->assertForbidden();
        $this->post("/admin/permintaan-rs/{$p->id}/validasi", $this->validasiData())->assertForbidden();
        $this->post("/admin/permintaan-rs/{$p->id}/tolak", ['alasan_penolakan' => 'x'])->assertForbidden();
    }
}
