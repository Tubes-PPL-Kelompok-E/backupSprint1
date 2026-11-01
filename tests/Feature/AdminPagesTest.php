<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $u = User::create(['name' => ucfirst($role), 'email' => "$role@test.id", 'instansi' => 'Test', 'password' => 'password123']);
        $u->assignRole($role);
        return $u;
    }

    public function test_admin_bisa_membuka_semua_halaman(): void
    {
        $this->actingAs($this->makeUser('admin_pmi'));

        foreach ([
            '/admin/dashboard'      => 'Dashboard Ketersediaan Stok Darah',
            '/admin/stok-darah'     => 'Urutan FEFO',
            '/admin/permintaan-rs'  => 'Permintaan Darah Rumah Sakit',
            '/admin/laporan'        => 'Jejak Audit Transaksi',
            '/admin/kegiatan-donor' => 'Kegiatan Donor Darah',
        ] as $url => $teks) {
            $this->get($url)->assertOk()->assertSee($teks);
        }
    }

    public function test_kegiatan_donor_menampilkan_pendonor_dari_database(): void
    {
        $this->makeUser('pendonor');
        $this->actingAs($this->makeUser('admin_pmi'))
            ->get('/admin/kegiatan-donor')->assertSee('pendonor@test.id');
    }

    public function test_peran_lain_dan_tamu_ditolak(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->actingAs($this->makeUser('petugas_rs'))->get('/admin/stok-darah')->assertForbidden();
    }
}
