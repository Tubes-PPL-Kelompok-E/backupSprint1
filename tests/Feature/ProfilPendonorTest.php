<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Profil pendonor dan pembaruan data kontak. */
class ProfilPendonorTest extends TestCase
{
    use Concerns, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPeran();
    }

    public function test_profil_menampilkan_data_riwayat_dan_kelayakan(): void
    {
        $u = $this->akun('pendonor');
        $p = $this->pendonor(['nama' => 'Siti Profil', 'no_hp' => '081234567890'], $u);
        \App\Models\Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays(10), 'volume_ml' => 350, 'lokasi' => 'UTD Pusat']);

        $this->actingAs($u)->get(route('pendonor.profil'))->assertOk()
            ->assertSee('Siti Profil')->assertSee('081234567890')->assertSee('UTD Pusat')->assertSee('Layak kembali mulai');
    }

    public function test_akun_belum_terhubung_melihat_pesan_hubungi_utd(): void
    {
        $this->actingAs($this->akun('pendonor'))->get(route('pendonor.profil'))->assertOk()->assertSee('Belum');
        $this->actingAs($this->akun('pendonor'))->put(route('pendonor.profil.update'), ['no_hp' => '081234567890', 'kota' => 'X'])->assertOk();
    }

    public function test_pendonor_memperbarui_kontak_dan_dicatat_di_log(): void
    {
        $u = $this->akun('pendonor');
        $p = $this->pendonor(['no_hp' => '081111111111', 'kota' => 'Jakarta Pusat'], $u);

        $this->actingAs($u)->put(route('pendonor.profil.update'), ['no_hp' => '0856 1234 5678', 'kota' => 'Bandung', 'alamat' => 'Jl. Baru 1'])
            ->assertRedirect(route('pendonor.profil'))->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('0856 1234 5678', $p->no_hp);
        $this->assertSame('Bandung', $p->kota);
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'pendonor_diubah', 'entitas_id' => $p->id, 'user_id' => $u->id]);
    }

    public function test_identitas_dan_golongan_tidak_bisa_diubah_lewat_profil(): void
    {
        $u = $this->akun('pendonor');
        $p = $this->pendonor(['nama' => 'Asli', 'golongan' => 'O', 'rhesus' => 'pos'], $u);

        $this->actingAs($u)->put(route('pendonor.profil.update'), [
            'no_hp' => '081234567890', 'kota' => 'Jakarta', 'nama' => 'Palsu', 'golongan' => 'AB', 'rhesus' => 'neg', 'tanggal_lahir' => '2000-01-01', 'user_id' => 999,
        ]);

        $p->refresh();
        $this->assertSame('Asli', $p->nama);
        $this->assertSame('O', $p->golongan);
        $this->assertSame('pos', $p->rhesus);
        $this->assertSame($u->id, $p->user_id);
    }

    public function test_kontak_tidak_valid_ditolak(): void
    {
        $u = $this->akun('pendonor');
        $p = $this->pendonor(['no_hp' => '081111111111'], $u);

        $this->actingAs($u)->put(route('pendonor.profil.update'), ['no_hp' => 'abc', 'kota' => 'Jakarta'])->assertSessionHasErrors('no_hp');
        $this->actingAs($u)->put(route('pendonor.profil.update'), ['no_hp' => '081234567890', 'kota' => ''])->assertSessionHasErrors('kota');
        $this->assertSame('081111111111', $p->fresh()->no_hp);
    }

    public function test_pendonor_tidak_bisa_mengubah_profil_pendonor_lain(): void
    {
        $u1 = $this->akun('pendonor');
        $u2 = $this->akun('pendonor');
        $p1 = $this->pendonor(['no_hp' => '081111111111'], $u1);
        $p2 = $this->pendonor(['no_hp' => '082222222222'], $u2);

        $this->actingAs($u1)->put(route('pendonor.profil.update'), ['no_hp' => '083333333333', 'kota' => 'Jakarta', 'pendonor_id' => $p2->id]);
        $this->assertSame('083333333333', $p1->fresh()->no_hp);
        $this->assertSame('082222222222', $p2->fresh()->no_hp);
    }

    public function test_peran_lain_tidak_boleh_membuka_profil_pendonor(): void
    {
        $this->actingAs($this->akun('admin_pmi'))->get(route('pendonor.profil'))->assertForbidden();
        $this->actingAs($this->akun('petugas_rs'))->put(route('pendonor.profil.update'), [])->assertForbidden();
    }
}
