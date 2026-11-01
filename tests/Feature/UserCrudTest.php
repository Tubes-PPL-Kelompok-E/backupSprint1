<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.id', 'instansi' => 'PMI', 'password' => 'password123']);
        $this->admin->assignRole('admin_pmi');
        $this->actingAs($this->admin);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'name' => 'Budi', 'email' => 'budi@test.id', 'role' => 'petugas_rs',
            'instansi' => 'RSUD', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ], $o);
    }

    public function test_create_akun_dengan_password_ter_hash(): void
    {
        $this->post('/admin/users', $this->payload())->assertRedirect('/admin/users');

        $u = User::where('email', 'budi@test.id')->first();
        $this->assertNotNull($u);
        $this->assertNotEquals('rahasia123', $u->password);
        $this->assertTrue(Hash::check('rahasia123', $u->password));
        $this->assertTrue($u->hasRole('petugas_rs'));
        $this->assertEquals('RSUD', $u->instansi);
    }

    public function test_email_harus_unik(): void
    {
        $this->post('/admin/users', $this->payload());
        $this->post('/admin/users', $this->payload())->assertSessionHasErrors('email');
        $this->assertEquals(1, User::where('email', 'budi@test.id')->count());
    }

    public function test_validasi_create(): void
    {
        $this->post('/admin/users', ['name' => ''])->assertSessionHasErrors(['name', 'email', 'role', 'instansi', 'password']);
        $this->post('/admin/users', $this->payload(['role' => 'hacker']))->assertSessionHasErrors('role');
        $this->post('/admin/users', $this->payload(['password' => '123', 'password_confirmation' => '123']))->assertSessionHasErrors('password');
    }

    public function test_read_daftar_akun(): void
    {
        $this->post('/admin/users', $this->payload());
        $this->get('/admin/users')->assertOk()->assertSee('budi@test.id');
        $this->get('/admin/users?q=budi')->assertOk()->assertSee('Budi');
    }

    public function test_filter_daftar_akun_berdasarkan_peran_dan_instansi(): void
    {
        $this->post('/admin/users', $this->payload());
        $this->post('/admin/users', $this->payload(['name' => 'Andi', 'email' => 'andi@test.id', 'role' => 'pendonor', 'instansi' => 'Universitas']));

        $this->get('/admin/users?role=petugas_rs')->assertSee('budi@test.id')->assertDontSee('andi@test.id');
        $this->get('/admin/users?instansi=Universitas')->assertSee('andi@test.id')->assertDontSee('budi@test.id');
        $this->get('/admin/users?role=pendonor&instansi=RSUD')->assertDontSee('andi@test.id')->assertDontSee('budi@test.id');
    }

    public function test_update_peran_dan_instansi(): void
    {
        $this->post('/admin/users', $this->payload());
        $u = User::where('email', 'budi@test.id')->first();

        $this->put("/admin/users/{$u->id}", ['name' => 'Budi S', 'role' => 'pendonor', 'instansi' => 'Umum'])
            ->assertRedirect('/admin/users');

        $u->refresh();
        $this->assertTrue($u->hasRole('pendonor'));
        $this->assertFalse($u->hasRole('petugas_rs'));
        $this->assertEquals('Umum', $u->instansi);
    }

    public function test_reset_password(): void
    {
        $this->post('/admin/users', $this->payload());
        $u = User::where('email', 'budi@test.id')->first();

        $this->put("/admin/users/{$u->id}/reset-password", ['password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertRedirect('/admin/users');

        $this->assertTrue(Hash::check('baru12345', $u->fresh()->password));
    }

    public function test_delete_soft_delete_dan_restore(): void
    {
        $this->post('/admin/users', $this->payload());
        $u = User::where('email', 'budi@test.id')->first();

        $this->delete("/admin/users/{$u->id}")->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', ['id' => $u->id]);

        $this->patch("/admin/users/{$u->id}/restore")->assertRedirect('/admin/users');
        $this->assertNotSoftDeleted('users', ['id' => $u->id]);
    }

    public function test_admin_tidak_bisa_menonaktifkan_diri_sendiri(): void
    {
        $this->delete("/admin/users/{$this->admin->id}")->assertSessionHasErrors('user');
        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }
}