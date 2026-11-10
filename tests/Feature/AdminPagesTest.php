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
            '/admin/pendonor'       => 'Data Pendonor',
            '/admin/lokasi-penyimpanan' => 'Lokasi Penyimpanan',
            '/admin/kegiatan-donor' => 'Kegiatan Donor Darah',
        ] as $url => $teks) {
            $this->get($url)->assertOk()->assertSee($teks);
        }
    }

    public function test_data_pendonor_menampilkan_pendonor_dari_database(): void
    {
        \App\Models\Pendonor::create([
            'kode_donor' => 'D-000001', 'nama' => 'Budi Pendonor', 'tanggal_lahir' => '1995-05-05', 'jenis_kelamin' => 'L',
            'golongan' => 'O', 'rhesus' => 'pos', 'kota' => 'Jakarta Pusat',
        ]);

        $this->actingAs($this->makeUser('admin_pmi'))
            ->get('/admin/pendonor')->assertOk()->assertSee('Budi Pendonor')->assertSee('D-000001');
    }

    public function test_peran_lain_dan_tamu_ditolak(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->actingAs($this->makeUser('petugas_rs'))->get('/admin/stok-darah')->assertForbidden();
    }
}
