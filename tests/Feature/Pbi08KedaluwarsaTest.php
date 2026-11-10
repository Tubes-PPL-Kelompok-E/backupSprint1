<?php

namespace Tests\Feature;

use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Status kedaluwarsa otomatis dan pencatatan pemusnahan kantong. */
class Pbi08KedaluwarsaTest extends TestCase
{
    use RefreshDatabase, Concerns;

    public function test_kantong_lewat_tanggal_otomatis_berstatus_kedaluwarsa_dan_tercatat(): void
    {
        $lewat = $this->kantong([], -1);
        $karantina = $this->kantong(['status' => 'karantina'], -3);
        $aman = $this->kantong([], 10);
        $hariIni = $this->kantong([], 0);   // kedaluwarsa hari ini masih boleh dipakai

        $this->artisan('bloodconnect:tandai-kedaluwarsa')->assertSuccessful();

        $this->assertSame('kedaluwarsa', $lewat->fresh()->status);
        $this->assertSame('kedaluwarsa', $karantina->fresh()->status);
        $this->assertSame('ready', $aman->fresh()->status);
        $this->assertSame('ready', $hariIni->fresh()->status);
        $this->assertSame(2, LogTransaksi::where('aksi', 'kantong_kedaluwarsa')->count());
        $this->assertSame(2, KantongDarah::siapPakai()->count());
    }

    public function test_dijalankan_berulang_tidak_menggandakan_log(): void
    {
        $this->kantong([], -2);

        $this->artisan('bloodconnect:tandai-kedaluwarsa')->assertSuccessful();
        $this->artisan('bloodconnect:tandai-kedaluwarsa')->assertSuccessful();

        $this->assertSame(1, LogTransaksi::where('aksi', 'kantong_kedaluwarsa')->count());
    }

    public function test_hanya_kantong_kedaluwarsa_yang_bisa_dimusnahkan(): void
    {
        $this->seedPeran();
        $admin = $this->akun('admin_pmi');
        $aman = $this->kantong([], 10);
        $exp = $this->kantong(['status' => 'kedaluwarsa'], -2);

        $this->actingAs($admin)->patch(route('admin.kantong.musnahkan', $aman))->assertSessionHasErrors('kantong');
        $this->assertSame('ready', $aman->fresh()->status);

        $this->patch(route('admin.kantong.musnahkan', $exp))->assertSessionHasNoErrors();
        $this->assertSame('dimusnahkan', $exp->fresh()->status);
        $this->assertTrue(LogTransaksi::where('aksi', 'kantong_dimusnahkan')->where('entitas_id', $exp->id)->exists());
    }
}
