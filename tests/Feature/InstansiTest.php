<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstansiTest extends TestCase
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
            'kode' => 'RS-100', 'nama' => 'RS Harapan Kita', 'tipe' => 'rumah_sakit',
            'alamat' => 'Jl. Letjen S. Parman', 'kota' => 'Jakarta Barat',
            'telepon' => '021-5684093', 'email' => 'info@harapankita.id',
        ], $o);
    }

    // ---------- CREATE ----------
    public function test_create_instansi_rumah_sakit(): void
    {
        $this->post('/admin/instansi', $this->payload())->assertRedirect('/admin/instansi');
        $this->assertDatabaseHas('instansi', ['kode' => 'RS-100', 'nama' => 'RS Harapan Kita', 'tipe' => 'rumah_sakit']);
    }

    public function test_create_instansi_pmi(): void
    {
        $this->post('/admin/instansi', $this->payload(['kode' => 'PMI-100', 'nama' => 'UTD PMI Kota', 'tipe' => 'pmi']));
        $this->assertDatabaseHas('instansi', ['kode' => 'PMI-100', 'tipe' => 'pmi']);
    }

    public function test_kode_dibuat_otomatis_jika_kosong(): void
    {
        $this->post('/admin/instansi', $this->payload(['kode' => '']));
        $this->post('/admin/instansi', $this->payload(['kode' => '', 'nama' => 'RS Kedua']));
        $this->post('/admin/instansi', $this->payload(['kode' => '', 'nama' => 'UTD Satu', 'tipe' => 'pmi']));

        $this->assertDatabaseHas('instansi', ['nama' => 'RS Harapan Kita', 'kode' => 'RS-001']);
        $this->assertDatabaseHas('instansi', ['nama' => 'RS Kedua', 'kode' => 'RS-002']);
        $this->assertDatabaseHas('instansi', ['nama' => 'UTD Satu', 'kode' => 'PMI-001']);
    }

    public function test_kode_huruf_kecil_dinormalkan_menjadi_besar(): void
    {
        $this->post('/admin/instansi', $this->payload(['kode' => 'rs-abc']));
        $this->assertDatabaseHas('instansi', ['kode' => 'RS-ABC']);
    }

    public function test_kode_harus_unik(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $this->post('/admin/instansi', $this->payload(['nama' => 'RS Lain']))->assertSessionHasErrors('kode');
        $this->assertEquals(1, Instansi::count());
    }

    public function test_kode_instansi_terhapus_tidak_bisa_dipakai_ulang(): void
    {
        $this->post('/admin/instansi', $this->payload());
        Instansi::first()->delete();

        $this->post('/admin/instansi', $this->payload(['nama' => 'RS Baru']))->assertSessionHasErrors('kode');
    }

    public function test_validasi_create(): void
    {
        $this->post('/admin/instansi', [])->assertSessionHasErrors(['nama', 'tipe', 'alamat', 'kota']);
        $this->post('/admin/instansi', $this->payload(['tipe' => 'apotek']))->assertSessionHasErrors('tipe');
        $this->post('/admin/instansi', $this->payload(['kode' => 'A!']))->assertSessionHasErrors('kode');
        $this->post('/admin/instansi', $this->payload(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->post('/admin/instansi', $this->payload(['telepon' => 'abc']))->assertSessionHasErrors('telepon');
        $this->assertEquals(0, Instansi::count());
    }

    // ---------- READ ----------
    public function test_read_daftar_instansi(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $this->get('/admin/instansi')->assertOk()->assertSee('RS-100')->assertSee('RS Harapan Kita');
    }

    public function test_pencarian_dan_filter_tipe(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $this->post('/admin/instansi', $this->payload(['kode' => 'PMI-100', 'nama' => 'UTD PMI Bandung', 'tipe' => 'pmi', 'kota' => 'Bandung']));

        $this->get('/admin/instansi?q=Bandung')->assertSee('UTD PMI Bandung')->assertDontSee('RS Harapan Kita');
        $this->get('/admin/instansi?q=RS-100')->assertSee('RS Harapan Kita')->assertDontSee('UTD PMI Bandung');
        $this->get('/admin/instansi?tipe=pmi')->assertSee('UTD PMI Bandung')->assertDontSee('RS Harapan Kita');
        $this->get('/admin/instansi?q=tidakada')->assertSee('Tidak ada instansi yang cocok');
    }

    // ---------- UPDATE ----------
    public function test_update_data_instansi(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $i = Instansi::first();

        $this->put("/admin/instansi/{$i->id}", $this->payload(['nama' => 'RS Harapan Kita Baru', 'kota' => 'Jakarta Pusat']))
            ->assertRedirect('/admin/instansi');

        $this->assertDatabaseHas('instansi', ['id' => $i->id, 'nama' => 'RS Harapan Kita Baru', 'kota' => 'Jakarta Pusat']);
    }

    public function test_kode_tidak_bisa_diubah_saat_update(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $i = Instansi::first();

        $this->put("/admin/instansi/{$i->id}", $this->payload(['kode' => 'RS-999']));
        $this->assertEquals('RS-100', $i->fresh()->kode);
    }

    public function test_validasi_update(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $i = Instansi::first();

        $this->put("/admin/instansi/{$i->id}", $this->payload(['nama' => '']))->assertSessionHasErrors('nama');
    }

    // ---------- DELETE ----------
    public function test_delete_instansi(): void
    {
        $this->post('/admin/instansi', $this->payload());
        $i = Instansi::first();

        $this->delete("/admin/instansi/{$i->id}")->assertRedirect('/admin/instansi');

        $this->assertSoftDeleted('instansi', ['id' => $i->id]);
        // Pesan sukses (flash) memuat nama instansi, jadi cek lewat kode yang hanya ada di tabel
        $this->get('/admin/instansi')->assertDontSee('RS-100');
    }

    // ---------- AKSES ----------
    public function test_peran_selain_admin_dan_tamu_ditolak(): void
    {
        auth()->logout();
        $this->get('/admin/instansi')->assertRedirect('/login');

        $p = User::create(['name' => 'Petugas', 'email' => 'p@test.id', 'instansi' => 'RS', 'password' => 'password123']);
        $p->assignRole('petugas_rs');
        $this->actingAs($p)->get('/admin/instansi')->assertForbidden();
        $this->post('/admin/instansi', $this->payload())->assertForbidden();
    }
}
