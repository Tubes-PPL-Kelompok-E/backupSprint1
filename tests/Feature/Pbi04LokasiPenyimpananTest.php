<?php

namespace Tests\Feature;

use App\Models\LokasiPenyimpanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** lokasi penyimpanan kantong. */
class Pbi04LokasiPenyimpananTest extends TestCase
{
    use Concerns, RefreshDatabase;

    private function admin()
    {
        $this->seedPeran();

        return $this->akun('admin_pmi');
    }

    private function lokasi(array $o = []): LokasiPenyimpanan
    {
        return LokasiPenyimpanan::create($o + ['nama' => 'Cold Room ' . uniqid(), 'jenis' => 'cold_room', 'kapasitas' => 5, 'aktif' => true]);
    }

    public function test_hanya_admin_pmi_boleh_mengakses(): void
    {
        $this->seedPeran();
        $this->actingAs($this->akun('pendonor'))->get(route('admin.lokasi'))->assertForbidden();
        $this->actingAs($this->akun('petugas_rs'))->post(route('admin.lokasi.store'), [])->assertForbidden();
    }

    public function test_tambah_lokasi_tersimpan_dan_dicatat_di_log(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post(route('admin.lokasi.store'), ['nama' => 'Cold Room 9', 'jenis' => 'cold_room', 'kapasitas' => 100, 'suhu_min' => 2, 'suhu_maks' => 6])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('lokasi_penyimpanan', ['nama' => 'Cold Room 9', 'kapasitas' => 100, 'aktif' => true]);
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'lokasi_diubah']);
        $this->actingAs($a)->get(route('admin.lokasi'))->assertSee('Cold Room 9');
    }

    public function test_validasi_nama_unik_kapasitas_dan_suhu(): void
    {
        $a = $this->admin();
        $this->lokasi(['nama' => 'Dobel']);
        $this->actingAs($a)->post(route('admin.lokasi.store'), ['nama' => 'Dobel', 'jenis' => 'cold_room', 'kapasitas' => 5])->assertSessionHasErrors('nama');
        $this->actingAs($a)->post(route('admin.lokasi.store'), ['nama' => 'X', 'jenis' => 'cold_room', 'kapasitas' => 0])->assertSessionHasErrors('kapasitas');
        $this->actingAs($a)->post(route('admin.lokasi.store'), ['nama' => 'Y', 'jenis' => 'aneh', 'kapasitas' => 5])->assertSessionHasErrors('jenis');
        $this->actingAs($a)->post(route('admin.lokasi.store'), ['nama' => 'Z', 'jenis' => 'cold_room', 'kapasitas' => 5, 'suhu_min' => 8, 'suhu_maks' => 2])->assertSessionHasErrors('suhu_maks');
    }

    public function test_keterisian_dihitung_dari_kantong_yang_menempati(): void
    {
        $l = $this->lokasi(['nama' => 'CR-T', 'kapasitas' => 3]);
        $this->kantong(['lokasi_simpan' => 'CR-T']);
        $this->kantong(['lokasi_simpan' => 'CR-T', 'status' => 'karantina']);
        $this->kantong(['lokasi_simpan' => 'CR-T', 'status' => 'dimusnahkan']); // tidak menempati
        $this->assertSame(2, $l->terisi());
        $this->assertSame(1, $l->sisa());
        $this->assertFalse($l->penuh());
    }

    public function test_kapasitas_tidak_boleh_di_bawah_isi_dan_tidak_bisa_nonaktif_bila_terisi(): void
    {
        $a = $this->admin();
        $l = $this->lokasi(['nama' => 'CR-U', 'kapasitas' => 5]);
        $this->kantong(['lokasi_simpan' => 'CR-U']);
        $this->kantong(['lokasi_simpan' => 'CR-U']);

        $this->actingAs($a)->put(route('admin.lokasi.update', $l), ['nama' => 'CR-U', 'jenis' => 'cold_room', 'kapasitas' => 1, 'aktif' => 1])->assertSessionHasErrors('kapasitas');
        $this->actingAs($a)->put(route('admin.lokasi.update', $l), ['nama' => 'CR-U', 'jenis' => 'cold_room', 'kapasitas' => 5])->assertSessionHasErrors('aktif');
        $this->actingAs($a)->put(route('admin.lokasi.update', $l), ['nama' => 'CR-U', 'jenis' => 'cold_room', 'kapasitas' => 9, 'aktif' => 1])->assertSessionHas('success');
        $this->assertSame(9, $l->fresh()->kapasitas);
    }

    public function test_lokasi_dengan_riwayat_kantong_tidak_bisa_dihapus_tetapi_kosong_bisa(): void
    {
        $a = $this->admin();
        $dipakai = $this->lokasi(['nama' => 'CR-V']);
        $this->kantong(['lokasi_simpan' => 'CR-V', 'status' => 'dimusnahkan']);
        $kosong = $this->lokasi(['nama' => 'CR-W']);

        $this->actingAs($a)->delete(route('admin.lokasi.destroy', $dipakai))->assertSessionHasErrors('lokasi');
        $this->assertDatabaseHas('lokasi_penyimpanan', ['nama' => 'CR-V']);
        $this->actingAs($a)->delete(route('admin.lokasi.destroy', $kosong))->assertSessionHas('success');
        $this->assertDatabaseMissing('lokasi_penyimpanan', ['nama' => 'CR-W']);
    }

    private function ujiSaring($kantong, array $o = [])
    {
        Storage::fake('local');

        return $this->post(route('admin.uji-saring.store', $kantong), $o + [
            'hasil_uji' => 'non_reaktif', 'nomor_berita_acara' => 'BA-1', 'tanggal_uji' => today()->toDateString(),
            'berkas' => UploadedFile::fake()->create('ba.pdf', 10, 'application/pdf'),
        ]);
    }

    public function test_uji_saring_memakai_lokasi_pilihan_dan_menolak_lokasi_penuh_atau_nonaktif(): void
    {
        $a = $this->admin();
        $this->actingAs($a);
        $baik = $this->lokasi(['nama' => 'CR-OK', 'kapasitas' => 5]);
        $penuh = $this->lokasi(['nama' => 'CR-FULL', 'kapasitas' => 1]);
        $this->kantong(['lokasi_simpan' => 'CR-FULL']);
        $mati = $this->lokasi(['nama' => 'CR-OFF', 'aktif' => false]);

        $k1 = $this->kantong(['status' => 'karantina', 'lokasi_simpan' => 'Karantina']);
        $this->ujiSaring($k1, ['lokasi_simpan' => 'CR-FULL'])->assertSessionHasErrors('lokasi_simpan');
        $this->assertSame('karantina', $k1->fresh()->status);
        $this->ujiSaring($k1, ['lokasi_simpan' => 'CR-OFF'])->assertSessionHasErrors('lokasi_simpan');
        $this->ujiSaring($k1, ['lokasi_simpan' => 'CR-OK'])->assertSessionHas('success');
        $this->assertSame('CR-OK', $k1->fresh()->lokasi_simpan);
        $this->assertSame('ready', $k1->fresh()->status);
    }

    public function test_uji_saring_tanpa_pilihan_memakai_lokasi_aktif_yang_masih_punya_ruang(): void
    {
        $this->actingAs($this->admin());
        $this->lokasi(['nama' => 'Cold Room 1']);
        $k = $this->kantong(['status' => 'karantina', 'lokasi_simpan' => 'Karantina']);
        $this->ujiSaring($k)->assertSessionHas('success');
        $this->assertSame('Cold Room 1', $k->fresh()->lokasi_simpan);
    }
}
