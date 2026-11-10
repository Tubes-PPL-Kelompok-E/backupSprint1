<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use App\Models\LokasiPenyimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** kelola lokasi penyimpanan kantong dan kapasitasnya. */
class LokasiPenyimpananController extends Controller
{
    public function index()
    {
        return view('admin.lokasi.index', [
            'title'  => 'Lokasi Penyimpanan',
            'lokasi' => LokasiPenyimpanan::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $l = LokasiPenyimpanan::create($data);
        $this->log($l, "Lokasi penyimpanan \"{$l->nama}\" ditambahkan (kapasitas {$l->kapasitas}).", null, $l->only(['nama', 'jenis', 'kapasitas', 'aktif']));

        return back()->with('success', "Lokasi \"{$l->nama}\" ditambahkan.");
    }

    public function update(Request $request, LokasiPenyimpanan $lokasi)
    {
        $data = $this->validasi($request, $lokasi);
        $terisi = $lokasi->terisi();
        if ($data['kapasitas'] < $terisi) {
            return back()->withInput()->withErrors(['kapasitas' => "Kapasitas tidak boleh di bawah jumlah kantong yang tersimpan saat ini ($terisi)."]);
        }
        if (! ($data['aktif'] ?? false) && $terisi > 0) {
            return back()->withInput()->withErrors(['aktif' => "Lokasi masih menyimpan $terisi kantong dan tidak dapat dinonaktifkan."]);
        }

        $lama = $lokasi->only(['nama', 'jenis', 'kapasitas', 'aktif']);
        DB::transaction(function () use ($lokasi, $data) {
            $namaLama = $lokasi->nama;
            $lokasi->update($data);
            // Kantong mencatat lokasi lewat nama; ikut diperbarui agar hitungan isi dan kapasitas tetap benar.
            if ($namaLama !== $lokasi->nama) {
                KantongDarah::where('lokasi_simpan', $namaLama)->update(['lokasi_simpan' => $lokasi->nama]);
            }
        });
        $this->log($lokasi, "Lokasi penyimpanan \"{$lokasi->nama}\" diperbarui.", $lama, $lokasi->only(['nama', 'jenis', 'kapasitas', 'aktif']));

        return back()->with('success', "Lokasi \"{$lokasi->nama}\" diperbarui.");
    }

    public function destroy(LokasiPenyimpanan $lokasi)
    {
        $pernahDipakai = KantongDarah::where('lokasi_simpan', $lokasi->nama)->exists();
        if ($lokasi->terisi() > 0 || $pernahDipakai) {
            return back()->withErrors(['lokasi' => "Lokasi \"{$lokasi->nama}\" memiliki riwayat kantong dan tidak dapat dihapus. Nonaktifkan bila sudah tidak dipakai."]);
        }

        $nama = $lokasi->nama;
        $this->log($lokasi, "Lokasi penyimpanan \"$nama\" dihapus.", $lokasi->only(['nama', 'jenis', 'kapasitas']), null);
        $lokasi->delete();

        return back()->with('success', "Lokasi \"$nama\" dihapus.");
    }

    private function validasi(Request $request, ?LokasiPenyimpanan $l = null): array
    {
        $data = $request->validate([
            'nama'       => ['required', 'string', 'max:100', Rule::unique('lokasi_penyimpanan', 'nama')->ignore($l?->id)],
            'jenis'      => ['required', Rule::in(array_keys(LokasiPenyimpanan::JENIS))],
            'kapasitas'  => ['required', 'integer', 'min:1', 'max:100000'],
            'suhu_min'   => ['nullable', 'numeric', 'between:-100,40'],
            'suhu_maks'  => ['nullable', 'numeric', 'between:-100,40', 'gte:suhu_min'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama lokasi wajib diisi.', 'nama.unique' => 'Nama lokasi sudah dipakai.',
            'kapasitas.required' => 'Kapasitas wajib diisi.', 'kapasitas.min' => 'Kapasitas minimal 1 kantong.',
            'suhu_maks.gte' => 'Suhu maksimum tidak boleh lebih rendah dari suhu minimum.',
        ]);
        $data['aktif'] = $request->boolean('aktif', $l === null);

        return $data;
    }

    private function log(LokasiPenyimpanan $l, string $ringkasan, ?array $lama, ?array $baru): void
    {
        LogTransaksi::catat('lokasi_diubah', 'lokasi', $l->id, $ringkasan, ['lama' => $lama, 'baru' => $baru]);
    }
}
