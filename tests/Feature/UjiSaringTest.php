<?php

namespace Tests\Feature;

use App\Models\KantongDarah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UjiSaringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.id', 'instansi' => 'PMI', 'password' => 'password123']);
        $admin->assignRole('admin_pmi');
        $this->actingAs($admin);
    }

    private function kantong(array $o = []): KantongDarah
    {
        return KantongDarah::create(array_merge([
            'kode_batch' => 'BATCH-UJI-001', 'tanggal_donor' => now()->subDays(3)->toDateString(), 'volume_ml' => 350,
            'komponen' => 'prc', 'golongan' => 'O', 'rhesus' => 'pos',
            'tanggal_kedaluwarsa' => now()->addDays(30)->toDateString(), 'status' => 'karantina', 'lokasi_simpan' => 'Karantina',
        ], $o));
    }

    private function hasil(array $o = []): array
    {
        return array_merge([
            'hasil_uji' => 'non_reaktif', 'nomor_berita_acara' => 'BA-0942/UTD/X/2026',
            'tanggal_uji' => now()->toDateString(),
            'berkas' => UploadedFile::fake()->create('BA-0942.pdf', 100, 'application/pdf'),
        ], $o);
    }

    public function test_antrean_hanya_karantina_urut_tanggal_donor_terlama(): void
    {
        $this->kantong(['kode_batch' => 'BATCH-BARU', 'tanggal_donor' => now()->subDay()->toDateString()]);
        $this->kantong(['kode_batch' => 'BATCH-LAMA', 'tanggal_donor' => now()->subDays(9)->toDateString()]);
        $this->kantong(['kode_batch' => 'BATCH-READY', 'status' => 'ready']);

        $this->get('/admin/uji-saring')->assertOk()
            ->assertSeeInOrder(['BATCH-LAMA', 'BATCH-BARU'])
            ->assertDontSee('BATCH-READY');
    }

    public function test_batch_terpilih_lewat_query(): void
    {
        $this->kantong(['kode_batch' => 'BATCH-LAMA', 'tanggal_donor' => now()->subDays(9)->toDateString()]);
        $b = $this->kantong(['kode_batch' => 'BATCH-PILIH']);

        $this->get('/admin/uji-saring?batch=' . $b->id)->assertSee('Hasil uji saring')->assertSee('Dipilih');
    }

    public function test_non_reaktif_mengubah_status_menjadi_ready_dan_menyimpan_berkas(): void
    {
        $k = $this->kantong();

        $this->post("/admin/uji-saring/{$k->id}", $this->hasil())->assertRedirect('/admin/uji-saring');

        $k->refresh();
        $this->assertEquals('ready', $k->status);
        $this->assertEquals('non_reaktif', $k->hasil_uji);
        $this->assertEquals('BA-0942/UTD/X/2026', $k->nomor_berita_acara);
        Storage::disk('local')->assertExists($k->berkas_berita_acara);
    }

    public function test_reaktif_menahan_kantong(): void
    {
        $k = $this->kantong();

        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['hasil_uji' => 'reaktif', 'catatan_uji' => 'HIV reaktif']));

        $this->assertEquals('ditahan', $k->fresh()->status);
        $this->assertEquals('reaktif', $k->fresh()->hasil_uji);
    }

    public function test_berkas_wajib_dan_format_serta_ukuran_dibatasi(): void
    {
        $k = $this->kantong();

        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['berkas' => null]))->assertSessionHasErrors('berkas');
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['berkas' => UploadedFile::fake()->create('virus.exe', 10)]))->assertSessionHasErrors('berkas');
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['berkas' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf')]))->assertSessionHasErrors('berkas');

        $this->assertEquals('karantina', $k->fresh()->status);
    }

    public function test_validasi_tanggal_dan_nomor_berita_acara(): void
    {
        $k = $this->kantong();

        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['tanggal_uji' => now()->subDays(10)->toDateString()]))->assertSessionHasErrors('tanggal_uji');
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['tanggal_uji' => now()->addDay()->toDateString()]))->assertSessionHasErrors('tanggal_uji');
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['nomor_berita_acara' => '']))->assertSessionHasErrors('nomor_berita_acara');
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['hasil_uji' => 'mungkin']))->assertSessionHasErrors('hasil_uji');
    }

    public function test_kantong_bukan_karantina_tidak_bisa_diuji_ulang(): void
    {
        $k = $this->kantong(['status' => 'ready']);

        $this->post("/admin/uji-saring/{$k->id}", $this->hasil(['hasil_uji' => 'reaktif']))->assertSessionHasErrors('kantong');
        $this->assertEquals('ready', $k->fresh()->status);
    }

    public function test_unduh_berkas_berita_acara(): void
    {
        $k = $this->kantong();
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil());

        $this->get("/admin/uji-saring/{$k->id}/berkas")->assertOk();
        $this->get('/admin/uji-saring/' . $this->kantong(['kode_batch' => 'BATCH-KOSONG'])->id . '/berkas')->assertNotFound();
    }

    public function test_peran_lain_dan_tamu_ditolak(): void
    {
        $k = $this->kantong();
        auth()->logout();
        $this->get('/admin/uji-saring')->assertRedirect('/login');

        $p = User::create(['name' => 'Petugas', 'email' => 'p@test.id', 'instansi' => 'RS', 'password' => 'password123']);
        $p->assignRole('petugas_rs');
        $this->actingAs($p)->get('/admin/uji-saring')->assertForbidden();
        $this->post("/admin/uji-saring/{$k->id}", $this->hasil())->assertForbidden();
    }
}
