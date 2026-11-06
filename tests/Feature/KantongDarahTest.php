<?php

namespace Tests\Feature;

use App\Models\KantongDarah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KantongDarahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.id', 'instansi' => 'PMI', 'password' => 'password123']);
        $admin->assignRole('admin_pmi');
        $this->actingAs($admin);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'kode_batch' => 'BATCH-TEST-A01', 'tanggal_donor' => now()->subDays(2)->toDateString(),
            'volume_ml' => 350, 'komponen' => 'prc', 'golongan' => 'A', 'rhesus' => 'pos',
        ], $o);
    }

    public function test_halaman_input_tampil(): void
    {
        $this->get('/admin/kantong-darah/create')->assertOk()->assertSee('Input Kantong Darah Masuk');
    }

    public function test_simpan_kantong_berstatus_karantina_dan_kedaluwarsa_dihitung(): void
    {
        $this->post('/admin/kantong-darah', $this->payload())->assertRedirect('/admin/stok-darah');

        $k = KantongDarah::first();
        $this->assertEquals('karantina', $k->status);
        $this->assertEquals('BATCH-TEST-A01', $k->kode_batch);
        $this->assertEquals(now()->subDays(2)->addDays(35)->toDateString(), $k->tanggal_kedaluwarsa->toDateString());
    }

    public function test_masa_simpan_berbeda_per_komponen(): void
    {
        $this->post('/admin/kantong-darah', $this->payload(['kode_batch' => 'BATCH-TC-001', 'komponen' => 'tc']));
        $tc = KantongDarah::where('kode_batch', 'BATCH-TC-001')->first();

        $this->assertEquals(now()->subDays(2)->addDays(5)->toDateString(), $tc->tanggal_kedaluwarsa->toDateString());
    }

    public function test_kode_batch_otomatis_jika_kosong(): void
    {
        $tgl = '2026-10-06';
        $this->post('/admin/kantong-darah', $this->payload(['kode_batch' => '', 'tanggal_donor' => $tgl]));
        $this->post('/admin/kantong-darah', $this->payload(['kode_batch' => '', 'tanggal_donor' => $tgl]));

        $this->assertDatabaseHas('kantong_darah', ['kode_batch' => 'BATCH-20261006-A01']);
        $this->assertDatabaseHas('kantong_darah', ['kode_batch' => 'BATCH-20261006-A02']);
    }

    public function test_kode_batch_harus_unik(): void
    {
        $this->post('/admin/kantong-darah', $this->payload());
        $this->post('/admin/kantong-darah', $this->payload())->assertSessionHasErrors('kode_batch');
        $this->assertEquals(1, KantongDarah::count());
    }

    public function test_tanggal_donor_masa_depan_ditolak(): void
    {
        $this->post('/admin/kantong-darah', $this->payload(['tanggal_donor' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('tanggal_donor');
    }

    public function test_validasi_input(): void
    {
        $this->post('/admin/kantong-darah', [])->assertSessionHasErrors(['tanggal_donor', 'volume_ml', 'komponen', 'golongan', 'rhesus']);
        $this->post('/admin/kantong-darah', $this->payload(['volume_ml' => 5]))->assertSessionHasErrors('volume_ml');
        $this->post('/admin/kantong-darah', $this->payload(['golongan' => 'X']))->assertSessionHasErrors('golongan');
        $this->post('/admin/kantong-darah', $this->payload(['komponen' => 'abc']))->assertSessionHasErrors('komponen');
    }

    public function test_simpan_dan_tambah_lagi_kembali_ke_form(): void
    {
        $this->post('/admin/kantong-darah', $this->payload() + ['tambah_lagi' => 1])
            ->assertRedirect('/admin/kantong-darah/create');
    }

    public function test_cek_batch_json(): void
    {
        $this->post('/admin/kantong-darah', $this->payload());

        $this->getJson('/admin/kantong-darah/cek-batch?kode=BATCH-TEST-A01')->assertJson(['tersedia' => false]);
        $this->getJson('/admin/kantong-darah/cek-batch?kode=BATCH-BARU-A01&tanggal=2026-10-06&golongan=A')
            ->assertJson(['tersedia' => true, 'saran' => 'BATCH-20261006-A01']);
    }

    public function test_stok_urut_fefo_pencarian_dan_filter(): void
    {
        $this->post('/admin/kantong-darah', $this->payload(['kode_batch' => 'BATCH-BARU-001', 'tanggal_donor' => now()->subDay()->toDateString()]));
        $this->post('/admin/kantong-darah', $this->payload(['kode_batch' => 'BATCH-LAMA-001', 'tanggal_donor' => now()->subDays(10)->toDateString(), 'golongan' => 'O']));

        $this->get('/admin/stok-darah')->assertOk()->assertSeeInOrder(['BATCH-LAMA-001', 'BATCH-BARU-001']);
        $this->get('/admin/stok-darah?q=BARU')->assertSee('BATCH-BARU-001')->assertDontSee('BATCH-LAMA-001');
        $this->get('/admin/stok-darah?golongan=O')->assertSee('BATCH-LAMA-001')->assertDontSee('BATCH-BARU-001');
        $this->get('/admin/stok-darah?status=ready')->assertSee('Tidak ada kantong yang cocok');
    }

    public function test_catat_pemusnahan_hanya_untuk_kantong_kedaluwarsa(): void
    {
        $segar = KantongDarah::create($this->payload() + ['tanggal_kedaluwarsa' => now()->addDays(10)->toDateString()]);
        $basi  = KantongDarah::create($this->payload(['kode_batch' => 'BATCH-BASI-001']) + ['tanggal_kedaluwarsa' => now()->subDay()->toDateString()]);

        $this->patch("/admin/kantong-darah/{$segar->id}/musnahkan")->assertSessionHasErrors('kantong');
        $this->assertNotEquals('dimusnahkan', $segar->fresh()->status);

        $this->patch("/admin/kantong-darah/{$basi->id}/musnahkan")->assertSessionHas('success');
        $this->assertEquals('dimusnahkan', $basi->fresh()->status);
    }

    public function test_peran_lain_dan_tamu_ditolak(): void
    {
        auth()->logout();
        $this->get('/admin/stok-darah')->assertRedirect('/login');

        $p = User::create(['name' => 'Petugas', 'email' => 'p@test.id', 'instansi' => 'RS', 'password' => 'password123']);
        $p->assignRole('petugas_rs');
        $this->actingAs($p)->get('/admin/kantong-darah/create')->assertForbidden();
        $this->post('/admin/kantong-darah', $this->payload())->assertForbidden();
    }
}
