<?php

namespace Tests\Feature;

use App\Models\BatasMinimumStok;
use App\Models\Instansi;
use App\Models\LogTransaksi;
use App\Services\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** batas minimum stok dan dashboard. */
class Pbi11DashboardStokTest extends TestCase
{
    use RefreshDatabase, Concerns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPeran();
    }

    public function test_ringkasan_hanya_menghitung_kantong_ready_yang_belum_kedaluwarsa(): void
    {
        $this->kantong();                                         // siap
        $this->kantong([], 3);                                    // siap, segera kedaluwarsa (< 7 hari)
        $this->kantong([], -2);                                   // Ready tapi sudah lewat tanggal -> tidak dihitung
        $this->kantong(['status' => 'karantina']);                // bukan Ready
        $this->kantong(['status' => 'ditahan']);                 // bukan Ready

        $r = app(StokService::class)->dashboard()['ringkasan'];

        $this->assertSame(2, $r['siap_pakai']);
        $this->assertSame(1, $r['segera_kedaluwarsa']);
    }

    public function test_golongan_di_bawah_batas_minimum_ditandai_kritis(): void
    {
        // Batas bawaan 30 PRC + 10 WB + 5 TC + 5 FFP = 50. Golongan A+ diberi 3 kantong -> kritis.
        foreach (range(1, 3) as $i) {
            $this->kantong(['golongan' => 'A', 'rhesus' => 'pos']);
        }
        BatasMinimumStok::whereRaw('1=1')->delete();

        $baris = collect(app(StokService::class)->perGolongan())->keyBy('label');

        $this->assertSame(3, $baris['A (+)']['jumlah']);
        $this->assertSame(50, $baris['A (+)']['minimum']);
        $this->assertTrue($baris['A (+)']['kritis']);
    }

    public function test_filter_komponen_membandingkan_dengan_batas_komponen_itu(): void
    {
        foreach (range(1, 12) as $i) {
            $this->kantong(['golongan' => 'B', 'rhesus' => 'pos', 'komponen' => 'wb']);
        }

        $wb = collect(app(StokService::class)->perGolongan('wb'))->keyBy('label')['B (+)'];

        $this->assertSame(12, $wb['jumlah']);
        $this->assertSame(10, $wb['minimum']);
        $this->assertFalse($wb['kritis']);                        // 12 >= batas WB (10)
    }

    public function test_admin_melihat_dashboard_dan_peringatan_stok_rendah(): void
    {
        $this->kantong(['golongan' => 'AB', 'rhesus' => 'neg']);

        $this->actingAs($this->akun('admin_pmi'))->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('TOTAL KANTONG READY')
            ->assertSee('FEFO Alert')
            ->assertSee('di bawah batas minimum', false);
    }

    public function test_dashboard_tanpa_data_menampilkan_nol_dan_pesan(): void
    {
        $this->actingAs($this->akun('admin_pmi'))->get('/admin/dashboard')
            ->assertOk()->assertViewHas('data', fn ($d) => $d['ada_data'] === false && $d['ringkasan']['siap_pakai'] === 0)
            ->assertSee('Belum ada data kantong darah');
    }

    public function test_endpoint_polling_json_untuk_admin(): void
    {
        $this->kantong();

        $this->actingAs($this->akun('admin_pmi'))->getJson('/dashboard/data')
            ->assertOk()
            ->assertJsonPath('ringkasan.siap_pakai', 1)
            ->assertJsonStructure(['ringkasan', 'stok', 'kritis', 'alert', 'diperbarui']);
    }

    public function test_admin_rs_melihat_dashboard_baca_saja_dan_tidak_bisa_mengubah_batas(): void
    {
        Instansi::create(['kode' => 'RS-A', 'nama' => 'RS Alpha', 'tipe' => 'rumah_sakit', 'alamat' => 'x', 'kota' => 'Jakarta']);
        $this->kantong();
        $petugas = $this->akun('petugas_rs', 'RS Alpha');

        $this->actingAs($petugas)->getJson('/dashboard/data')->assertOk()->assertJsonPath('ringkasan.siap_pakai', 1);
        $this->get('/petugas/dashboard')->assertOk()->assertSee('KATALOG STOK', false);
        // Tampilan baca saja: halaman admin ditolak
        $this->get('/admin/batas-minimum')->assertForbidden();
        $this->put('/admin/batas-minimum', ['batas' => []])->assertForbidden();
    }

    public function test_pendonor_dan_tamu_tidak_boleh_membuka_data_dashboard(): void
    {
        $this->getJson('/dashboard/data')->assertUnauthorized();
        $this->actingAs($this->akun('pendonor'))->getJson('/dashboard/data')->assertForbidden();
    }

    public function test_admin_mengubah_batas_minimum_dan_tercatat_di_log(): void
    {
        $admin = $this->akun('admin_pmi');
        $this->actingAs($admin);

        $this->put('/admin/batas-minimum', ['batas' => ['A' => ['pos' => ['prc' => 80]]] + $this->batasPenuh()])
            ->assertRedirect('/admin/batas-minimum');

        $this->assertSame(80, BatasMinimumStok::peta()['A|pos|prc']);
        $this->assertSame(1, BatasMinimumStok::count());           // hanya yang berubah yang disimpan
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'batas_minimum_diubah', 'user_id' => $admin->id]);

        $this->delete('/admin/batas-minimum')->assertRedirect();
        $this->assertSame(30, BatasMinimumStok::peta()['A|pos|prc']);   // kembali ke bawaan config
    }

    public function test_batas_minimum_negatif_atau_bukan_angka_ditolak(): void
    {
        $this->actingAs($this->akun('admin_pmi'));

        $this->put('/admin/batas-minimum', ['batas' => ['A' => ['pos' => ['prc' => -5]]] + $this->batasPenuh()])->assertSessionHasErrors();
        $this->put('/admin/batas-minimum', ['batas' => ['A' => ['pos' => ['prc' => 'abc']]] + $this->batasPenuh()])->assertSessionHasErrors();
        $this->assertSame(0, BatasMinimumStok::count());
    }

    /** Form lengkap 4 golongan x 2 rhesus x 4 komponen berisi nilai bawaan. */
    private function batasPenuh(): array
    {
        $b = [];
        foreach (['A', 'B', 'AB', 'O'] as $g) {
            foreach (['pos', 'neg'] as $r) {
                foreach (config('bloodconnect.batas_minimum_default') as $k => $n) {
                    $b[$g][$r][$k] = $n;
                }
            }
        }

        return $b;
    }
}
