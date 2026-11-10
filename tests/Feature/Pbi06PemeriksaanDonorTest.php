<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Services\PemeriksaanDonorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** pemeriksaan pra-donor dan penentuan kelayakan. */
class Pbi06PemeriksaanDonorTest extends TestCase
{
    use Concerns, RefreshDatabase;

    private array $normal = ['hemoglobin' => 14.0, 'sistolik' => 120, 'diastolik' => 80, 'berat_kg' => 60, 'suhu_tubuh' => 36.6, 'nadi' => 72];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPeran();
    }

    public function test_nilai_normal_dinyatakan_layak(): void
    {
        $e = app(PemeriksaanDonorService::class)->evaluasi($this->pendonor(), $this->normal);
        $this->assertSame('layak', $e['hasil']);
        $this->assertSame([], $e['alasan']);
    }

    #[DataProvider('tidakLayak')]
    public function test_setiap_ambang_yang_dilanggar_menghasilkan_tidak_layak(array $ubah, string $kata): void
    {
        $e = app(PemeriksaanDonorService::class)->evaluasi($this->pendonor(), $ubah + $this->normal);
        $this->assertSame('tidak_layak', $e['hasil']);
        $this->assertStringContainsString($kata, implode(' ', $e['alasan']));
    }

    public static function tidakLayak(): array
    {
        return [
            'hb rendah' => [['hemoglobin' => 11.0], 'Hemoglobin'],
            'hb tinggi' => [['hemoglobin' => 18.0], 'Hemoglobin'],
            'sistolik tinggi' => [['sistolik' => 170], 'sistolik'],
            'sistolik rendah' => [['sistolik' => 80, 'diastolik' => 60], 'sistolik'],
            'diastolik tinggi' => [['diastolik' => 105], 'diastolik'],
            'berat kurang' => [['berat_kg' => 44.9], 'Berat badan'],
            'demam' => [['suhu_tubuh' => 38.0], 'Suhu'],
            'nadi cepat' => [['nadi' => 110], 'nadi'],
        ];
    }

    public function test_nilai_tepat_di_batas_masih_layak(): void
    {
        $e = app(PemeriksaanDonorService::class)->evaluasi($this->pendonor(), ['hemoglobin' => 12.5, 'sistolik' => 160, 'diastolik' => 100, 'berat_kg' => 45, 'suhu_tubuh' => 37.5, 'nadi' => 100]);
        $this->assertSame('layak', $e['hasil']);
    }

    public function test_jeda_donor_belum_terpenuhi_tidak_layak_meski_nilai_normal(): void
    {
        $p = $this->pendonor();
        Donasi::create(['pendonor_id' => $p->id, 'tanggal_donor' => today()->subDays(10), 'volume_ml' => 350]);
        $e = app(PemeriksaanDonorService::class)->evaluasi($p, $this->normal);
        $this->assertSame('tidak_layak', $e['hasil']);
        $this->assertStringContainsString('Jeda minimal 60 hari', $e['alasan'][0]);
    }

    public function test_usia_di_luar_rentang_tidak_layak(): void
    {
        $muda = $this->pendonor(['tanggal_lahir' => today()->subYears(16)->toDateString()]);
        $tua = $this->pendonor(['tanggal_lahir' => today()->subYears(70)->toDateString()]);
        foreach ([$muda, $tua] as $p) {
            $e = app(PemeriksaanDonorService::class)->evaluasi($p, $this->normal);
            $this->assertSame('tidak_layak', $e['hasil']);
            $this->assertStringContainsString('Usia', implode(' ', $e['alasan']));
        }
    }

    public function test_petugas_mencatat_pemeriksaan_dan_hasil_tersimpan_beserta_log(): void
    {
        $a = $this->akun('admin_pmi');
        $p = $this->pendonor();

        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), $this->normal)->assertSessionHas('success');
        $this->assertDatabaseHas('pemeriksaan_donor', ['pendonor_id' => $p->id, 'hasil' => 'layak', 'diperiksa_oleh' => $a->id]);
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'pemeriksaan_donor', 'entitas_id' => $p->id]);

        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), ['hemoglobin' => 10.0] + $this->normal)->assertSessionHas('warning');
        $this->actingAs($a)->get(route('admin.pendonor.show', $p))->assertSee('Tidak layak')->assertSee('Hemoglobin 10');
    }

    public function test_input_tidak_lengkap_atau_tidak_masuk_akal_ditolak(): void
    {
        $a = $this->akun('admin_pmi');
        $p = $this->pendonor();
        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), [])->assertSessionHasErrors(['hemoglobin', 'sistolik', 'diastolik', 'berat_kg', 'suhu_tubuh', 'nadi']);
        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), ['hemoglobin' => 99] + $this->normal)->assertSessionHasErrors('hemoglobin');
        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), ['diastolik' => 130] + $this->normal)->assertSessionHasErrors('diastolik');
        $this->assertDatabaseCount('pemeriksaan_donor', 0);
    }

    public function test_hanya_admin_pmi_boleh_memeriksa(): void
    {
        $p = $this->pendonor();
        $this->actingAs($this->akun('pendonor'))->post(route('admin.pendonor.pemeriksaan', $p), $this->normal)->assertForbidden();
        $this->actingAs($this->akun('petugas_rs'))->post(route('admin.pendonor.pemeriksaan', $p), $this->normal)->assertForbidden();
    }

    public function test_donasi_ditolak_bila_pemeriksaan_hari_ini_tidak_layak_dan_diterima_bila_layak(): void
    {
        $a = $this->akun('admin_pmi');
        $p = $this->pendonor();
        $donasi = ['tanggal_donor' => today()->toDateString(), 'volume_ml' => 350, 'lokasi' => 'UTD'];

        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), ['hemoglobin' => 9.0] + $this->normal);
        $this->actingAs($a)->post(route('admin.pendonor.donasi', $p), $donasi)->assertSessionHasErrors('donasi');
        $this->assertSame(0, $p->donasi()->count());

        $this->actingAs($a)->post(route('admin.pendonor.pemeriksaan', $p), $this->normal); // pemeriksaan ulang: layak
        // pemeriksaan terbaru (layak) menggantikan yang lama
        $this->actingAs($a)->post(route('admin.pendonor.donasi', $p), $donasi)->assertSessionHas('success');
        $this->assertSame(1, $p->donasi()->count());
    }

    public function test_perubahan_data_pendonor_oleh_petugas_dicatat_di_log(): void
    {
        $a = $this->akun('admin_pmi');
        $p = $this->pendonor(['no_hp' => '081111111111']);
        $this->actingAs($a)->put(route('admin.pendonor.update', $p), [
            'nama' => $p->nama, 'tanggal_lahir' => '1995-01-01', 'jenis_kelamin' => 'L', 'golongan' => 'O', 'rhesus' => 'pos',
            'no_hp' => '082222222222', 'kota' => 'Jakarta Pusat',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'pendonor_diubah', 'entitas_id' => $p->id]);

        // tanpa perubahan -> tidak menambah log
        $n = \App\Models\LogTransaksi::count();
        $this->actingAs($a)->put(route('admin.pendonor.update', $p), [
            'nama' => $p->nama, 'tanggal_lahir' => '1995-01-01', 'jenis_kelamin' => 'L', 'golongan' => 'O', 'rhesus' => 'pos',
            'no_hp' => '082222222222', 'kota' => 'Jakarta Pusat',
        ]);
        $this->assertSame($n, \App\Models\LogTransaksi::count());
    }
}
